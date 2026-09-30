<?php
// views/energy_water/form.php
require __DIR__ . '/../layout/header.php';
$isEdit = !empty($record['id']);
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1 text-dark">
                    <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle-fill' ?> text-success me-2"></i>
                    <?= $isEdit ? '編輯能資源消耗月報' : '填報能資源消耗月報' ?>
                </h4>
                <div class="text-secondary small">
                    輸入各廠區電力、水資源及燃料之月度實際消耗量
                </div>
            </div>
            <a href="index.php?route=energy_water" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3 p-4">
            <form method="POST" action="index.php?route=energy_water_store">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= $record['id'] ?>">
                <?php endif; ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary small">廠區據點 <span class="text-danger">*</span></label>
                        <select name="org_unit_id" class="form-select" required>
                            <?php foreach ($orgs as $o): ?>
                                <option value="<?= $o['id'] ?>" <?= ($record['org_unit_id'] ?? 3) == $o['id'] ? 'selected' : '' ?>>
                                    <?= e($o['unit_name']) ?> (<?= e($o['unit_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label fw-semibold text-secondary small">統計年度 <span class="text-danger">*</span></label>
                        <select name="period_year" class="form-select" required>
                            <option value="2024" <?= ($record['period_year'] ?? 2024) == 2024 ? 'selected' : '' ?>>2024</option>
                            <option value="2023" <?= ($record['period_year'] ?? '') == 2023 ? 'selected' : '' ?>>2023</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label fw-semibold text-secondary small">盤查月份 <span class="text-danger">*</span></label>
                        <select name="period_month" class="form-select" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($record['period_month'] ?? date('n')) == $m ? 'selected' : '' ?>><?= $m ?> 月</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-lightning-charge text-warning me-2"></i>電力能耗 (GRI 302)
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small">外購市電度數 (kWh)</label>
                        <input type="number" step="0.01" min="0" name="elec_grid_kwh" id="inputGridKwh" class="form-control" 
                               value="<?= $record['elec_grid_kwh'] ?? '0' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small">綠電轉供 / 太陽能發電度數 (kWh)</label>
                        <input type="number" step="0.01" min="0" name="elec_green_kwh" id="inputGreenKwh" class="form-control" 
                               value="<?= $record['elec_green_kwh'] ?? '0' ?>">
                    </div>
                </div>

                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-droplet-half text-primary me-2"></i>水資源利用 (GRI 303)
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small">自來水取用度數 (m³)</label>
                        <input type="number" step="0.01" min="0" name="water_tap_m3" id="inputTapWater" class="form-control" 
                               value="<?= $record['water_tap_m3'] ?? '0' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small">製程水循環回收量 (m³)</label>
                        <input type="number" step="0.01" min="0" name="water_recycle_m3" id="inputRecycleWater" class="form-control" 
                               value="<?= $record['water_recycle_m3'] ?? '0' ?>">
                    </div>
                </div>

                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-fire text-danger me-2"></i>化石燃料用量
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label text-secondary small">天然氣 (m³)</label>
                        <input type="number" step="0.01" min="0" name="natural_gas_m3" class="form-control" 
                               value="<?= $record['natural_gas_m3'] ?? '0' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small">柴油 (公升)</label>
                        <input type="number" step="0.01" min="0" name="diesel_liters" class="form-control" 
                               value="<?= $record['diesel_liters'] ?? '0' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-secondary small">汽油 (公升)</label>
                        <input type="number" step="0.01" min="0" name="gasoline_liters" class="form-control" 
                               value="<?= $record['gasoline_liters'] ?? '0' ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="index.php?route=energy_water" class="btn btn-outline-secondary">取消</a>
                    <button type="submit" class="btn btn-success shadow-sm" style="background-color: var(--esg-green); border: none;">
                        <i class="bi bi-check2-circle me-1"></i>儲存數據
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
