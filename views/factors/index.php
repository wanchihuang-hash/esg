<?php
// views/factors/index.php
require __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-calculator text-success me-2"></i>溫室氣體碳排係數庫管理
        </h4>
        <div class="text-secondary small">
            內建環境部氣候變遷署 6.0.4 係數庫、經濟部能源署電力排碳係數與 IPCC AR6 標準
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#testCalcModal">
            <i class="bi bi-play-circle me-1"></i>係數試算模擬器
        </button>
        <?php if (Auth::can('manage_factors')): ?>
            <button type="button" class="btn btn-success btn-sm shadow-sm" style="background-color: var(--esg-green); border: none;" data-bs-toggle="modal" data-bs-target="#factorModal">
                <i class="bi bi-plus-circle me-1"></i>新增碳排係數
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Scope Filter Bar -->
<div class="card border-0 shadow-sm p-3 mb-4 rounded-3">
    <div class="d-flex align-items-center gap-2">
        <span class="text-secondary small fw-semibold">範疇篩選：</span>
        <a href="index.php?route=factors" class="btn btn-sm <?= empty($scope) ? 'btn-success' : 'btn-outline-secondary' ?>">全部 (<?= count($factors) ?>)</a>
        <a href="index.php?route=factors&scope=Scope1" class="btn btn-sm <?= $scope === 'Scope1' ? 'btn-danger' : 'btn-outline-danger' ?>">範疇一 (直接)</a>
        <a href="index.php?route=factors&scope=Scope2" class="btn btn-sm <?= $scope === 'Scope2' ? 'btn-primary' : 'btn-outline-primary' ?>">範疇二 (電力)</a>
        <a href="index.php?route=factors&scope=Scope3" class="btn btn-sm <?= $scope === 'Scope3' ? 'btn-success' : 'btn-outline-success' ?>">範疇三 (其他間接)</a>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>代碼</th>
                    <th>係數名稱</th>
                    <th>範疇</th>
                    <th>排放源類別</th>
                    <th>燃料/標的</th>
                    <th>係數值 (kg CO₂e/單位)</th>
                    <th>計量單位</th>
                    <th>公告來源</th>
                    <th>適用年份</th>
                    <th>預設</th>
                    <?php if (Auth::can('manage_factors')): ?>
                        <th class="text-center" style="width: 80px;">操作</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($factors as $f): ?>
                    <tr>
                        <td><code><?= e($f['factor_code']) ?></code></td>
                        <td><strong class="text-dark small"><?= e($f['factor_name']) ?></strong></td>
                        <td>
                            <?php if ($f['scope_type'] === 'Scope1'): ?>
                                <span class="badge badge-scope1">範疇一</span>
                            <?php elseif ($f['scope_type'] === 'Scope2'): ?>
                                <span class="badge badge-scope2">範疇二</span>
                            <?php else: ?>
                                <span class="badge badge-scope3">範疇三</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e($f['category']) ?></span></td>
                        <td><?= e($f['fuel_type'] ?: '-') ?></td>
                        <td><strong class="text-success"><?= (float)$f['co2e_factor'] ?></strong></td>
                        <td><?= e($f['unit']) ?></td>
                        <td class="small text-muted"><?= e($f['data_source']) ?></td>
                        <td><?= $f['effective_year'] ?></td>
                        <td>
                            <?= $f['is_current'] ? '<i class="bi bi-check-circle-fill text-success" title="有效啟用中"></i>' : '<span class="text-muted">否</span>' ?>
                        </td>
                        <?php if (Auth::can('manage_factors')): ?>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                        data-bs-toggle="modal" data-bs-target="#editFactorModal"
                                        data-id="<?= $f['id'] ?>"
                                        data-code="<?= e($f['factor_code']) ?>"
                                        data-name="<?= e($f['factor_name']) ?>"
                                        data-scope="<?= $f['scope_type'] ?>"
                                        data-cat="<?= e($f['category']) ?>"
                                        data-fuel="<?= e($f['fuel_type']) ?>"
                                        data-factor="<?= $f['co2e_factor'] ?>"
                                        data-unit="<?= e($f['unit']) ?>"
                                        data-source="<?= e($f['data_source']) ?>"
                                        data-year="<?= $f['effective_year'] ?>"
                                        data-current="<?= $f['is_current'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Test Calculation Modal -->
