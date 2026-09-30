<?php
// app/Controllers/FactorController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Audit.php';
require_once __DIR__ . '/../Security.php';

class FactorController {
    public function index() {
        Auth::requireLogin();
        $pdo = Database::getConnection();

        $scope = $_GET['scope'] ?? '';
        $sql = "SELECT * FROM esg_emission_factors WHERE 1=1";
        $params = [];

        if ($scope) {
            $sql .= " AND scope_type = ?";
            $params[] = $scope;
        }

        $sql .= " ORDER BY scope_type ASC, effective_year DESC, id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $factors = $stmt->fetchAll();

        require __DIR__ . '/../../views/factors/index.php';
    }

    public function store() {
        Auth::requireRole(['admin', 'esg_lead']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('factors');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('factors');
        }

        $pdo = Database::getConnection();
        $code = trim((string)($_POST['factor_code'] ?? ''));
        $name = trim((string)($_POST['factor_name'] ?? ''));
        $scope = (string)($_POST['scope_type'] ?? '');
        $cat = trim($_POST['category']);
        $fuel = trim($_POST['fuel_type'] ?? '');
        $factor = (float)$_POST['co2e_factor'];
        $unit = trim($_POST['unit']);
        $source = trim($_POST['data_source']);
        $year = (int)$_POST['effective_year'];
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;
        if (!preg_match('/^[A-Z0-9._-]{2,50}$/i', $code) || $name === '' || mb_strlen($name, 'UTF-8') > 150
            || !in_array($scope, ['Scope1', 'Scope2', 'Scope3'], true) || $factor <= 0
            || $unit === '' || $source === '' || $year < 1900 || $year > 2100) {
            set_flash('danger', '排放係數資料格式或範圍不正確');
            redirect('factors');
        }

        $stmt = $pdo->prepare("
            INSERT INTO esg_emission_factors 
            (factor_code, factor_name, scope_type, category, fuel_type, co2e_factor, unit, data_source, effective_year, is_current, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$code, $name, $scope, $cat, $fuel, $factor, $unit, $source, $year, $isCurrent]);
        $newId = (int)$pdo->lastInsertId();

        Audit::log('CREATE', 'esg_emission_factors', $newId, null, [
            'factor_code' => $code,
            'factor_name' => $name,
            'co2e_factor' => $factor
        ]);

        set_flash('success', "碳排係數「{$name}」已成功新增！");
        redirect('factors');
    }

    public function update() {
        Auth::requireRole(['admin', 'esg_lead']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('factors');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('factors');
        }

        $id = (int)$_POST['id'];
        $pdo = Database::getConnection();

        $stmtOld = $pdo->prepare("SELECT * FROM esg_emission_factors WHERE id = ?");
        $stmtOld->execute([$id]);
        $old = $stmtOld->fetch();
        if (!$old) {
            set_flash('danger', '找不到指定係數');
            redirect('factors');
        }

        $name = trim($_POST['factor_name']);
        $scope = $_POST['scope_type'];
        $cat = trim($_POST['category']);
        $fuel = trim($_POST['fuel_type'] ?? '');
        $factor = (float)$_POST['co2e_factor'];
        $unit = trim($_POST['unit']);
        $source = trim($_POST['data_source']);
        $year = (int)$_POST['effective_year'];
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;
        if ($name === '' || mb_strlen($name, 'UTF-8') > 150
            || !in_array($scope, ['Scope1', 'Scope2', 'Scope3'], true) || $factor <= 0
            || $unit === '' || $source === '' || $year < 1900 || $year > 2100) {
            set_flash('danger', '排放係數資料格式或範圍不正確');
            redirect('factors');
        }

        $stmt = $pdo->prepare("
            UPDATE esg_emission_factors 
            SET factor_name = ?, scope_type = ?, category = ?, fuel_type = ?,
                co2e_factor = ?, unit = ?, data_source = ?, effective_year = ?, is_current = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $scope, $cat, $fuel, $factor, $unit, $source, $year, $isCurrent, $id]);

        Audit::log('UPDATE', 'esg_emission_factors', $id, $old, [
            'factor_name' => $name,
            'co2e_factor' => $factor,
            'effective_year' => $year
        ]);

        set_flash('success', "碳排係數已更新！");
        redirect('factors');
    }
}
