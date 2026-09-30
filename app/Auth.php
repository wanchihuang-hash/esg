<?php
// app/Auth.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Audit.php';

class Auth {
    public static function check(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string {
        return $_SESSION['user']['role_code'] ?? null;
    }

    public static function hasRole(array|string $roles): bool {
        if (!self::check()) return false;
        $currentRole = self::role();
        if (is_array($roles)) {
            return in_array($currentRole, $roles);
        }
        return $currentRole === $roles;
    }

    public static function can(string $permission): bool {
        $role = self::role();
        if (!$role) return false;
        if ($role === 'admin') return true;

        switch ($permission) {
            case 'edit_data':
                return in_array($role, ['admin', 'collector', 'reviewer', 'esg_lead'], true);
            case 'approve_data':
                return in_array($role, ['admin', 'reviewer', 'esg_lead']);
            case 'lock_data':
                return in_array($role, ['admin', 'esg_lead']);
            case 'export_reports':
                return true; // all authenticated can export/view reports
            case 'manage_system':
                return $role === 'admin';
            case 'manage_factors':
                return in_array($role, ['admin', 'esg_lead']);
            case 'manage_metrics':
                return in_array($role, ['admin', 'esg_lead'], true);
            default:
                return false;
        }
    }

    public static function requirePermission(string $permission): void {
        self::requireLogin();
        if (!self::can($permission)) {
            set_flash('danger', '您沒有執行此操作的權限');
            redirect('dashboard');
        }
    }

    /** Users with group-wide duties may access all units; local roles stay in their own unit. */
    public static function canAccessOrg(int $orgUnitId): bool {
        if (!self::check()) return false;
        if (self::hasRole(['admin', 'esg_lead', 'auditor'])) return true;
        return (int)(self::user()['org_unit_id'] ?? 0) === $orgUnitId;
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            set_flash('warning', '請先登入系統');
            redirect('login');
        }
    }

    public static function requireRole(array|string $roles): void {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            set_flash('danger', '您沒有存取該模組的權限');
            redirect('dashboard');
        }
    }

    public static function login(string $username, string $password): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_code, r.role_name, o.unit_name, o.unit_code
            FROM sys_users u
            JOIN sys_roles r ON u.role_id = r.id
            JOIN sys_org_units o ON u.org_unit_id = o.id
            WHERE u.username = :username
            LIMIT 1
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        if ($user['status'] == 0) {
            set_flash('danger', '此帳號已被停用，請洽系統管理員');
            return false;
        }

        require_once __DIR__ . '/Config.php';
        $maxFailed = (int)Config::get('sec_max_failed_logins', 5);

        if ($user['status'] == -1 || $user['failed_login_count'] >= $maxFailed) {
            set_flash('danger', '帳號因密碼錯誤次數過多被鎖定，請洽管理員解鎖');
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            // Increment failed login count
            $newCount = $user['failed_login_count'] + 1;
            $status = ($newCount >= $maxFailed) ? -1 : $user['status'];
            $update = $pdo->prepare("UPDATE sys_users SET failed_login_count = ?, status = ? WHERE id = ?");
            $update->execute([$newCount, $status, $user['id']]);
            return false;
        }

        // Login success - reset failed count, update last_login
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $update = $pdo->prepare("
            UPDATE sys_users 
            SET failed_login_count = 0, last_login_at = NOW(), last_login_ip = ?
            WHERE id = ?
        ");
        $update->execute([$ip, $user['id']]);

        // Store user in session
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['last_activity'] = time();
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role_id' => (int)$user['role_id'],
            'role_code' => $user['role_code'],
            'role_name' => $user['role_name'],
            'org_unit_id' => (int)$user['org_unit_id'],
            'unit_code' => $user['unit_code'],
            'unit_name' => $user['unit_name']
        ];

        // Audit log
        Audit::log('LOGIN', 'sys_users', $user['id'], null, ['username' => $username, 'status' => 'success']);

        return true;
    }

    public static function logout(): void {
        if (self::check()) {
            Audit::log('LOGOUT', 'sys_users', self::id(), ['username' => self::user()['username']], null);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