<div class="modal fade" id="testCalcModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-calculator text-primary me-2"></i>碳排公式核算模擬器</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small text-secondary">選取係數</label>
                    <select id="simFactorSelect" class="form-select">
                        <?php foreach ($factors as $f): ?>
                            <option value="<?= $f['id'] ?>" data-factor="<?= $f['co2e_factor'] ?>" data-unit="<?= e($f['unit']) ?>">
                                <?= e($f['factor_name']) ?> (<?= $f['co2e_factor'] ?> kg/<?= e($f['unit']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary">模擬活動量</label>
                    <div class="input-group">
                        <input type="number" step="0.01" id="simAmountInput" class="form-control" placeholder="輸入度數或公升數" value="10000">
                        <span class="input-group-text" id="simUnitLabel">度</span>
                    </div>
                </div>
                <div class="p-3 bg-light rounded border">
                    <div class="small text-secondary fw-semibold">核算結果 (公噸 CO₂e)：</div>
                    <div class="fs-2 fw-bold text-success" id="simResultTco2e">0.0000</div>
                    <div class="small text-muted mt-1" id="simFormulaText"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Factor Modal -->
<div class="modal fade" id="factorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="index.php?route=factors">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i>新增溫室氣體碳排係數</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">係數唯一代碼 (如 EF-SOLAR-01) <span class="text-danger">*</span></label>
                            <input type="text" name="factor_code" class="form-control" required placeholder="EF-...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">係數中文名稱 <span class="text-danger">*</span></label>
                            <input type="text" name="factor_name" class="form-control" required placeholder="如：2024年度新公告天然氣係數">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">範疇分類 <span class="text-danger">*</span></label>
                            <select name="scope_type" class="form-select" required>
                                <option value="Scope1">範疇一 (直接排放)</option>
                                <option value="Scope2">範疇二 (能源間接)</option>
                                <option value="Scope3">範疇三 (其他間接)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">排放源類別</label>
                            <input type="text" name="category" class="form-control" placeholder="如：固定燃燒 / 外購電力 / 廢棄物" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">燃料/能源標的</label>
                            <input type="text" name="fuel_type" class="form-control" placeholder="如：天然氣 / 柴油 / 綠電">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">排放係數值 (kg CO₂e/單位) <span class="text-danger">*</span></label>
                            <input type="number" step="0.000001" name="co2e_factor" class="form-control" required placeholder="0.000000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">計量單位 <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" required placeholder="如：度 / 公升 / 公斤 / 立方公尺">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">生效適用年份</label>
                            <input type="number" name="effective_year" class="form-control" value="2024" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">公告法規依據 / 數據來源 <span class="text-danger">*</span></label>
                        <input type="text" name="data_source" class="form-control" required placeholder="如：環境部氣候變遷署 6.0.4 版 / 能源署 2024 公告">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_current" value="1" id="isCurrentCheck" checked>
                        <label class="form-check-label small" for="isCurrentCheck">
                            設為目前最新預設係數
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success" style="background-color: var(--esg-green); border: none;">儲存係數</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Factor Modal -->
<div class="modal fade" id="editFactorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="index.php?route=factors">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="editFactorId">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>編輯排放係數</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">係數代碼</label>
                            <input type="text" id="editFactorCode" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">係數名稱 <span class="text-danger">*</span></label>
                            <input type="text" name="factor_name" id="editFactorName" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">範疇分類</label>
                            <select name="scope_type" id="editFactorScope" class="form-select" required>
                                <option value="Scope1">範疇一 (直接)</option>
                                <option value="Scope2">範疇二 (電力)</option>
                                <option value="Scope3">範疇三 (其他間接)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">排放源類別</label>
                            <input type="text" name="category" id="editFactorCategory" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">燃料標的</label>
                            <input type="text" name="fuel_type" id="editFactorFuel" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">係數值 (kg CO₂e/單位) <span class="text-danger">*</span></label>
                            <input type="number" step="0.000001" name="co2e_factor" id="editFactorVal" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">計量單位 <span class="text-danger">*</span></label>
                            <input type="text" name="unit" id="editFactorUnit" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">生效適用年份</label>
                            <input type="number" name="effective_year" id="editFactorYear" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">公告法規依據 / 數據來源</label>
                        <input type="text" name="data_source" id="editFactorSource" class="form-control" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_current" value="1" id="editFactorCurrent">
                        <label class="form-check-label small" for="editFactorCurrent">
                            設為目前最新預設係數
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">更新係數</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simulator
    const simSelect = document.getElementById('simFactorSelect');
    const simAmount = document.getElementById('simAmountInput');
    const simUnit = document.getElementById('simUnitLabel');
    const simResult = document.getElementById('simResultTco2e');
    const simFormula = document.getElementById('simFormulaText');

    function updateSim() {
        const opt = simSelect.options[simSelect.selectedIndex];
        if (!opt) return;
        const factor = parseFloat(opt.getAttribute('data-factor')) || 0;
        const unit = opt.getAttribute('data-unit') || '';
        const amt = parseFloat(simAmount.value) || 0;
        simUnit.textContent = unit;
        const tco2e = (amt * factor) / 1000.0;
        simResult.textContent = tco2e.toFixed(4);
        simFormula.textContent = `${amt.toLocaleString()} ${unit} × ${factor} kgCO₂e/${unit} ÷ 1,000 = ${tco2e.toFixed(4)} tCO₂e`;
    }

    simSelect.addEventListener('change', updateSim);
    simAmount.addEventListener('input', updateSim);
    updateSim();

    // Edit modal population
    const editModal = document.getElementById('editFactorModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            document.getElementById('editFactorId').value = btn.getAttribute('data-id');
            document.getElementById('editFactorCode').value = btn.getAttribute('data-code');
            document.getElementById('editFactorName').value = btn.getAttribute('data-name');
            document.getElementById('editFactorScope').value = btn.getAttribute('data-scope');
            document.getElementById('editFactorCategory').value = btn.getAttribute('data-cat');
            document.getElementById('editFactorFuel').value = btn.getAttribute('data-fuel');
            document.getElementById('editFactorVal').value = btn.getAttribute('data-factor');
            document.getElementById('editFactorUnit').value = btn.getAttribute('data-unit');
            document.getElementById('editFactorSource').value = btn.getAttribute('data-source');
            document.getElementById('editFactorYear').value = btn.getAttribute('data-year');
            document.getElementById('editFactorCurrent').checked = (btn.getAttribute('data-current') === '1');
        });
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
