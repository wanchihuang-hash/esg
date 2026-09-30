<?php
// app/Controllers/ReportController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Audit.php';
require_once __DIR__ . '/../GhgEngine.php';

class ReportController {
    public function gri() {
        Auth::requireLogin();
        $pdo = Database::getConnection();

        $year = isset($_GET['year']) ? (int)$_GET['year'] : 2024;
        if ($year < 2000 || $year > 2100) $year = (int)date('Y');
        $orgUnitId = Auth::hasRole(['admin', 'esg_lead', 'auditor']) ? null : (int)Auth::user()['org_unit_id'];

        // Fetch emissions summary
        $ghgSummary = GhgEngine::getDashboardSummary($year, $orgUnitId);

        // Fetch energy & water
        $stmtEW = $pdo->prepare("
            SELECT 
                SUM(elec_grid_kwh) as total_grid,
                SUM(elec_green_kwh) as total_green,
                SUM(water_tap_m3) as total_tap,
                SUM(water_recycle_m3) as total_recycle,
                SUM(natural_gas_m3) as total_gas,
                SUM(diesel_liters) as total_diesel,
                SUM(gasoline_liters) as total_gasoline
            FROM esg_energy_water
            WHERE period_year = ? AND workflow_status IN ('approved', 'locked')
            " . ($orgUnitId ? " AND org_unit_id = ?" : "") . "
        ");
        $stmtEW->execute($orgUnitId ? [$year, $orgUnitId] : [$year]);
        $ew = $stmtEW->fetch() ?: [];

        // Fetch social
        $stmtSoc = $pdo->prepare("
            SELECT 
                SUM(male_employees) as male,
                SUM(female_employees) as female,
                SUM(disabled_employees) as disabled,
                SUM(female_managers) as female_mgr,
                SUM(total_work_hours) as hours,
                SUM(disabling_injury_count) as injuries,
                SUM(lost_days_count) as lost_days,
                SUM(training_hours_total) as training,
                SUM(volunteer_hours) as volunteer,
                SUM(donation_amount_ntd) as donation
            FROM esg_social_metrics
            WHERE period_year = ?
            " . ($orgUnitId ? " AND org_unit_id = ?" : "") . "
        ");
        $stmtSoc->execute($orgUnitId ? [$year, $orgUnitId] : [$year]);
        $soc = $stmtSoc->fetch() ?: [];

        $totalEmp = ((int)($soc['male'] ?? 0)) + ((int)($soc['female'] ?? 0));
        $femaleRatio = $totalEmp > 0 ? round(((int)($soc['female'] ?? 0) / $totalEmp) * 100, 1) : 0;
        $totalHours = (float)($soc['hours'] ?? 0);
        $overallFr = $totalHours > 0 ? round(((int)($soc['injuries'] ?? 0) * 1000000) / $totalHours, 4) : 0;
        $overallSr = $totalHours > 0 ? round(((int)($soc['lost_days'] ?? 0) * 1000000) / $totalHours, 4) : 0;

        // Fetch governance
        $stmtGov = $pdo->prepare("SELECT * FROM esg_governance_metrics WHERE period_year = ?");
        $stmtGov->execute([$year]);
        $gov = $stmtGov->fetch() ?: [];

        // Check if year is locked
        $stmtLock = $pdo->prepare("
            SELECT COUNT(*) FROM esg_ghg_emissions 
            WHERE period_year = ? AND workflow_status != 'locked'
            " . ($orgUnitId ? " AND org_unit_id = ?" : "") . "
        ");
        $stmtLock->execute($orgUnitId ? [$year, $orgUnitId] : [$year]);
        $unlockedCount = (int)$stmtLock->fetchColumn();
        $isYearLocked = ($unlockedCount === 0 && $ghgSummary['total_emissions'] > 0);

        require __DIR__ . '/../../views/reports/gri.php';
    }

    public function exportCsv() {
        Auth::requireLogin();
        $pdo = Database::getConnection();
        $year = isset($_GET['year']) ? (int)$_GET['year'] : 2024;
        if ($year < 2000 || $year > 2100) {
            http_response_code(422);
            exit('年度格式不正確');
        }
        $orgUnitId = Auth::hasRole(['admin', 'esg_lead', 'auditor']) ? null : (int)Auth::user()['org_unit_id'];

        $stmt = $pdo->prepare("
            SELECT 
                e.id, o.unit_name, e.period_year, e.period_month, e.scope_type, 
                e.emission_source, f.factor_name, e.activity_amount, e.activity_unit, 
                e.calculated_tco2e, e.invoice_no, e.workflow_status, e.created_at
            FROM esg_ghg_emissions e
            JOIN sys_org_units o ON e.org_unit_id = o.id
            JOIN esg_emission_factors f ON e.factor_id = f.id
            WHERE e.period_year = ? AND e.workflow_status IN ('approved', 'locked')
            " . ($orgUnitId ? " AND e.org_unit_id = ?" : "") . "
            ORDER BY e.period_month ASC, e.scope_type ASC
        ");
        $stmt->execute($orgUnitId ? [$year, $orgUnitId] : [$year]);
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=ESG_GHG_Inventory_{$year}.csv");

        // Output BOM for Excel UTF-8 display
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['編號', '組織廠區', '年度', '月份', '範疇分類', '排放源描述', '所用係數名稱', '活動量', '計量單位', '排放當量(tCO2e)', '發票/憑單電號', '審批狀態', '建檔時間']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['unit_name'],
                $r['period_year'],
                $r['period_month'],
                $r['scope_type'],
                $r['emission_source'],
                $r['factor_name'],
                $r['activity_amount'],
                $r['activity_unit'],
                $r['calculated_tco2e'],
                $r['invoice_no'],
                $r['workflow_status'],
                $r['created_at']
            ]);
        }
        fclose($out);
        Audit::log('EXPORT', 'esg_ghg_emissions', null, null, ['type' => 'CSV', 'year' => $year]);
        exit;
    }

    public function lockYear() {
        Auth::requireRole(['admin', 'esg_lead']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('report_gri');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('report_gri');
        }

        $year = (int)$_POST['year'];
        if ($year < 2000 || $year > 2100) {
            set_flash('danger', '年度格式不正確');
            redirect('report_gri');
        }
        $pdo = Database::getConnection();

        $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM esg_ghg_emissions WHERE period_year = ? AND workflow_status <> 'approved' AND workflow_status <> 'locked'");
        $pendingStmt->execute([$year]);
        if ((int)$pendingStmt->fetchColumn() > 0) {
            set_flash('danger', '年度仍有草稿、待審或退回資料，完成處理前不可封存');
            redirect('report_gri', ['year' => $year]);
        }

        $pdo->beginTransaction();
        try {

        $stmt = $pdo->prepare("
            UPDATE esg_ghg_emissions 
            SET workflow_status = 'locked', reviewed_at = NOW(), reviewer_id = ?
            WHERE period_year = ? AND workflow_status = 'approved'
        ");
        $stmt->execute([Auth::id(), $year]);
        $affected = $stmt->rowCount();

        // Also lock energy & water
        $stmtEW = $pdo->prepare("
            UPDATE esg_energy_water 
            SET workflow_status = 'locked'
            WHERE period_year = ?
        ");
        $stmtEW->execute([$year]);

        Audit::log('LOCK_ANNUAL_DATA', 'esg_ghg_emissions', $year, null, ['affected_rows' => $affected]);
        $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Annual lock failed: ' . $e->getMessage());
            set_flash('danger', '年度封存失敗，資料未變更');
            redirect('report_gri', ['year' => $year]);
        }

        set_flash('success', "已完成 {$year} 年度數據全盤審定與封存鎖檔！共鎖定 {$affected} 筆溫室氣體活動記錄。");
        redirect('report_gri', ['year' => $year]);
    }
}
