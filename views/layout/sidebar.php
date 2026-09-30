<?php
// views/layout/sidebar.php
require_once __DIR__ . '/../../app/Auth.php';
$role = Auth::role();
$route = $_GET['route'] ?? 'dashboard';
?>
<div id="sidebar" class="d-flex flex-column flex-shrink-0">
    <div class="brand-title d-flex align-items-center">
        <i class="bi bi-globe-americas fs-4 text-warning me-2"></i>
        <div>
            <div class="fw-bold lh-1 text-white">ESG-SMP 永續平台</div>
            <div class="small text-white-50 mt-1" style="font-size: 0.72rem;">ISO 14064 / GRI Standards</div>
        </div>
    </div>

    <ul class="nav nav-pills flex-column mb-auto mt-2">
        <!-- 戰情室儀表板 -->
        <li class="nav-item">
            <a href="index.php?route=dashboard" class="nav-link <?= $route === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> ESG 戰情室
            </a>
        </li>

        <!-- 環境面向 (E) -->
        <li class="sidebar-heading">環境永續 (Environmental)</li>
        <li>
            <a href="index.php?route=ghg" class="nav-link <?= in_array($route, ['ghg', 'ghg_create', 'ghg_edit']) ? 'active' : '' ?>">
                <i class="bi bi-cloud-haze2"></i> 碳盤查活動清冊
            </a>
        </li>
        <li>
            <a href="index.php?route=energy_water" class="nav-link <?= in_array($route, ['energy_water', 'energy_water_create', 'energy_water_edit']) ? 'active' : '' ?>">
                <i class="bi bi-lightning-charge"></i> 能資源與水消耗
            </a>
        </li>

        <!-- 社會責任 (S) -->
        <li class="sidebar-heading">社會責任 (Social)</li>
        <li>
            <a href="index.php?route=social" class="nav-link <?= $route === 'social' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> DEI 與安衛指標 (FR/SR)
            </a>
        </li>

        <!-- 公司治理 (G) -->
        <li class="sidebar-heading">公司治理 (Governance)</li>
        <li>
            <a href="index.php?route=governance" class="nav-link <?= $route === 'governance' ? 'active' : '' ?>">
                <i class="bi bi-shield-check"></i> 董事治理與 TCFD 風險
            </a>
        </li>

        <!-- 報告書與揭露 -->
        <li class="sidebar-heading">永續揭露與查證</li>
        <li>
            <a href="index.php?route=report_gri" class="nav-link <?= $route === 'report_gri' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i> GRI 準則內容索引
            </a>
        </li>
        <li>
            <a href="index.php?route=report_export&year=2024" class="nav-link">
                <i class="bi bi-filetype-csv"></i> 匯出 GHG 清冊 (CSV)
            </a>
        </li>

        <!-- 系統管理 -->
        <li class="sidebar-heading">系統設定與維護</li>
        <li>
            <a href="index.php?route=factors" class="nav-link <?= $route === 'factors' ? 'active' : '' ?>">
                <i class="bi bi-calculator"></i> 排放係數庫管理
            </a>
        </li>

        <?php if ($role === 'admin'): ?>
            <li>
                <a href="index.php?route=org" class="nav-link <?= $route === 'org' ? 'active' : '' ?>">
                    <i class="bi bi-diagram-3"></i> 組織廠區架構
                </a>
            </li>
            <li>
                <a href="index.php?route=users" class="nav-link <?= $route === 'users' ? 'active' : '' ?>">
                    <i class="bi bi-person-badge"></i> 使用者帳號權限
                </a>
            </li>
            <li>
                <a href="index.php?route=configs" class="nav-link <?= $route === 'configs' ? 'active' : '' ?>">
                    <i class="bi bi-sliders"></i> 系統參數設定 (Super Admin)
                </a>
            </li>
            <li>
                <a href="index.php?route=ai_settings" class="nav-link <?= $route === 'ai_settings' ? 'active' : '' ?>">
                    <i class="bi bi-robot"></i> AI 客服設定
                </a>
            </li>
        <?php endif; ?>

        <?php if (Auth::hasRole(['admin', 'esg_lead', 'auditor'])): ?>
            <li>
                <a href="index.php?route=audit" class="nav-link <?= $route === 'audit' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> 全生命週期稽核日誌
                </a>
            </li>
        <?php endif; ?>

        <li>
            <a href="index.php?route=ai_assistant" class="nav-link <?= $route === 'ai_assistant' ? 'active' : '' ?>">
                <i class="bi bi-robot"></i> AI 客服小編
            </a>
        </li>

        <!-- 操作手冊（固定為左側選單最後一項） -->
        <li class="mt-2">
            <a href="index.php?route=manual" class="nav-link <?= $route === 'manual' ? 'active' : '' ?>">
                <i class="bi bi-book"></i> 系統操作手冊
            </a>
        </li>
    </ul>

    <!-- Bottom Footer info in sidebar -->
    <div class="p-3 border-top border-secondary text-white-50 small">
        <div><i class="bi bi-shield-lock me-1"></i>系統版本: <?= e(APP_VERSION) ?></div>
        <div style="font-size: 0.72rem;" class="mt-1">XAMPP / PHP <?= PHP_VERSION ?></div>
    </div>
</div>
