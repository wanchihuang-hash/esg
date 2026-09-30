<?php
// views/ghg/form.php
require __DIR__ . '/../layout/header.php';

$isEdit = !empty($emission['id']);
$formAction = $isEdit ? 'index.php?route=ghg_update' : 'index.php?route=ghg_store';
?>

<div class="row justify-content-center">
    <div class="col-lg-9 col-md-11">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1 text-dark">
                    <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle-fill' ?> text-success me-2"></i>
                    <?= $isEdit ? '編輯溫室氣體活動數據' : '填報溫室氣體活動數據' ?>
                </h4>
                <div class="text-secondary small">
                    填報各排放源之實際活動數據，系統將依據公告係數即時核算公噸二氧化碳當量 (tCO₂e)
                </div>
            </div>
            <a href="index.php?route=ghg" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>返回清冊
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3 p-4">
            <form method="POST" action="<?= $formAction ?>" enctype="multipart/form-data" id="ghgForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= $emission['id'] ?>">
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <!-- Org Unit -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small">填報組織 / 廠區 <span class="text-danger">*</span></label>
                        <select name="org_unit_id" id="orgUnitSelect" class="form-select" required>
                            <?php foreach ($orgs as $o): ?>
                                <option value="<?= $o['id'] ?>" <?= ($emission['org_unit_id'] ?? 3) == $o['id'] ? 'selected' : '' ?>>
                                    <?= e($o['unit_name']) ?> (<?= e($o['unit_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Year & Month -->
                    <div class="col-md-3 col-6">
                        <label class="form-label fw-semibold text-secondary small">盤查年度 <span class="text-danger">*</span></label>
                        <select name="period_year" class="form-select" required>
                            <option value="2024" <?= ($emission['period_year'] ?? 2024) == 2024 ? 'selected' : '' ?>>2024</option>
                            <option value="2023" <?= ($emission['period_year'] ?? '') == 2023 ? 'selected' : '' ?>>2023</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label fw-semibold text-secondary small">盤查月份 <span class="text-danger">*</span></label>
                        <select name="period_month" class="form-select" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($emission['period_month'] ?? date('n')) == $m ? 'selected' : '' ?>>
                                    <?= $m ?> 月
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Emission Factor Selection -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">選擇溫室氣體排放係數 (引用標準庫) <span class="text-danger">*</span></label>
                    <select name="factor_id" id="factorSelect" class="form-select" required>
                        <option value="">-- 請選擇排放係數項目 --</option>
                        <?php foreach ($factors as $f): ?>
                            <option value="<?= $f['id'] ?>" 
                                    data-scope="<?= $f['scope_type'] ?>"
                                    data-factor="<?= $f['co2e_factor'] ?>" 
                                    data-unit="<?= e($f['unit']) ?>"
                                    data-source="<?= e($f['data_source']) ?>"
                                    <?= ($emission['factor_id'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                                [<?= $f['scope_type'] ?>] <?= e($f['factor_name']) ?> (<?= $f['co2e_factor'] ?> kg CO₂e / <?= e($f['unit']) ?>) - <?= e($f['data_source']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Emission Source Description -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small">具體排放源名稱或設備描述 <span class="text-danger">*</span></label>
                    <input type="text" name="emission_source" class="form-control" placeholder="例如：桃園一廠 1號主變電站外購用電 / 緊急柴油發電機 / 廠區公務車輛" 
                           value="<?= e($emission['emission_source'] ?? '') ?>" required>
                </div>

                <!-- Activity Amount & Unit -->
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold text-secondary small">活動數據輸入量 <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" min="0" name="activity_amount" id="activityAmountInput" 
                               class="form-control form-control-lg fw-bold" placeholder="0.00" 
                               value="<?= e($emission['activity_amount'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-secondary small">計量單位</label>
                        <input type="text" id="unitDisplay" class="form-control form-control-lg bg-light" readonly value="<?= e($emission['activity_unit'] ?? '度/公升/公斤') ?>">
                    </div>
                </div>

                <!-- Live Calculation Preview Card -->
                <div class="p-3 mb-3 rounded-3 border bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-secondary small fw-semibold">
                            <i class="bi bi-cpu-fill text-success me-1"></i>即時碳排核算預覽 (ISO 14064-1 公式)
                        </div>
                        <span id="scopeBadge" class="badge bg-secondary">請選取係數</span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-bold text-success" id="calcTco2e">0.0000</span>
                        <span class="text-muted fw-semibold">公噸 CO₂e (tCO₂e)</span>
                    </div>
                    <div class="small text-muted mt-1" id="calcFormula">
                        公式：活動數據量 (A) × 排放係數 (EF) ÷ 1,000 = 核算排放當量 (tCO₂e)
                    </div>
                </div>

                <!-- Invoice & Proof File -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small">發票號碼 / 收據號 / 台電電號</label>
                        <input type="text" name="invoice_no" class="form-control" placeholder="例如：TPC-2024-09876543 / AB-12345678" 
                               value="<?= e($emission['invoice_no'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small">佐證單據掃描檔上傳 (PDF, 圖片, Excel)</label>
                        <input type="file" name="proof_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.csv">
                        <?php if (!empty($emission['proof_file_path'])): ?>
                            <div class="small mt-1">
                                目前檔案：<a href="<?= e($emission['proof_file_path']) ?>" target="_blank" class="text-primary"><i class="bi bi-file-earmark-check me-1"></i>檢視現有憑單</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="index.php?route=ghg" class="btn btn-outline-secondary">取消</a>
                    <div class="d-flex gap-2">
                        <button type="submit" name="submit_action" value="draft" class="btn btn-light border">
                            <i class="bi bi-save me-1"></i>儲存為草稿 (Draft)
                        </button>
                        <button type="submit" name="submit_action" value="submit" class="btn btn-success shadow-sm" style="background-color: var(--esg-green); border: none;">
                            <i class="bi bi-send-check-fill me-1"></i>送出並提交審核 (Submit)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const factorSelect = document.getElementById('factorSelect');
    const amountInput = document.getElementById('activityAmountInput');
    const unitDisplay = document.getElementById('unitDisplay');
    const calcTco2e = document.getElementById('calcTco2e');
    const calcFormula = document.getElementById('calcFormula');
    const scopeBadge = document.getElementById('scopeBadge');

    function updateCalculation() {
        const selectedOption = factorSelect.options[factorSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            calcTco2e.textContent = '0.0000';
            unitDisplay.value = '請選取係數';
            scopeBadge.textContent = '未選取';
            scopeBadge.className = 'badge bg-secondary';
            calcFormula.textContent = '公式：活動數據量 (A) × 排放係數 (EF) ÷ 1,000 = 核算排放當量 (tCO₂e)';
            return;
        }

        const factorVal = parseFloat(selectedOption.getAttribute('data-factor')) || 0;
        const unit = selectedOption.getAttribute('data-unit') || '';
        const scope = selectedOption.getAttribute('data-scope') || '';
        const amount = parseFloat(amountInput.value) || 0;

        unitDisplay.value = unit;
        scopeBadge.textContent = scope;
        if (scope === 'Scope1') scopeBadge.className = 'badge badge-scope1';
        else if (scope === 'Scope2') scopeBadge.className = 'badge badge-scope2';
        else scopeBadge.className = 'badge badge-scope3';

        const tco2e = (amount * factorVal) / 1000.0;
        calcTco2e.textContent = tco2e.toFixed(4);
        calcFormula.textContent = `計算歷程：${amount.toLocaleString()} ${unit} × ${factorVal} kgCO₂e/${unit} ÷ 1,000 = ${tco2e.toFixed(4)} tCO₂e`;
    }

    factorSelect.addEventListener('change', updateCalculation);
    amountInput.addEventListener('input', updateCalculation);

    // Initial trigger
    updateCalculation();
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
