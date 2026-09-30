<?php
// views/energy_water/index.php
require __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-lightning-charge text-warning me-2"></i>水電能資源月度消耗管理
        </h4>
        <div class="text-secondary small">
            涵蓋 GRI 302 (能源消耗) 與 GRI 303 (水資源與循環利用) 揭露指標
        </div>
    </div>
    <?php if (Auth::can('edit_data')): ?>
        <a href="index.php?route=energy_water_create" class="btn btn-success btn-sm shadow-sm" style="background-color: var(--esg-green); border: none;">
            <i class="bi bi-plus-circle me-1"></i>新增能資源月報
        </a>
    <?php endif; ?>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">全廠市電外購總量</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($totals['grid_kwh']) ?> <span class="fs-6 fw-normal text-muted">度</span></div>
            <div class="text-muted small">電網傳統電力</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">綠電轉供 / 自發自用</div>
            <div class="fs-4 fw-bold text-success mt-1"><?= number_format($totals['green_kwh']) ?> <span class="fs-6 fw-normal text-muted">度</span></div>
            <div class="text-success small fw-semibold">綠電佔比：<?= $greenRate ?>%</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">自來水取用量</div>
            <div class="fs-4 fw-bold text-primary mt-1"><?= number_format($totals['water_tap']) ?> <span class="fs-6 fw-normal text-muted">度(m³)</span></div>
            <div class="text-muted small">自來水公司計費度數</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">製程水循環回收量</div>
            <div class="fs-4 fw-bold text-info mt-1"><?= number_format($totals['water_recycle']) ?> <span class="fs-6 fw-normal text-muted">度(m³)</span></div>
            <div class="text-info small fw-semibold">循環水利用率：<?= $recycleRate ?>%</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm p-3 mb-4 rounded-3">
    <form method="GET" action="index.php" class="row g-2 align-items-center">
        <input type="hidden" name="route" value="energy_water">
        
        <div class="col-md-3 col-6">
            <label class="form-label small text-secondary mb-1">統計年度</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="2024" <?= $year == 2024 ? 'selected' : '' ?>>2024</option>
                <option value="2023" <?= $year == 2023 ? 'selected' : '' ?>>2023</option>
            </select>
        </div>

        <div class="col-md-4 col-6">
            <label class="form-label small text-secondary mb-1">組織廠區</label>
            <select name="org_unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">全部廠區</option>
                <?php foreach ($orgs as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $orgUnitId == $o['id'] ? 'selected' : '' ?>><?= e($o['unit_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>廠區據點</th>
                    <th>盤查月份</th>
                    <th>市電用量 (度)</th>
                    <th>綠電用量 (度)</th>
                    <th>綠電比率</th>
                    <th>自來水 (m³)</th>
                    <th>循環回收水 (m³)</th>
                    <th>天然氣 (m³)</th>
                    <th>柴油 (L)</th>
                    <th>汽油 (L)</th>
                    <th class="text-center" style="width: 100px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="11" class="text-center py-4 text-muted">目前暫無能資源月度統計數據</td></tr>
                <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <?php
                            $canEditRecord = $r['workflow_status'] !== 'locked' && (
                                Auth::hasRole(['admin', 'esg_lead'])
                                || (Auth::hasRole('collector') && (int)$r['created_by'] === Auth::id() && $r['workflow_status'] === 'draft')
                            );
                            ?>
                        <?php
                        $mElec = (float)$r['elec_grid_kwh'] + (float)$r['elec_green_kwh'];
                        $mGreenPct = $mElec > 0 ? round(((float)$r['elec_green_kwh'] / $mElec) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td>
                                <strong class="text-dark small"><?= e($r['unit_name']) ?></strong>
                            </td>
                            <td class="fw-bold"><?= $r['period_year'] ?>/<?= str_pad($r['period_month'], 2, '0', STR_PAD_LEFT) ?></td>
                            <td><?= number_format($r['elec_grid_kwh']) ?></td>
                            <td class="text-success"><?= number_format($r['elec_green_kwh']) ?></td>
                            <td>
                                <span class="badge <?= $mGreenPct > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted' ?>">
                                    <?= $mGreenPct ?>%
                                </span>
                            </td>
                            <td><?= number_format($r['water_tap_m3']) ?></td>
                            <td class="text-info"><?= number_format($r['water_recycle_m3']) ?></td>
                            <td><?= number_format($r['natural_gas_m3']) ?></td>
                            <td><?= number_format($r['diesel_liters']) ?></td>
                            <td><?= number_format($r['gasoline_liters']) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($canEditRecord): ?>
                                        <a href="index.php?route=energy_water_edit&id=<?= $r['id'] ?>" class="btn btn-outline-secondary" title="編輯">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (Auth::hasRole('admin') && $r['workflow_status'] !== 'locked'): ?>
                                        <form method="POST" action="index.php?route=energy_water_delete" class="d-inline" onsubmit="return confirm('確定刪除此記錄？')">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="刪除">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
