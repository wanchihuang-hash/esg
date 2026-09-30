<?php
// app/GhgEngine.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Config.php';

class GhgEngine {
    /**
     * Calculate metric tons of CO2 equivalent (tCO2e)
     * Formula: tCO2e = (Activity Amount * Factor CO2e) / 1000
     */
    public static function calculate(float $activityAmount, float $co2eFactor, float $gwp = 1.0): float {
        if ($activityAmount <= 0 || $co2eFactor <= 0) {
            return 0.0000;
        }
        $kgCO2e = $activityAmount * $co2eFactor * $gwp;
        return round($kgCO2e / 1000.0, 4);
    }

    /**
     * Check if a newly submitted activity value triggers the anomaly alert
     */
    public static function checkAnomaly(int $orgUnitId, string $scopeType, float $currentTco2e): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT AVG(calculated_tco2e) as avg_emission
            FROM esg_ghg_emissions
            WHERE org_unit_id = ? AND scope_type = ? AND workflow_status IN ('approved', 'locked')
        ");
        $stmt->execute([$orgUnitId, $scopeType]);
        $avg = (float)$stmt->fetchColumn();

        if ($avg <= 0) {
            return ['is_anomaly' => false, 'diff_percent' => 0, 'avg' => 0];
        }

        $diffPercent = (($currentTco2e - $avg) / $avg) * 100;
        $threshold = (float)Config::get('ghg_anomaly_threshold', 20.0);
        $isAnomaly = abs($diffPercent) >= $threshold;

        return [
            'is_anomaly' => $isAnomaly,
            'diff_percent' => round($diffPercent, 1),
            'avg' => round($avg, 4),
            'threshold' => $threshold
        ];
    }

    /**
     * Get aggregate statistics for Dashboard
     */
    public static function getDashboardSummary(?int $year = null, ?int $orgUnitId = null): array {
        $pdo = Database::getConnection();
        $year = $year ?: (int)date('Y');

        $where = "WHERE period_year = :year";
        $params = ['year' => $year];

        if ($orgUnitId) {
            $where .= " AND org_unit_id = :org_unit_id";
            $params['org_unit_id'] = $orgUnitId;
        }

        // Official KPI and disclosure totals must only use reviewed data.
        $approvedWhere = $where . " AND workflow_status IN ('approved', 'locked')";

        // Scope emissions
        $sql = "
            SELECT 
                scope_type,
                SUM(calculated_tco2e) as total_tco2e
            FROM esg_ghg_emissions
            {$approvedWhere}
            GROUP BY scope_type
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $scopeRows = $stmt->fetchAll();

        $scopes = ['Scope1' => 0.0, 'Scope2' => 0.0, 'Scope3' => 0.0];
        $totalEmissions = 0.0;
        foreach ($scopeRows as $row) {
            $val = (float)$row['total_tco2e'];
            $scopes[$row['scope_type']] = $val;
            $totalEmissions += $val;
        }

        // Monthly trends
        $sqlMonth = "
            SELECT 
                period_month,
                scope_type,
                SUM(calculated_tco2e) as month_tco2e
            FROM esg_ghg_emissions
            {$approvedWhere}
            GROUP BY period_month, scope_type
            ORDER BY period_month ASC
        ";
        $stmtMonth = $pdo->prepare($sqlMonth);
        $stmtMonth->execute($params);
        $monthRows = $stmtMonth->fetchAll();

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[$m] = ['Scope1' => 0.0, 'Scope2' => 0.0, 'Scope3' => 0.0, 'Total' => 0.0];
        }
        foreach ($monthRows as $r) {
            $m = (int)$r['period_month'];
            $sc = $r['scope_type'];
            $val = (float)$r['month_tco2e'];
            $monthlyData[$m][$sc] = $val;
            $monthlyData[$m]['Total'] += $val;
        }

        // Plant breakdown
        $sqlPlant = "
            SELECT 
                o.id,
                o.unit_name,
                SUM(e.calculated_tco2e) as total_tco2e
            FROM esg_ghg_emissions e
            JOIN sys_org_units o ON e.org_unit_id = o.id
            {$approvedWhere}
            GROUP BY o.id, o.unit_name
            ORDER BY total_tco2e DESC
        ";
        $stmtPlant = $pdo->prepare($sqlPlant);
        $stmtPlant->execute($params);
        $plantRows = $stmtPlant->fetchAll();

        // Energy & Water stats
        $sqlEW = "
            SELECT 
                SUM(elec_grid_kwh) as total_grid_kwh,
                SUM(elec_green_kwh) as total_green_kwh,
                SUM(water_tap_m3) as total_tap_m3,
                SUM(water_recycle_m3) as total_recycle_m3
            FROM esg_energy_water
            {$approvedWhere}
        ";
        $stmtEW = $pdo->prepare($sqlEW);
        $stmtEW->execute($params);
        $ew = $stmtEW->fetch() ?: [];

        $totalElec = ((float)($ew['total_grid_kwh'] ?? 0)) + ((float)($ew['total_green_kwh'] ?? 0));
        $greenRatio = $totalElec > 0 ? round((((float)$ew['total_green_kwh']) / $totalElec) * 100, 2) : 0;

        $totalWater = ((float)($ew['total_tap_m3'] ?? 0)) + ((float)($ew['total_recycle_m3'] ?? 0));
        $recycleRatio = $totalWater > 0 ? round((((float)$ew['total_recycle_m3']) / $totalWater) * 100, 2) : 0;

        // Status counts (Draft, Pending, Approved, Locked)
        $sqlStatus = "
            SELECT workflow_status, COUNT(*) as cnt
            FROM esg_ghg_emissions
            {$where}
            GROUP BY workflow_status
        ";
        $stmtStatus = $pdo->prepare($sqlStatus);
        $stmtStatus->execute($params);
        $statusCounts = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'year' => $year,
            'total_emissions' => round($totalEmissions, 2),
            'scopes' => $scopes,
            'monthly' => $monthlyData,
            'plants' => $plantRows,
            'energy_water' => [
                'total_elec_kwh' => $totalElec,
                'green_ratio' => $greenRatio,
                'total_water_m3' => $totalWater,
                'recycle_ratio' => $recycleRatio
            ],
            'workflow' => [
                'draft' => $statusCounts['draft'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
                'approved' => $statusCounts['approved'] ?? 0,
                'locked' => $statusCounts['locked'] ?? 0,
            ]
        ];
    }
}
