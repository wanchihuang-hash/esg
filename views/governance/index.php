<?php
// views/governance/index.php
require __DIR__ . '/../layout/header.php';

$seats = (int)($current['board_seats_total'] ?? 9);
$indep = (int)($current['independent_directors'] ?? 4);
$female = (int)($current['female_directors'] ?? 3);
$indepRatio = $seats > 0 ? round(($indep / $seats) * 100, 1) : 0;
$femaleRatio = $seats > 0 ? round(($female / $seats) * 100, 1) : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-shield-check text-navy me-2" style="color: var(--esg-navy);"></i>公司治理與誠信運作 (Governance & TCFD)
        </h4>
        <div class="text-secondary small">
            涵蓋 GRI 2 (一般揭露/治理架構)、GRI 205 (反貪腐誠信經營) 與 TCFD 氣候風險矩陣
        </div>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
            <input type="hidden" name="route" value="governance">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="2024" <?= $year == 2024 ? 'selected' : '' ?>>2024 年度</option>
                <option value="2023" <?= $year == 2023 ? 'selected' : '' ?>>2023 年度</option>
            </select>
        </form>
        <?php if (Auth::can('manage_metrics')): ?>
            <button type="button" class="btn btn-success btn-sm shadow-sm" style="background-color: var(--esg-green); border: none;" data-bs-toggle="modal" data-bs-target="#govModal">
                <i class="bi bi-pencil-square me-1"></i>編輯治理指標
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Board Independence -->
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">獨立董事席次與比例</div>
            <div class="fs-4 fw-bold text-dark mt-1">
                <?= $indep ?> / <?= $seats ?> <span class="fs-6 fw-normal text-muted">席</span>
            </div>
            <div class="text-success small fw-semibold">
                獨董比例: <?= $indepRatio ?>% (高於金管會 1/3 標準)
            </div>
        </div>
    </div>

    <!-- Female Directors -->
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">女性董事席次與多元化</div>
            <div class="fs-4 fw-bold text-primary mt-1">
                <?= $female ?> / <?= $seats ?> <span class="fs-6 fw-normal text-muted">席</span>
            </div>
            <div class="text-primary small fw-semibold">
                女性董事佔比: <?= $femaleRatio ?>%
            </div>
        </div>
    </div>

    <!-- Attendance -->
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">董事會運作效能</div>
            <div class="fs-4 fw-bold text-dark mt-1">
                <?= $current['board_meetings_count'] ?? 0 ?> <span class="fs-6 fw-normal text-muted">次開會</span>
            </div>
            <div class="text-muted small">
                平均出席率: <strong class="text-success"><?= $current['board_attendance_rate'] ?? 0 ?>%</strong>
            </div>
        </div>
    </div>

    <!-- Ethics & Whistleblower -->
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">誠信經營與反貪腐 (GRI 205)</div>
            <div class="fs-4 fw-bold text-success mt-1">
                <?= number_format($current['ethics_train_headcount'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">人受訓</span>
            </div>
            <div class="text-muted small">
                舉報信箱: 受理 <?= $current['whistleblower_cases'] ?? 0 ?> / 結案 <?= $current['whistleblower_closed'] ?? 0 ?>
            </div>
        </div>
    </div>
</div>

<!-- Compliance Status Banner -->
<div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white">
    <div class="row align-items-center">
        <div class="col-md-4 border-end">
            <div class="d-flex align-items-center">
                <i class="bi bi-shield-lock-fill fs-2 text-success me-3"></i>
                <div>
                    <div class="text-secondary small">重大資安與個資侵害事件</div>
                    <div class="fs-5 fw-bold <?= ($current['cyber_incidents_count'] ?? 0) == 0 ? 'text-success' : 'text-danger' ?>">
                        <?= ($current['cyber_incidents_count'] ?? 0) == 0 ? '零事件 (Zero Incidents)' : $current['cyber_incidents_count'] . ' 件' ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 border-end">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-octagon-fill fs-2 <?= ($current['legal_penalty_count'] ?? 0) == 0 ? 'text-success' : 'text-danger' ?> me-3"></i>
                <div>
                    <div class="text-secondary small">重大環保與勞工法規裁罰</div>
                    <div class="fs-5 fw-bold <?= ($current['legal_penalty_count'] ?? 0) == 0 ? 'text-success' : 'text-danger' ?>">
                        <?= ($current['legal_penalty_count'] ?? 0) == 0 ? '零裁罰 (Zero Penalties)' : $current['legal_penalty_count'] . ' 筆' ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-currency-dollar fs-2 text-secondary me-3"></i>
                <div>
                    <div class="text-secondary small">法規裁罰累計總金額</div>
                    <div class="fs-5 fw-bold text-dark">
                        NT$ <?= number_format($current['legal_penalty_amount'] ?? 0) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TCFD Climate Risk Matrix -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-compass text-danger me-2"></i>TCFD 氣候變遷相關財務揭露風險矩陣 (Climate Risk & Opportunities)
            </h6>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">氣候情境分析</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 180px;">風險類別</th>
                    <th>氣候風險情境與事件名稱</th>
                    <th>潛在財務衝擊說明</th>
                    <th style="width: 140px;">財務衝擊程度</th>
                    <th style="width: 140px;">發生機率</th>
                    <th>因應減緩與適應策略</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tcfdRisks as $rk): ?>
                    <tr>
                        <td>
                            <span class="badge <?= strpos($rk['category'], '轉型') !== false ? 'bg-primary-subtle text-primary border' : 'bg-warning-subtle text-warning-emphasis border' ?>">
                                <?= e($rk['category']) ?>
                            </span>
                        </td>
                        <td class="fw-semibold text-dark"><?= e($rk['name']) ?></td>
                        <td class="small text-secondary"><?= e($rk['description']) ?></td>
                        <td><span class="badge bg-danger text-white"><?= e($rk['impact']) ?></span></td>
                        <td><span class="badge bg-warning text-dark"><?= e($rk['likelihood']) ?></span></td>
                        <td class="small text-dark"><?= e($rk['response']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for editing Governance metrics -->
<div class="modal fade" id="govModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="index.php?route=governance">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-check text-navy me-2"></i>維護公司治理指標 (<?= $year ?> 年度)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="period_year" value="<?= $year ?>">

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">1. 董事會運作與結構 (GRI 2-9 ~ 2-12)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-secondary">董事會總席位</label>
                            <input type="number" name="board_seats_total" class="form-control" value="<?= $current['board_seats_total'] ?? 9 ?>" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-secondary">獨立董事席位</label>
                            <input type="number" name="independent_directors" class="form-control" value="<?= $current['independent_directors'] ?? 4 ?>" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-secondary">女性董事席位</label>
                            <input type="number" name="female_directors" class="form-control" value="<?= $current['female_directors'] ?? 3 ?>" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-secondary">開會次數</label>
                            <input type="number" name="board_meetings_count" class="form-control" value="<?= $current['board_meetings_count'] ?? 8 ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">董事平均出席率 (%)</label>
                        <input type="number" step="0.01" name="board_attendance_rate" class="form-control" value="<?= $current['board_attendance_rate'] ?? 98.2 ?>" required>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">2. 誠信經營與反貪腐 (GRI 205)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">誠信經營受訓人次</label>
                            <input type="number" name="ethics_train_headcount" class="form-control" value="<?= $current['ethics_train_headcount'] ?? 700 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">舉報信箱受理件數</label>
                            <input type="number" name="whistleblower_cases" class="form-control" value="<?= $current['whistleblower_cases'] ?? 0 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">舉報信箱結案件數</label>
                            <input type="number" name="whistleblower_closed" class="form-control" value="<?= $current['whistleblower_closed'] ?? 0 ?>">
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">3. 資安與法遵事件 (GRI 2-27 & ISO 27001)</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">重大資安事件數</label>
                            <input type="number" name="cyber_incidents_count" class="form-control" value="<?= $current['cyber_incidents_count'] ?? 0 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">重大法規裁罰件數</label>
                            <input type="number" name="legal_penalty_count" class="form-control" value="<?= $current['legal_penalty_count'] ?? 0 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">罰鍰總金額 (NTD)</label>
                            <input type="number" step="0.01" name="legal_penalty_amount" class="form-control" value="<?= $current['legal_penalty_amount'] ?? 0 ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success" style="background-color: var(--esg-green); border: none;">
                        <i class="bi bi-check2 me-1"></i>保存指標
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
