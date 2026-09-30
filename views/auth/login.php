<?php
// views/auth/login.php
require_once __DIR__ . '/../../config/app.php';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1E4620 0%, #1B365D 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            max-width: 480px;
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>

<div class="login-card bg-white p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-2">
            <i class="bi bi-globe-americas fs-1"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1"><?= e(APP_NAME) ?></h4>
        <div class="text-muted small">Enterprise ESG Sustainability Management Platform</div>
        <div class="badge bg-success-subtle text-success border border-success-subtle mt-2 px-3 py-1">
            遵循 ISO 14064-1 & GRI Standards 2021
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show small" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-octagon-fill' ?> me-2"></i>
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?route=login">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary small">使用者帳號</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="請輸入帳號 (例如: admin)" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold text-secondary small">登入密碼</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control" placeholder="請輸入密碼" required>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold shadow-sm mb-3" style="background-color: var(--esg-green); border: none;">
            <i class="bi bi-box-arrow-in-right me-1"></i> 安全登入系統
        </button>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
