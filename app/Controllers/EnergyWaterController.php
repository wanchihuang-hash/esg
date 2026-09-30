<?php
// app/Controllers/EnergyWaterController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Audit.php';
require_once __DIR__ . '/../Security.php';

class EnergyWaterController {
    private function orgOptions(PDO $pdo): array {
        if (Auth::hasRole(['admin', 'esg_lead', 'auditor'])) {
            return $pdo->query("SELECT id, unit_name, unit_code FROM sys_org_units WHERE is_active = 1 ORDER BY id")->fetchAll();
        }
        $stmt = $pdo->prepare("SELECT id, unit_name, unit_code FROM sys_org_units WHERE is_active = 1 AND id = ?");
        $stmt->execute([(int)Auth::user()['org_unit_id']]);
        return $stmt->fetchAll();
    }

    private function canEditRow(array $row): bool {
        if ($row['workflow_status'] === 'locked' || !Auth::canAccessOrg((int)$row['org_unit_id'])) return false;
        if (Auth::hasRole(['admin', 'esg_lead'])) return true;
        return Auth::hasRole('collector') && (int)$row['created_by'] === Auth::id() && $row['workflow_status'] === 'draft';
    }
    public function index() {
        Auth::requireLogin();
        $pdo = Database::getConnection();

        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : 2024;
        $orgUnitId = !empty($_GET['org_unit_id']) ? (int)$_GET['org_unit_id'] : '';

        $sql = "
            SELECT ew.*, o.unit_name, o.unit_code, u.full_name as creator_name
            FROM esg_energy_water ew
            JOIN sys_org_units o ON ew.org_unit_id = o.id
            JOIN sys_users u ON ew.created_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!Auth::hasRole(['admin', 'esg_lead', 'auditor'])) {
            $sql .= " AND ew.org_unit_id = ?";
            $params[] = (int)Auth::user()['org_unit_id'];
        }

        if ($year) {
            $sql .= " AND ew.period_year = ?";
            $params[] = $year;
        }
        if ($orgUnitId) {
            $sql .= " AND ew.org_unit_id = ?";
            $params[] = $orgUnitId;
        }

        $sql .= " ORDER BY ew.period_year DESC, ew.period_month DESC, ew.org_unit_id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        // Calculate Totals
        $totals = [
            'grid_kwh' => 0.0,
            'green_kwh' => 0.0,
            'water_tap' => 0.0,
            'water_recycle' => 0.0,
            'gas_m3' => 0.0,
            'diesel_l' => 0.0,
            'gasoline_l' => 0.0
        ];

        foreach ($records as $r) {
            $totals['grid_kwh'] += (float)$r['elec_grid_kwh'];
            $totals['green_kwh'] += (float)$r['elec_green_kwh'];
            $totals['water_tap'] += (float)$r['water_tap_m3'];
            $totals['water_recycle'] += (float)$r['water_recycle_m3'];
            $totals['gas_m3'] += (float)$r['natural_gas_m3'];
            $totals['diesel_l'] += (float)$r['diesel_liters'];
            $totals['gasoline_l'] += (float)$r['gasoline_liters'];
        }

        $totalElec = $totals['grid_kwh'] + $totals['green_kwh'];
        $greenRate = $totalElec > 0 ? round(($totals['green_kwh'] / $totalElec) * 100, 2) : 0;

        $totalWater = $totals['water_tap'] + $totals['water_recycle'];
        $recycleRate = $totalWater > 0 ? round(($totals['water_recycle'] / $totalWater) * 100, 2) : 0;

        $orgs = $this->orgOptions($pdo);

