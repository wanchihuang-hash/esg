<?php
// app/Controllers/AuditController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';

class AuditController {
    public function index() {
        Auth::requireRole(['admin', 'esg_lead', 'auditor']);
        $pdo = Database::getConnection();

        $action = $_GET['action_type'] ?? '';
        $table = $_GET['table'] ?? '';
        $keyword = trim($_GET['keyword'] ?? '');

        $sql = "
            SELECT a.*, u.username, u.full_name, r.role_name
            FROM sys_audit_logs a
            LEFT JOIN sys_users u ON a.user_id = u.id
            LEFT JOIN sys_roles r ON u.role_id = r.id
            WHERE 1=1
        ";
        $params = [];

        if ($action) {
            $sql .= " AND a.action_type = ?";
            $params[] = $action;
        }
        if ($table) {
            $sql .= " AND a.target_table = ?";
            $params[] = $table;
        }
        if ($keyword) {
            $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR a.ip_address LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        $sql .= " ORDER BY a.id DESC LIMIT 200";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Get action types and tables for filter
        $actions = $pdo->query("SELECT DISTINCT action_type FROM sys_audit_logs ORDER BY action_type")->fetchAll(PDO::FETCH_COLUMN);
        $tables = $pdo->query("SELECT DISTINCT target_table FROM sys_audit_logs ORDER BY target_table")->fetchAll(PDO::FETCH_COLUMN);

        require __DIR__ . '/../../views/audit/index.php';
    }
}
