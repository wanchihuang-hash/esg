<?php
// app/Controllers/ApiController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../GhgEngine.php';
require_once __DIR__ . '/../Security.php';

class ApiController {
    private function jsonResponse(array $data, int $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function factorInfo() {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM esg_emission_factors WHERE id = ?");
        $stmt->execute([$id]);
        $factor = $stmt->fetch();

        if ($factor) {
            $this->jsonResponse(['status' => 'success', 'data' => $factor]);
        } else {
            $this->jsonResponse(['status' => 'error', 'message' => '係數不存在'], 404);
        }
    }

    public function calculate() {
        Auth::requireLogin();
        $factorId = (int)($_POST['factor_id'] ?? $_GET['factor_id'] ?? 0);
        $rawAmount = $_POST['amount'] ?? $_GET['amount'] ?? null;
        if (!is_numeric($rawAmount) || !is_finite((float)$rawAmount) || (float)$rawAmount < 0) {
            $this->jsonResponse(['status' => 'error', 'message' => '活動量格式不正確'], 422);
        }
        $amount = (float)$rawAmount;
        $orgUnitId = (int)($_POST['org_unit_id'] ?? $_GET['org_unit_id'] ?? 0);
        if ($orgUnitId > 0 && !Auth::canAccessOrg($orgUnitId)) {
            $this->jsonResponse(['status' => 'error', 'message' => '無權存取指定組織'], 403);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM esg_emission_factors WHERE id = ?");
        $stmt->execute([$factorId]);
        $factor = $stmt->fetch();

        if (!$factor) {
            $this->jsonResponse(['status' => 'error', 'message' => '係數不存在'], 404);
        }

        $tco2e = GhgEngine::calculate($amount, (float)$factor['co2e_factor']);
        $anomaly = GhgEngine::checkAnomaly($orgUnitId, $factor['scope_type'], $tco2e);

        $this->jsonResponse([
            'status' => 'success',
            'data' => [
                'factor_code' => $factor['factor_code'],
                'co2e_factor' => (float)$factor['co2e_factor'],
                'unit' => $factor['unit'],
                'activity_amount' => $amount,
                'calculated_tco2e' => $tco2e,
                'anomaly' => $anomaly,
                'formula' => "{$amount} {$factor['unit']} × {$factor['co2e_factor']} kgCO2e/{$factor['unit']} ÷ 1000 = {$tco2e} tCO2e"
            ]
        ]);
    }

    public function dashboardSummary() {
        Auth::requireLogin();
        $year = isset($_GET['year']) ? (int)$_GET['year'] : 2024;
        if ($year < 2000 || $year > 2100) {
            $this->jsonResponse(['status' => 'error', 'message' => '年度格式不正確'], 422);
        }
        $orgUnitId = !empty($_GET['org_unit_id']) ? (int)$_GET['org_unit_id'] : null;
        if (!Auth::hasRole(['admin', 'esg_lead', 'auditor'])) {
            $orgUnitId = (int)Auth::user()['org_unit_id'];
        } elseif ($orgUnitId && !Auth::canAccessOrg($orgUnitId)) {
            $this->jsonResponse(['status' => 'error', 'message' => '無權存取指定組織'], 403);
        }

        $summary = GhgEngine::getDashboardSummary($year, $orgUnitId);
        $this->jsonResponse(['status' => 'success', 'data' => $summary]);
    }

    public function emissions() {
        Auth::requireLogin();
        $year = isset($_GET['year']) ? (int)$_GET['year'] : 2024;
        if ($year < 2000 || $year > 2100) {
            $this->jsonResponse(['status' => 'error', 'message' => '年度格式不正確'], 422);
        }
        $pdo = Database::getConnection();
        $orgUnitId = Auth::hasRole(['admin', 'esg_lead', 'auditor']) ? null : (int)Auth::user()['org_unit_id'];
        $stmt = $pdo->prepare("
            SELECT e.*, o.unit_name, f.factor_name, f.co2e_factor, f.unit as factor_unit
            FROM esg_ghg_emissions e
            JOIN sys_org_units o ON e.org_unit_id = o.id
            JOIN esg_emission_factors f ON e.factor_id = f.id
            WHERE e.period_year = ? AND e.workflow_status IN ('approved', 'locked')
            " . ($orgUnitId ? " AND e.org_unit_id = ?" : "") . "
            ORDER BY e.period_month ASC
        ");
        $stmt->execute($orgUnitId ? [$year, $orgUnitId] : [$year]);
        $data = $stmt->fetchAll();
        $this->jsonResponse(['status' => 'success', 'count' => count($data), 'data' => $data]);
    }
}
