<?php
// config/app.php

// Harden session cookies before the session is created.
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Inactivity timeout. The database setting is mirrored here so it can be
// enforced before any database-dependent class is loaded.
$sessionTimeout = max(300, (int)(getenv('ESG_SESSION_TIMEOUT_SECONDS') ?: 1800));
if (!empty($_SESSION['user_id']) && !empty($_SESSION['last_activity'])
    && (time() - (int)$_SESSION['last_activity']) > $sessionTimeout) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    $_SESSION['flash'] = ['type' => 'warning', 'message' => '工作階段已逾時，請重新登入'];
}
$_SESSION['last_activity'] = time();

// Baseline response headers against clickjacking, MIME sniffing and common XSS.
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net data:; img-src 'self' data: blob:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Generate CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Base App Settings
define('APP_NAME', '企業 ESG 永續管理資訊系統');
define('APP_SUB_NAME', 'Enterprise ESG Sustainability Management Platform (ESG-SMP)');
define('APP_VERSION', 'v1.0');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('APP_ENV', getenv('ESG_APP_ENV') ?: 'production');

// Helper Functions
function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function redirect($route, $params = []) {
    $url = 'index.php?route=' . urlencode($route);
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }
    header("Location: {$url}");
    exit;
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
