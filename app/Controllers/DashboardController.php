<?php
// app/Controllers/DashboardController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/../GhgEngine.php';

class DashboardController {
    public function index() {
        Auth::requireLogin();
        $pdo = Database::getConnection();

        // Get filter inputs
        $year = isset($_GET['year']) ? (int)$_GET['year'] : 2024;
        $orgUnitId = !empty($_GET['org_unit_id']) ? (int)$_GET['org_unit_id'] : null;
        if (!Auth::hasRole(['admin', 'esg_lead', 'auditor'])) {
            $orgUnitId = (int)Auth::user()['org_unit_id'];
        }

        // Fetch org units for dropdown
        if (Auth::hasRole(['admin', 'esg_lead', 'auditor'])) {
            $orgs = $pdo->query("SELECT id, unit_name, unit_code FROM sys_org_units WHERE is_active = 1 ORDER BY id")->fetchAll();
        } else {
            $stmtOrg = $pdo->prepare("SELECT id, unit_name, unit_code FROM sys_org_units WHERE is_active = 1 AND id = ?");
            $stmtOrg->execute([$orgUnitId]);
            $orgs = $stmtOrg->fetchAll();
        }

        // Fetch summary
        $summary = GhgEngine::getDashboardSummary($year, $orgUnitId);

        // Calculate Year-on-Year comparison with configured baseline year
        $prevYear = (int)Config::get('ghg_base_year', $year - 1);
        $prevSummary = GhgEngine::getDashboardSummary($prevYear, $orgUnitId);
        $yoyDiffPercent = 0;
        if ($prevSummary['total_emissions'] > 0) {
            $yoyDiffPercent = round((($summary['total_emissions'] - $prevSummary['total_emissions']) / $prevSummary['total_emissions']) * 100, 2);
        }

        // Reduction Target vs baseline from Config
        $targetVal = (float)Config::get('ghg_reduction_target', 5.0);
        $reductionTargetPercent = -abs($targetVal);
        $actualReductionPercent = ($prevSummary['total_emissions'] > 0) ? -($yoyDiffPercent) : 0;
        $targetProgress = ($reductionTargetPercent != 0) ? min(100, max(0, round(($actualReductionPercent / abs($reductionTargetPercent)) * 100, 1))) : 0;

        // Fetch anomaly items (exceeding historical avg by 20%)
        $anomalies = [];
        $recentStmt = $pdo->prepare("
            SELECT e.*, o.unit_name, f.factor_name, f.unit as factor_unit
            FROM esg_ghg_emissions e
            JOIN sys_org_units o ON e.org_unit_id = o.id
            JOIN esg_emission_factors f ON e.factor_id = f.id
            WHERE e.period_year = :year AND e.workflow_status IN ('approved', 'locked')
              " . ($orgUnitId ? "AND e.org_unit_id = :org_unit_id" : "") . "
            ORDER BY e.created_at DESC
            LIMIT 50
        ");
        $recentParams = ['year' => $year];
        if ($orgUnitId) $recentParams['org_unit_id'] = $orgUnitId;
        $recentStmt->execute($recentParams);
        $recentEmissions = $recentStmt->fetchAll();

        foreach ($recentEmissions as $em) {
            $check = GhgEngine::checkAnomaly((int)$em['org_unit_id'], $em['scope_type'], (float)$em['calculated_tco2e']);
            if ($check['is_anomaly']) {
                $em['anomaly_detail'] = $check;
                $anomalies[] = $em;
                if (count($anomalies) >= 5) break; // show top 5 anomalies
            }
        }

        require __DIR__ . '/../../views/dashboard/index.php';
    }
}
