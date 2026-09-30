<?php
// app/Controllers/GhgController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Audit.php';
require_once __DIR__ . '/../GhgEngine.php';
require_once __DIR__ . '/../Security.php';

class GhgController {
    private function canEditRow(array $row): bool {
        if ($row['workflow_status'] === 'locked') return false;
        if (Auth::hasRole(['admin', 'esg_lead'])) {
            return in_array($row['workflow_status'], ['draft', 'rejected'], true);
        }
        return Auth::hasRole('collector')
            && (int)$row['created_by'] === Auth::id()
            && Auth::canAccessOrg((int)$row['org_unit_id'])
            && in_array($row['workflow_status'], ['draft', 'rejected'], true);
    }

    private function orgOptions(PDO $pdo): array {
        if (Auth::hasRole(['admin', 'esg_lead', 'auditor'])) {
            return $pdo->query("SELECT id, unit_name, unit_code FROM sys_org_units WHERE is_active = 1 ORDER BY id")->fetchAll();
        }
        $stmt = $pdo->prepare("SELECT id, unit_name, unit_code FROM sys_org_units WHERE is_active = 1 AND id = ?");
        $stmt->execute([(int)Auth::user()['org_unit_id']]);
        return $stmt->fetchAll();
    }
    public function index() {
        Auth::requireLogin();
        $pdo = Database::getConnection();

        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : 2024;
        $scope = $_GET['scope'] ?? '';
        $orgUnitId = !empty($_GET['org_unit_id']) ? (int)$_GET['org_unit_id'] : '';
        $status = $_GET['status'] ?? '';
        $keyword = trim($_GET['keyword'] ?? '');

        $sql = "
            SELECT e.*, o.unit_name, o.unit_code, f.factor_code, f.factor_name, f.co2e_factor, f.unit as factor_unit,
                   u.full_name as creator_name, r.full_name as reviewer_name
            FROM esg_ghg_emissions e
            JOIN sys_org_units o ON e.org_unit_id = o.id
            JOIN esg_emission_factors f ON e.factor_id = f.id
            JOIN sys_users u ON e.created_by = u.id
            LEFT JOIN sys_users r ON e.reviewer_id = r.id
            WHERE 1=1
        ";
        $params = [];

        if (!Auth::hasRole(['admin', 'esg_lead', 'auditor'])) {
            $sql .= " AND e.org_unit_id = ?";
            $params[] = (int)Auth::user()['org_unit_id'];
        }

        if ($year) {
            $sql .= " AND e.period_year = ?";
            $params[] = $year;
        }
        if ($scope) {
            $sql .= " AND e.scope_type = ?";
            $params[] = $scope;
        }
        if ($orgUnitId) {
            $sql .= " AND e.org_unit_id = ?";
            $params[] = $orgUnitId;
        }
        if ($status) {
            $sql .= " AND e.workflow_status = ?";
            $params[] = $status;
        }
        if ($keyword) {
            $sql .= " AND (e.emission_source LIKE ? OR e.invoice_no LIKE ? OR f.factor_name LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        $sql .= " ORDER BY e.period_year DESC, e.period_month DESC, e.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $emissions = $stmt->fetchAll();

        // Calculate filtered total tCO2e
        $totalTco2e = 0.0;
        foreach ($emissions as $em) {
            $totalTco2e += (float)$em['calculated_tco2e'];
        }

        // Data for dropdown filters
        $orgs = $this->orgOptions($pdo);
        $factors = $pdo->query("SELECT id, factor_code, factor_name, scope_type, co2e_factor, unit FROM esg_emission_factors WHERE is_current = 1 ORDER BY scope_type, id")->fetchAll();

        require __DIR__ . '/../../views/ghg/index.php';
    }

    public function create() {
        Auth::requirePermission('edit_data');
        $pdo = Database::getConnection();

        $orgs = $this->orgOptions($pdo);
        $factors = $pdo->query("SELECT id, factor_code, factor_name, scope_type, co2e_factor, unit, data_source FROM esg_emission_factors WHERE is_current = 1 ORDER BY scope_type, id")->fetchAll();

        require __DIR__ . '/../../views/ghg/form.php';
    }

    public function store() {
        Auth::requirePermission('edit_data');
        Security::requirePost('ghg');

        $pdo = Database::getConnection();
        try {
            $orgUnitId = Security::intInRange($_POST['org_unit_id'] ?? null, 1, PHP_INT_MAX, '組織');
            $year = Security::intInRange($_POST['period_year'] ?? null, 2000, 2100, '年度');
            $month = Security::intInRange($_POST['period_month'] ?? null, 1, 12, '月份');
            $factorId = Security::intInRange($_POST['factor_id'] ?? null, 1, PHP_INT_MAX, '排放係數');
            $emissionSource = Security::requiredText($_POST['emission_source'] ?? '', 120, '排放源');
            $activityAmount = Security::nonNegativeNumber($_POST['activity_amount'] ?? null, '活動量');
            $invoiceNo = trim((string)($_POST['invoice_no'] ?? ''));
            if (mb_strlen($invoiceNo, 'UTF-8') > 60) throw new InvalidArgumentException('發票／憑單編號過長');
        } catch (Throwable $e) {
            set_flash('danger', $e->getMessage());
            redirect('ghg_create');
        }
        if (!Auth::canAccessOrg($orgUnitId)) {
            set_flash('danger', '不可填報其他組織的資料');
            redirect('ghg');
        }
        $action = $_POST['submit_action'] ?? 'draft'; // 'draft' or 'pending'

        // Fetch factor details
        $stmtFactor = $pdo->prepare("SELECT * FROM esg_emission_factors WHERE id = ?");
        $stmtFactor->execute([$factorId]);
        $factor = $stmtFactor->fetch();
        if (!$factor) {
            set_flash('danger', '未找到指定的排放係數');
            redirect('ghg');
        }

        // Calculate tCO2e: (Activity * Factor) / 1000
        $tco2e = GhgEngine::calculate($activityAmount, (float)$factor['co2e_factor']);

        // Handle proof file upload
        try {
            $filePath = isset($_FILES['proof_file']) ? Security::storeProofUpload($_FILES['proof_file']) : null;
        } catch (Throwable $e) {
            set_flash('danger', $e->getMessage());
            redirect('ghg_create');
        }

        $status = ($action === 'submit') ? 'pending' : 'draft';

        $stmt = $pdo->prepare("
            INSERT INTO esg_ghg_emissions 
            (org_unit_id, period_year, period_month, scope_type, emission_source, factor_id, activity_amount, activity_unit, calculated_tco2e, invoice_no, proof_file_path, workflow_status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $orgUnitId, $year, $month, $factor['scope_type'], $emissionSource, $factorId,
            $activityAmount, $factor['unit'], $tco2e, $invoiceNo, $filePath, $status, Auth::id()
        ]);
        $newId = (int)$pdo->lastInsertId();

        Audit::log('CREATE', 'esg_ghg_emissions', $newId, null, [
            'org_unit_id' => $orgUnitId,
            'source' => $emissionSource,
            'amount' => $activityAmount,
            'tco2e' => $tco2e,
            'status' => $status
        ]);

        // Check Anomaly
        $anomalyCheck = GhgEngine::checkAnomaly($orgUnitId, $factor['scope_type'], $tco2e);
        if ($anomalyCheck['is_anomaly']) {
            set_flash('warning', "活動數據已新增，但碳排量 ({$tco2e} tCO2e) 與歷史均值偏差達 {$anomalyCheck['diff_percent']}%，系統已標註異常警示！");
        } else {
            set_flash('success', "活動數據已成功新增！核算碳排：{$tco2e} tCO2e");
        }

        redirect('ghg');
    }

    public function edit() {
        Auth::requirePermission('edit_data');
        $id = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM esg_ghg_emissions WHERE id = ?");
        $stmt->execute([$id]);
        $emission = $stmt->fetch();

        if (!$emission) {
            set_flash('danger', '找不到指定記錄');
            redirect('ghg');
        }

        if (!$this->canEditRow($emission)) {
            set_flash('danger', '此記錄不在您的可編輯範圍或已被鎖檔');
            redirect('ghg');
        }

        $orgs = $this->orgOptions($pdo);
        $factors = $pdo->query("SELECT id, factor_code, factor_name, scope_type, co2e_factor, unit, data_source FROM esg_emission_factors WHERE is_current = 1 ORDER BY scope_type, id")->fetchAll();

        require __DIR__ . '/../../views/ghg/form.php';
    }

    public function update() {
        Auth::requirePermission('edit_data');
        Security::requirePost('ghg');

        $id = (int)$_POST['id'];
        $pdo = Database::getConnection();

        $stmtOld = $pdo->prepare("SELECT * FROM esg_ghg_emissions WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldRecord = $stmtOld->fetch();
        if (!$oldRecord) {
            set_flash('danger', '找不到指定記錄');
            redirect('ghg');
        }

        if (!$this->canEditRow($oldRecord)) {
            set_flash('danger', '此記錄不在您的可編輯範圍或已被封存');
            redirect('ghg');
        }

        try {
            $orgUnitId = Security::intInRange($_POST['org_unit_id'] ?? null, 1, PHP_INT_MAX, '組織');
            $year = Security::intInRange($_POST['period_year'] ?? null, 2000, 2100, '年度');
            $month = Security::intInRange($_POST['period_month'] ?? null, 1, 12, '月份');
            $factorId = Security::intInRange($_POST['factor_id'] ?? null, 1, PHP_INT_MAX, '排放係數');
            $emissionSource = Security::requiredText($_POST['emission_source'] ?? '', 120, '排放源');
            $activityAmount = Security::nonNegativeNumber($_POST['activity_amount'] ?? null, '活動量');
            $invoiceNo = trim((string)($_POST['invoice_no'] ?? ''));
        } catch (Throwable $e) {
            set_flash('danger', $e->getMessage());
            redirect('ghg_edit', ['id' => $id]);
        }
        if (!Auth::canAccessOrg($orgUnitId)) {
            set_flash('danger', '不可將資料移至其他組織');
            redirect('ghg');
        }
        $action = $_POST['submit_action'] ?? '';

        $stmtFactor = $pdo->prepare("SELECT * FROM esg_emission_factors WHERE id = ?");
        $stmtFactor->execute([$factorId]);
        $factor = $stmtFactor->fetch();
        if (!$factor) {
            set_flash('danger', '未找到指定的排放係數');
            redirect('ghg_edit', ['id' => $id]);
        }
        $tco2e = GhgEngine::calculate($activityAmount, (float)$factor['co2e_factor']);

        $filePath = $oldRecord['proof_file_path'];
        try {
            $newFilePath = isset($_FILES['proof_file']) ? Security::storeProofUpload($_FILES['proof_file']) : null;
            if ($newFilePath !== null) $filePath = $newFilePath;
        } catch (Throwable $e) {
            set_flash('danger', $e->getMessage());
            redirect('ghg_edit', ['id' => $id]);
        }

        $status = $oldRecord['workflow_status'];
        if ($action === 'submit') {
            $status = 'pending';
        }

        $stmt = $pdo->prepare("
            UPDATE esg_ghg_emissions 
            SET org_unit_id = ?, period_year = ?, period_month = ?, scope_type = ?, 
                emission_source = ?, factor_id = ?, activity_amount = ?, activity_unit = ?, 
                calculated_tco2e = ?, invoice_no = ?, proof_file_path = ?, workflow_status = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $orgUnitId, $year, $month, $factor['scope_type'],
            $emissionSource, $factorId, $activityAmount, $factor['unit'],
            $tco2e, $invoiceNo, $filePath, $status, $id
        ]);

        Audit::log('UPDATE', 'esg_ghg_emissions', $id, $oldRecord, [
            'activity_amount' => $activityAmount,
            'calculated_tco2e' => $tco2e,
            'workflow_status' => $status
        ]);

        set_flash('success', "活動數據已成功更新！");
        redirect('ghg');
    }

    public function workflow() {
        Auth::requireLogin();
        Security::requirePost('ghg');

        $id = (int)$_POST['id'];
        $action = $_POST['action'] ?? '';
        $comment = trim($_POST['comment'] ?? '');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM esg_ghg_emissions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            set_flash('danger', '記錄不存在');
            redirect('ghg');
        }

        $newStatus = $row['workflow_status'];
        $reviewerId = Auth::id();

        if ($action === 'submit') {
            if (!$this->canEditRow($row) || !in_array($row['workflow_status'], ['draft', 'rejected'], true)) {
                set_flash('danger', '只有資料建立者可將草稿或退回資料重新送審');
                redirect('ghg');
            }
            $newStatus = 'pending';
        } elseif ($action === 'approve') {
            if (!Auth::can('approve_data') || !Auth::canAccessOrg((int)$row['org_unit_id']) || $row['workflow_status'] !== 'pending') {
                set_flash('danger', '您沒有審核權限');
                redirect('ghg');
            }
            $newStatus = 'approved';
        } elseif ($action === 'reject') {
            if (!Auth::can('approve_data') || !Auth::canAccessOrg((int)$row['org_unit_id']) || $row['workflow_status'] !== 'pending') {
                set_flash('danger', '您沒有審核權限');
                redirect('ghg');
            }
            $newStatus = 'rejected';
        } elseif ($action === 'lock') {
            if (!Auth::can('lock_data') || $row['workflow_status'] !== 'approved') {
                set_flash('danger', '只有 ESG 負責人或管理員具備鎖檔權限');
                redirect('ghg');
            }
            $newStatus = 'locked';
        } else {
            set_flash('danger', '不支援的工作流動作');
            redirect('ghg');
        }

        $update = $pdo->prepare("
            UPDATE esg_ghg_emissions 
            SET workflow_status = ?, reviewer_id = ?, reviewer_comment = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$newStatus, $reviewerId, $comment ?: $row['reviewer_comment'], $id]);

