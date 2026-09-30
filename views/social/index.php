<?php
// views/social/index.php
require __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-people text-primary me-2"></i>社會責任指標 (DEI 多元共融與職安衛 ISO 45001)
        </h4>
        <div class="text-secondary small">
            涵蓋 GRI 401 (員工聘僱)、GRI 403 (職業健康安全)、GRI 404 (訓練教育) 與 GRI 405 (多元平等)
        </div>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
            <input type="hidden" name="route" value="social">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="2024" <?= $year == 2024 ? 'selected' : '' ?>>2024 年度</option>
                <option value="2023" <?= $year == 2023 ? 'selected' : '' ?>>2023 年度</option>
            </select>
        </form>
        <?php if (Auth::can('manage_metrics')): ?>
            <button type="button" class="btn btn-success btn-sm shadow-sm" style="background-color: var(--esg-green); border: none;" data-bs-toggle="modal" data-bs-target="#socialModal">
                <i class="bi bi-plus-circle me-1"></i>填報社會責任指標
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Employees & Gender -->
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">集團員工總數</div>
            <div class="fs-4 fw-bold text-dark mt-1">
                <?= number_format($summary['total_emp']) ?> <span class="fs-6 fw-normal text-muted">人</span>
            </div>
            <div class="text-muted small">
                男: <?= $summary['male_emp'] ?> / 女: <?= $summary['female_emp'] ?> (女性佔比: <strong class="text-primary"><?= $summary['female_emp_ratio'] ?>%</strong>)
            </div>
        </div>
    </div>

    <!-- Female Managers & Disabled -->
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">女性主管與身障就業</div>
            <div class="fs-4 fw-bold text-success mt-1">
                <?= $summary['female_mgr'] ?> <span class="fs-6 fw-normal text-muted">位女性主管</span>
            </div>
            <div class="text-muted small">
                身障進用: <strong class="text-dark"><?= $summary['disabled_emp'] ?></strong> 位 (符合法定進用比例)
            </div>
        </div>
    </div>

    <!-- OHS: FR & SR -->
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">失能傷害頻率 (FR)</div>
            <div class="fs-4 fw-bold <?= $summary['overall_fr'] == 0 ? 'text-success' : 'text-danger' ?> mt-1">
                <?= $summary['overall_fr'] ?>
            </div>
            <div class="text-muted small">
                工傷次數: <?= $summary['injuries'] ?> 次 / 總工時: <?= number_format($summary['work_hours']) ?> 小時
            </div>
        </div>
    </div>

    <!-- OHS: SR & Days Lost -->
    <div class="col-md-3 col-6">
        <div class="card card-kpi p-3">
            <div class="text-secondary small">失能傷害嚴重率 (SR)</div>
            <div class="fs-4 fw-bold <?= $summary['overall_sr'] == 0 ? 'text-success' : 'text-warning' ?> mt-1">
                <?= $summary['overall_sr'] ?>
            </div>
            <div class="text-muted small">
                損失工作日數: <?= $summary['lost_days'] ?> 日
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-kpi p-3">
            <div class="d-flex align-items-center">
                <i class="bi bi-book fs-3 text-info me-3"></i>
                <div>
                    <div class="text-secondary small">員工教育培訓總時數</div>
                    <div class="fs-5 fw-bold text-dark"><?= number_format($summary['training_hours']) ?> 小時</div>
                    <div class="text-muted small">人均時數: <?= $summary['total_emp'] > 0 ? round($summary['training_hours'] / $summary['total_emp'], 1) : 0 ?> 小時/年</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-kpi p-3">
            <div class="d-flex align-items-center">
                <i class="bi bi-heart fs-3 text-danger me-3"></i>
                <div>
                    <div class="text-secondary small">企業志工服務總時數</div>
                    <div class="fs-5 fw-bold text-dark"><?= number_format($summary['volunteer_hours']) ?> 小時</div>
                    <div class="text-muted small">社區參與與淨灘/弱勢服務</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-kpi p-3">
            <div class="d-flex align-items-center">
                <i class="bi bi-cash-coin fs-3 text-success me-3"></i>
                <div>
                    <div class="text-secondary small">公益捐贈與贊助金額</div>
                    <div class="fs-5 fw-bold text-success">NT$ <?= number_format($summary['donation']) ?></div>
                    <div class="text-muted small">支持弱勢學童與環保倡議</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>廠區/組織</th>
                    <th>統計年度</th>
                    <th>男性人數</th>
                    <th>女性人數</th>
                    <th>女性主管</th>
                    <th>身障人數</th>
                    <th>總工作時數</th>
                    <th>失能傷害 (次)</th>
                    <th>損失日數</th>
                    <th>FR (百萬工時)</th>
                    <th>SR (百萬工時)</th>
                    <th>培訓總時數</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="12" class="text-center py-4 text-muted">目前暫無該年度之社會指標數據</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><strong class="text-dark"><?= e($r['unit_name']) ?></strong></td>
                            <td class="fw-bold"><?= $r['period_year'] ?></td>
                            <td><?= $r['male_employees'] ?></td>
                            <td><?= $r['female_employees'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $r['female_managers'] ?></span></td>
                            <td><?= $r['disabled_employees'] ?></td>
                            <td><?= number_format($r['total_work_hours']) ?></td>
                            <td class="<?= $r['disabling_injury_count'] > 0 ? 'text-danger fw-bold' : '' ?>"><?= $r['disabling_injury_count'] ?></td>
                            <td class="<?= $r['lost_days_count'] > 0 ? 'text-danger fw-bold' : '' ?>"><?= $r['lost_days_count'] ?></td>
                            <td><strong class="<?= $r['fr_value'] > 0 ? 'text-danger' : 'text-success' ?>"><?= $r['fr_value'] ?></strong></td>
                            <td><strong class="<?= $r['sr_value'] > 0 ? 'text-warning' : 'text-success' ?>"><?= $r['sr_value'] ?></strong></td>
                            <td><?= number_format($r['training_hours_total']) ?> hrs</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Inputting Social Metrics -->
<div class="modal fade" id="socialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="index.php?route=social">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-people text-success me-2"></i>填報社會責任與人資職安指標</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">廠區據點 <span class="text-danger">*</span></label>
                            <select name="org_unit_id" class="form-select" required>
                                <?php foreach ($orgs as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= e($o['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-secondary">統計年度 <span class="text-danger">*</span></label>
                            <select name="period_year" class="form-select" required>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">1. 人力多元與 DEI 指標 (GRI 401 & 405)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-secondary">男性員工數</label>
                            <input type="number" name="male_employees" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-secondary">女性員工數</label>
                            <input type="number" name="female_employees" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-secondary">女性主管數</label>
                            <input type="number" name="female_managers" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label class="form-label small text-secondary">身障員工數</label>
                            <input type="number" name="disabled_employees" class="form-control" value="0" required>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">2. 職業安全衛生與工傷計算 (ISO 45001 / GRI 403)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">總工作時數 (小時)</label>
                            <input type="number" step="0.01" name="total_work_hours" id="workHoursInput" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">失能傷害次數</label>
                            <input type="number" name="disabling_injury_count" id="injuriesInput" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">損失工作日數</label>
                            <input type="number" name="lost_days_count" id="lostDaysInput" class="form-control" value="0" required>
                        </div>
                    </div>

                    <div class="p-2 mb-3 bg-light rounded border small text-muted">
                        <i class="bi bi-info-circle me-1"></i>系統將自動核算：
                        失能傷害頻率 \(FR = \frac{\text{次數} \times 1,000,000}{\text{總工時}}\)，
                        失能傷害嚴重率 \(SR = \frac{\text{損失日數} \times 1,000,000}{\text{總工時}}\)。
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">3. 培訓發展與社會參與 (GRI 404 & 413)</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">員工培訓總時數 (小時)</label>
                            <input type="number" step="0.01" name="training_hours_total" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">志工服務總時數 (小時)</label>
                            <input type="number" step="0.01" name="volunteer_hours" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-secondary">公益捐贈金額 (NTD)</label>
                            <input type="number" step="0.01" name="donation_amount_ntd" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success" style="background-color: var(--esg-green); border: none;">
                        <i class="bi bi-check2 me-1"></i>保存社會指標
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
