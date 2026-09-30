<?php
// app/Controllers/UserController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Audit.php';
require_once __DIR__ . '/../Security.php';

class UserController {
    public function index() {
        Auth::requireRole('admin');
        $pdo = Database::getConnection();

        $users = $pdo->query("
            SELECT u.*, r.role_code, r.role_name, o.unit_name, o.unit_code
            FROM sys_users u
            JOIN sys_roles r ON u.role_id = r.id
            JOIN sys_org_units o ON u.org_unit_id = o.id
            ORDER BY u.id ASC
        ")->fetchAll();

        $roles = $pdo->query("SELECT * FROM sys_roles ORDER BY id ASC")->fetchAll();
        $orgs = $pdo->query("SELECT * FROM sys_org_units WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

        require __DIR__ . '/../../views/users/index.php';
    }

    public function store() {
        Auth::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('users');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('users');
        }

        $pdo = Database::getConnection();
        $username = trim((string)($_POST['username'] ?? ''));
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $orgId = (int)$_POST['org_unit_id'];
        $roleId = (int)$_POST['role_id'];
        $password = (string)($_POST['password'] ?? '');

        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)
            || $fullName === '' || mb_strlen($fullName, 'UTF-8') > 100
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 10
            || !preg_match('/[A-Za-z]/', $password)
            || !preg_match('/\d/', $password)) {
            set_flash('danger', '帳號、姓名、電子信箱或密碼格式不正確；密碼至少 10 碼且需包含英文字母與數字');
            redirect('users');
        }
        $refStmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM sys_org_units WHERE id = ? AND is_active = 1) AS org_ok, (SELECT COUNT(*) FROM sys_roles WHERE id = ?) AS role_ok");
        $refStmt->execute([$orgId, $roleId]);
        $refs = $refStmt->fetch();
        if (!(int)$refs['org_ok'] || !(int)$refs['role_ok']) {
            set_flash('danger', '指定的組織或角色不存在');
            redirect('users');
        }

        // Check duplicate
        $chk = $pdo->prepare("SELECT id FROM sys_users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetch()) {
            set_flash('danger', "帳號「{$username}」已存在");
            redirect('users');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO sys_users 
            (org_unit_id, role_id, username, password_hash, full_name, email, phone, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$orgId, $roleId, $username, $hash, $fullName, $email, $phone]);
        $newId = (int)$pdo->lastInsertId();

        Audit::log('CREATE', 'sys_users', $newId, null, ['username' => $username, 'role_id' => $roleId]);

        set_flash('success', "使用者「{$fullName} ({$username})」新增成功！初始密碼：{$password}");
        redirect('users');
    }

    public function update() {
        Auth::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('users');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('users');
        }

        $id = (int)$_POST['id'];
        $pdo = Database::getConnection();

        $stmtOld = $pdo->prepare("SELECT * FROM sys_users WHERE id = ?");
        $stmtOld->execute([$id]);
        $old = $stmtOld->fetch();
        if (!$old) {
            set_flash('danger', '找不到指定帳號');
            redirect('users');
        }

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $orgId = (int)$_POST['org_unit_id'];
        $roleId = (int)$_POST['role_id'];
        $status = (int)$_POST['status'];

        if ($fullName === '' || mb_strlen($fullName, 'UTF-8') > 100
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($status, [-1, 0, 1], true)) {
            set_flash('danger', '使用者資料格式不正確');
            redirect('users');
        }
        if (!empty($_POST['password'])) {
            $newPassword = (string)$_POST['password'];
            if (strlen($newPassword) < 10 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
                set_flash('danger', '新密碼至少 10 碼且需包含英文字母與數字');
                redirect('users');
            }
        }

        $sql = "UPDATE sys_users SET full_name = ?, email = ?, phone = ?, org_unit_id = ?, role_id = ?, status = ?";
        $params = [$fullName, $email, $phone, $orgId, $roleId, $status];

        if (!empty($_POST['password'])) {
            $sql .= ", password_hash = ?";
            $params[] = password_hash((string)$_POST['password'], PASSWORD_BCRYPT);
        }

        // If status changed to 1, reset failed_login_count
        if ($status == 1) {
            $sql .= ", failed_login_count = 0";
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        Audit::log('UPDATE', 'sys_users', $id, $old, ['full_name' => $fullName, 'status' => $status, 'role_id' => $roleId]);

        set_flash('success', "帳號「{$old['username']}」資料已更新！");
        redirect('users');
    }
}