        require __DIR__ . '/../../views/energy_water/index.php';
    }

    public function create() {
        Auth::requirePermission('edit_data');
        $pdo = Database::getConnection();
        $orgs = $this->orgOptions($pdo);
        require __DIR__ . '/../../views/energy_water/form.php';
    }

    public function store() {
        Auth::requirePermission('edit_data');
        Security::requirePost('energy_water');

        $pdo = Database::getConnection();
        try {
            $orgUnitId = Security::intInRange($_POST['org_unit_id'] ?? null, 1, PHP_INT_MAX, '組織');
            $year = Security::intInRange($_POST['period_year'] ?? null, 2000, 2100, '年度');
            $month = Security::intInRange($_POST['period_month'] ?? null, 1, 12, '月份');
            $grid = Security::nonNegativeNumber($_POST['elec_grid_kwh'] ?? 0, '市電');
            $green = Security::nonNegativeNumber($_POST['elec_green_kwh'] ?? 0, '綠電');
            $waterTap = Security::nonNegativeNumber($_POST['water_tap_m3'] ?? 0, '自來水');
            $waterRec = Security::nonNegativeNumber($_POST['water_recycle_m3'] ?? 0, '回收水');
            $natGas = Security::nonNegativeNumber($_POST['natural_gas_m3'] ?? 0, '天然氣');
            $diesel = Security::nonNegativeNumber($_POST['diesel_liters'] ?? 0, '柴油');
            $gasoline = Security::nonNegativeNumber($_POST['gasoline_liters'] ?? 0, '汽油');
        } catch (Throwable $e) {
            set_flash('danger', $e->getMessage());
            redirect('energy_water');
        }
        if (!Auth::canAccessOrg($orgUnitId)) {
            set_flash('danger', '不可填報其他組織的資料');
            redirect('energy_water');
        }
        $existingStmt = $pdo->prepare("SELECT * FROM esg_energy_water WHERE org_unit_id = ? AND period_year = ? AND period_month = ?");
        $existingStmt->execute([$orgUnitId, $year, $month]);
        $existing = $existingStmt->fetch();
        if ($existing && !$this->canEditRow($existing)) {
            set_flash('danger', '該期間資料已送審、封存或不在您的可編輯範圍');
            redirect('energy_water');
        }
        $status = $existing['workflow_status'] ?? 'draft';

        $stmt = $pdo->prepare("
            INSERT INTO esg_energy_water
            (org_unit_id, period_year, period_month, elec_grid_kwh, elec_green_kwh, water_tap_m3, water_recycle_m3, natural_gas_m3, diesel_liters, gasoline_liters, workflow_status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            elec_grid_kwh = VALUES(elec_grid_kwh),
            elec_green_kwh = VALUES(elec_green_kwh),
            water_tap_m3 = VALUES(water_tap_m3),
            water_recycle_m3 = VALUES(water_recycle_m3),
            natural_gas_m3 = VALUES(natural_gas_m3),
            diesel_liters = VALUES(diesel_liters),
            gasoline_liters = VALUES(gasoline_liters),
            workflow_status = VALUES(workflow_status)
        ");
        $stmt->execute([$orgUnitId, $year, $month, $grid, $green, $waterTap, $waterRec, $natGas, $diesel, $gasoline, $status, Auth::id()]);
        $idStmt = $pdo->prepare("SELECT id FROM esg_energy_water WHERE org_unit_id = ? AND period_year = ? AND period_month = ?");
        $idStmt->execute([$orgUnitId, $year, $month]);
        $newId = (int)$idStmt->fetchColumn();

        Audit::log($existing ? 'UPDATE' : 'CREATE', 'esg_energy_water', $newId, $existing ?: null, [
            'org_unit_id' => $orgUnitId,
            'year' => $year,
            'month' => $month,
            'grid' => $grid,
            'green' => $green
        ]);

        set_flash('success', '能源與水資源月度數據已保存');
        redirect('energy_water');
    }

    public function edit() {
        Auth::requirePermission('edit_data');
        $id = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM esg_energy_water WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();

        if (!$record) {
            set_flash('danger', '找不到指定記錄');
            redirect('energy_water');
        }

        if (!$this->canEditRow($record)) {
            set_flash('danger', '此記錄不在您的可編輯範圍或已被封存');
            redirect('energy_water');
        }

        $orgs = $this->orgOptions($pdo);
        require __DIR__ . '/../../views/energy_water/form.php';
    }

    public function delete() {
        Auth::requireRole('admin');
        $id = (int)($_POST['id'] ?? 0);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('energy_water');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM esg_energy_water WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row && $row['workflow_status'] !== 'locked') {
            $pdo->prepare("DELETE FROM esg_energy_water WHERE id = ?")->execute([$id]);
            Audit::log('DELETE', 'esg_energy_water', $id, $row, null);
            set_flash('success', '能資源記錄已刪除');
        } elseif ($row) {
            set_flash('danger', '鎖檔記錄不可刪除');
        }
        redirect('energy_water');
    }
}