        Audit::log(strtoupper($action), 'esg_ghg_emissions', $id, [
            'status' => $row['workflow_status']
        ], [
            'status' => $newStatus,
            'comment' => $comment
        ]);

        set_flash('success', "審批狀態已更新為：{$newStatus}");
        redirect('ghg');
    }

    public function delete() {
        Auth::requireLogin();
        $id = (int)($_POST['id'] ?? 0);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('ghg');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM esg_ghg_emissions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            set_flash('danger', '記錄不存在');
            redirect('ghg');
        }

        if ($row['workflow_status'] === 'locked') {
            set_flash('danger', '鎖檔記錄不可刪除');
            redirect('ghg');
        }

        $canDelete = (Auth::hasRole('admin') && in_array($row['workflow_status'], ['draft', 'rejected'], true))
            || (Auth::hasRole('collector') && (int)$row['created_by'] === Auth::id()
                && Auth::canAccessOrg((int)$row['org_unit_id'])
                && in_array($row['workflow_status'], ['draft', 'rejected'], true));
        if (!$canDelete) {
            set_flash('danger', '您沒有刪除此記錄的權限');
            redirect('ghg');
        }

        $del = $pdo->prepare("DELETE FROM esg_ghg_emissions WHERE id = ?");
        $del->execute([$id]);

        Audit::log('DELETE', 'esg_ghg_emissions', $id, $row, null);

        set_flash('success', '記錄已刪除');
        redirect('ghg');
    }
}
