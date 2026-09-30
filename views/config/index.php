<?php
// views/config/index.php
require __DIR__ . '/../layout/header.php';

$categoryNames = [
    'ghg' => ['title' => '溫室氣體盤查與計算參數', 'icon' => 'bi-calculator-fill', 'badge' => 'ISO 14064 / GHG Protocol'],
    'energy' => ['title' => '能資源轉型與循環指標目標', 'icon' => 'bi-lightning-charge-fill', 'badge' => 'GRI 302 / GRI 303'],
    'workflow' => ['title' => '審查工作流程與單據檔案管制', 'icon' => 'bi-file-earmark-check-fill', 'badge' => '內控管制與文件安全'],
    'security' => ['title' => '系統資安防禦與帳號安全門檻', 'icon' => 'bi-shield-lock-fill', 'badge' => 'OWASP Top 10 安全基準'],
    'company' => ['title' => '企業法定邊界與永續揭露抬頭', 'icon' => 'bi-building-fill', 'badge' => '組織邊界界定']
];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-sliders text-danger me-2"></i>系統全域參數與可調整性配置 (Super Admin 專用)
        </h4>
        <div class="text-secondary small">
            具備高延展與彈性調整能力，支援企業配合國際標準（ISO 14064-1 / GRI / SBTi）動態校調計算基準、預警門檻與資安設定
        </div>
    </div>
    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
        <i class="bi bi-shield-fill-exclamation me-1"></i>僅限 Super Admin 系統管理員存取調整
    </span>
</div>

<form method="POST" action="index.php?route=configs_update">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <?php foreach ($categoryNames as $catKey => $catInfo): ?>
        <?php $items = $groupedConfigs[$catKey] ?? []; ?>
        <?php if (empty($items)) continue; ?>

        <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark fs-6">
                    <i class="bi <?= $catInfo['icon'] ?> text-success me-2"></i><?= $catInfo['title'] ?>
                </span>
                <span class="badge bg-light text-secondary border"><?= $catInfo['badge'] ?></span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <?php foreach ($items as $item): ?>
                        <div class="col-lg-6 col-12">
                            <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold text-dark mb-0"><?= e($item['config_name']) ?></label>
                                        <code class="small text-muted"><?= e($item['config_key']) ?></code>
                                    </div>

                                    <div class="text-secondary small mb-2" style="font-size: 0.84rem;">
                                        <i class="bi bi-info-circle text-primary me-1"></i><?= e($item['description']) ?>
                                    </div>

                                    <div class="input-group input-group-sm mb-2">
                                        <?php if ($item['config_key'] === 'company_consolidation_approach'): ?>
                                            <select name="configs[<?= e($item['config_key']) ?>]" class="form-select fw-semibold">
                                                <option value="營運控制權法 (Operational Control)" <?= $item['config_value'] === '營運控制權法 (Operational Control)' ? 'selected' : '' ?>>營運控制權法 (Operational Control)</option>
                                                <option value="股權比例法 (Equity Share)" <?= $item['config_value'] === '股權比例法 (Equity Share)' ? 'selected' : '' ?>>股權比例法 (Equity Share)</option>
                                                <option value="財務控制權法 (Financial Control)" <?= $item['config_value'] === '財務控制權法 (Financial Control)' ? 'selected' : '' ?>>財務控制權法 (Financial Control)</option>
                                            </select>
                                        <?php elseif ($item['config_key'] === 'ghg_default_gwp_version'): ?>
                                            <select name="configs[<?= e($item['config_key']) ?>]" class="form-select fw-semibold">
                                                <option value="AR6" <?= $item['config_value'] === 'AR6' ? 'selected' : '' ?>>IPCC AR6 (第六次評估報告 - 最新標準)</option>
                                                <option value="AR5" <?= $item['config_value'] === 'AR5' ? 'selected' : '' ?>>IPCC AR5 (第五次評估報告)</option>
                                                <option value="AR4" <?= $item['config_value'] === 'AR4' ? 'selected' : '' ?>>IPCC AR4 (第四次評估報告)</option>
                                            </select>
                                        <?php else: ?>
                                            <input type="<?= $item['value_type'] === 'number' ? 'number' : 'text' ?>" 
                                                   <?= $item['value_type'] === 'number' ? 'step="any"' : '' ?>
                                                   name="configs[<?= e($item['config_key']) ?>]" 
                                                   class="form-control fw-bold" 
                                                   value="<?= e($item['config_value']) ?>" required>
                                        <?php endif; ?>

                                        <?php if (!empty($item['unit'])): ?>
                                            <span class="input-group-text bg-white text-secondary fw-semibold"><?= e($item['unit']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($item['recommendation'])): ?>
                                    <div class="p-2 rounded bg-success-subtle border border-success-subtle small text-success-emphasis" style="font-size: 0.8rem;">
                                        <strong><i class="bi bi-lightbulb-fill text-warning me-1"></i>專家實務建議：</strong><?= e($item['recommendation']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Floating Action Footer -->
    <div class="card border-0 shadow-lg rounded-3 p-3 sticky-bottom bg-white" style="bottom: 15px;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-secondary small">
                <i class="bi bi-shield-check text-success me-1"></i>修改參數將立即寫入全生命週期稽核日誌 (Audit Trail) 並即時生效。
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?route=dashboard" class="btn btn-outline-secondary">取消返回</a>
                <button type="submit" class="btn btn-danger shadow-sm px-4 fw-semibold">
                    <i class="bi bi-save me-1"></i>儲存更新系統參數 (Save Configuration)
                </button>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
