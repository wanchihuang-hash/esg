<?php
// views/layout/header.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Auth.php';

$user = Auth::user();
$currentRoute = $_GET['route'] ?? 'dashboard';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - <?= e(APP_SUB_NAME) ?></title>
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Chart.js 4.4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- Custom ESG Theme CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <?php if ($currentRoute === 'manual'): ?><link href="assets/css/manual.css" rel="stylesheet"><?php endif; ?>
    <?php if (in_array($currentRoute, ['ai_assistant', 'ai_settings'], true)): ?><link href="assets/css/ai.css" rel="stylesheet"><?php endif; ?>
    <?php if (Auth::check() && $currentRoute !== 'ai_assistant' && $currentRoute !== 'ai_settings'): ?><link href="assets/css/ai-widget.css" rel="stylesheet"><?php endif; ?>
</head>
<body>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Container -->
    <div id="content" class="d-flex flex-column">
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary btn-sm me-3 d-md-none" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="text-secondary small d-none d-sm-block">
                    <i class="bi bi-geo-alt-fill text-success me-1"></i>
                    所屬單位：<strong class="text-dark"><?= e($user['unit_name'] ?? '總部') ?></strong> (<?= e($user['unit_code'] ?? 'HQ') ?>)
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Current User Badge -->
                <div class="d-flex align-items-center bg-light px-3 py-1 rounded-pill border">
                    <i class="bi bi-person-circle fs-5 text-success me-2"></i>
                    <div>
                        <span class="fw-semibold text-dark small"><?= e($user['full_name'] ?? '使用者') ?></span>
                        <span class="badge bg-secondary ms-1 small" style="font-size: 0.7rem;"><?= e($user['role_name'] ?? '訪客') ?></span>
                    </div>
                </div>

                <!-- Logout -->
                <form method="POST" action="index.php?route=logout" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm" title="登出系統"><i class="bi bi-box-arrow-right"></i></button>
                </form>
            </div>
        </nav>

        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="container-fluid px-4 pt-3">
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'danger' ? 'bi-exclamation-octagon-fill' : 'bi-info-circle-fill') ?> me-2"></i>
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content Area -->
        <main class="container-fluid px-4 py-4 flex-grow-1">
