<?php
// app/Controllers/OrgController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Audit.php';

class OrgController {
    public function index() {
        Auth::requireLogin();
        $pdo = Database::getConnection();

        $orgs = $pdo->query("
            SELECT o.*, p.unit_name as parent_name,
                   (SELECT COUNT(*) FROM sys_users WHERE org_unit_id = o.id) as user_count,
                   (SELECT COUNT(*) FROM esg_ghg_emissions WHERE org_unit_id = o.id) as emission_count
            FROM sys_org_units o
            LEFT JOIN sys_org_units p ON o.parent_id = p.id
            ORDER BY o.id ASC
        ")->fetchAll();

        require __DIR__ . '/../../views/org/index.php';
    }

    public function store() {
        Auth::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('org');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('org');
        }

        $pdo = Database::getConnection();
        $code = trim($_POST['unit_code']);
        $name = trim($_POST['unit_name']);
        $type = (string)($_POST['unit_type'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $country = trim($_POST['country'] ?? 'Taiwan');
        $address = trim($_POST['address'] ?? '');
        if (!preg_match('/^[A-Za-z0-9._-]{2,50}$/', $code) || $name === '' || mb_strlen($name, 'UTF-8') > 100
            || !in_array($type, ['group', 'company', 'plant', 'dept'], true)) {
            set_flash('danger', '組織代碼、名稱或類型格式不正確');
            redirect('org');
        }

        $stmt = $pdo->prepare("
            INSERT INTO sys_org_units 
            (parent_id, unit_code, unit_name, unit_type, country, address, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$parentId, $code, $name, $type, $country, $address]);
        $newId = (int)$pdo->lastInsertId();

        Audit::log('CREATE', 'sys_org_units', $newId, null, ['unit_code' => $code, 'unit_name' => $name]);

        set_flash('success', "組織單位「{$name}」新增成功！");
        redirect('org');
    }

    public function update() {
        Auth::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('org');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('org');
        }

        $id = (int)$_POST['id'];
        $pdo = Database::getConnection();

        $stmtOld = $pdo->prepare("SELECT * FROM sys_org_units WHERE id = ?");
        $stmtOld->execute([$id]);
        $old = $stmtOld->fetch();
        if (!$old) {
            set_flash('danger', '找不到指定組織');
            redirect('org');
        }

        $name = trim($_POST['unit_name']);
        $type = (string)($_POST['unit_type'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $address = trim($_POST['address'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || mb_strlen($name, 'UTF-8') > 100
            || !in_array($type, ['group', 'company', 'plant', 'dept'], true)
            || $parentId === $id) {
            set_flash('danger', '組織名稱、類型或上層組織設定不正確');
            redirect('org');
        }

        $stmt = $pdo->prepare("
            UPDATE sys_org_units 
            SET unit_name = ?, unit_type = ?, parent_id = ?, address = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $type, $parentId, $address, $isActive, $id]);

        Audit::log('UPDATE', 'sys_org_units', $id, $old, ['unit_name' => $name, 'is_active' => $isActive]);

        set_flash('success', "組織單位資料已更新！");
        redirect('org');
    }
}
