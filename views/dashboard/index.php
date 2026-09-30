<?php
// views/dashboard/index.php
require __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-speedometer2 text-success me-2"></i>ESG 永續戰情室 (Executive Dashboard)
        </h4>
        <div class="text-secondary small">
            集團全範疇溫室氣體盤查、能資源消耗趨勢與減碳目標即時監控
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
        <input type="hidden" name="route" value="dashboard">
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="2024" <?= $year == 2024 ? 'selected' : '' ?>>2024 年度</option>
            <option value="2023" <?= $year == 2023 ? 'selected' : '' ?>>2023 年度 (基準年)</option>
        </select>
        <select name="org_unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">全集團所有廠區</option>
            <?php foreach ($orgs as $o): ?>
                <option value="<?= $o['id'] ?>" <?= $orgUnitId == $o['id'] ? 'selected' : '' ?>><?= e($o['unit_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <a href="index.php?route=report_gri&year=<?= $year ?>" class="btn btn-outline-success btn-sm text-nowrap">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>GRI 報表
        </a>
    </form>
</div>

<!-- Anomaly Alert Banner if any anomaly detected -->
<?php if (!empty($anomalies)): ?>
    <div class="alert anomaly-alert shadow-sm p-3 mb-4 rounded-3" role="alert">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2"></i>
            <strong class="text-danger">溫室氣體活動數據異常波動預警 (超過歷史均值 ±20%)</strong>
        </div>
        <div class="small text-secondary mb-2">
            依據 ISO 14064-1 數據品質管制要求，偵測到以下活動項目單月排放與歷史均值偏差過大，請審核主管儘速查核佐證單據：
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($anomalies as $anom): ?>
                <span class="badge bg-white text-danger border border-danger-subtle p-2">
                    <strong><?= e($anom['unit_name']) ?></strong> (<?= e($anom['period_month']) ?>月) - <?= e($anom['emission_source']) ?>: 
                    <?= $anom['calculated_tco2e'] ?> tCO2e (偏差: <strong class="<?= $anom['anomaly_detail']['diff_percent'] > 0 ? 'text-danger' : 'text-primary' ?>"><?= ($anom['anomaly_detail']['diff_percent'] > 0 ? '+' : '') . $anom['anomaly_detail']['diff_percent'] ?>%</strong>)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Total Emissions -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-kpi p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary small fw-semibold">全集團總碳排量</div>
                    <div class="fs-3 fw-bold text-dark mt-1">
                        <?= number_format($summary['total_emissions'], 2) ?>
                        <span class="fs-6 fw-normal text-muted">tCO₂e</span>
                    </div>
                    <div class="small mt-1">
                        <?php if ($prevSummary['total_emissions'] > 0): ?>
                            <span class="<?= $yoyDiffPercent <= 0 ? 'text-success' : 'text-danger' ?> fw-semibold">
                                <i class="bi <?= $yoyDiffPercent <= 0 ? 'bi-arrow-down-right' : 'bi-arrow-up-right' ?>"></i>
                                <?= abs($yoyDiffPercent) ?>%
                            </span>
                            <span class="text-muted">較 <?= $prevYear ?> 基準年</span>
                        <?php else: ?>
                            <span class="text-muted">基準年度</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-cloud-haze2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Scope 1 -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-kpi p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary small fw-semibold">範疇一 (Scope 1) 直接排放</div>
                    <div class="fs-3 fw-bold text-danger mt-1">
                        <?= number_format($summary['scopes']['Scope1'], 2) ?>
                        <span class="fs-6 fw-normal text-muted">tCO₂e</span>
                    </div>
                    <div class="small text-muted mt-1">
                        佔比 <?= $summary['total_emissions'] > 0 ? round(($summary['scopes']['Scope1'] / $summary['total_emissions']) * 100, 1) : 0 ?>% (柴油、汽油、天然氣)
                    </div>
                </div>
                <div class="kpi-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-fire"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Scope 2 -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-kpi p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary small fw-semibold">範疇二 (Scope 2) 外購電力</div>
                    <div class="fs-3 fw-bold text-primary mt-1">
                        <?= number_format($summary['scopes']['Scope2'], 2) ?>
                        <span class="fs-6 fw-normal text-muted">tCO₂e</span>
                    </div>
                    <div class="small text-muted mt-1">
                        佔比 <?= $summary['total_emissions'] > 0 ? round(($summary['scopes']['Scope2'] / $summary['total_emissions']) * 100, 1) : 0 ?>% (台電電網間接排放)
                    </div>
                </div>
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-lightning-charge"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Scope 3 -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-kpi p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary small fw-semibold">範疇三 (Scope 3) 其他間接</div>
                    <div class="fs-3 fw-bold text-success mt-1">
                        <?= number_format($summary['scopes']['Scope3'], 2) ?>
                        <span class="fs-6 fw-normal text-muted">tCO₂e</span>
                    </div>
                    <div class="small text-muted mt-1">
                        佔比 <?= $summary['total_emissions'] > 0 ? round(($summary['scopes']['Scope3'] / $summary['total_emissions']) * 100, 1) : 0 ?>% (自來水、廢棄物焚化)
                    </div>
                </div>
                <div class="kpi-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary KPIs & Progress Bar -->
<div class="row g-3 mb-4">
    <!-- Green Power & Water Rate -->
    <div class="col-lg-6">
        <div class="card card-kpi p-3 h-100">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-battery-charging text-success me-2"></i>能資源轉型與水循環效率</h6>
            <div class="row g-3 text-center">
                <div class="col-6 border-end">
                    <div class="text-secondary small">綠電使用比例 (Green Power %)</div>
                    <div class="fs-4 fw-bold text-success mt-1"><?= $summary['energy_water']['green_ratio'] ?>%</div>
                    <div class="text-muted small">總用電 <?= number_format($summary['energy_water']['total_elec_kwh']) ?> 度</div>
                </div>
                <div class="col-6">
                    <div class="text-secondary small">製程水循環回收率 (Water Recycling)</div>
                    <div class="fs-4 fw-bold text-info mt-1"><?= $summary['energy_water']['recycle_ratio'] ?>%</div>
                    <div class="text-muted small">總用水 <?= number_format($summary['energy_water']['total_water_m3']) ?> 度</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Annual Carbon Reduction Progress -->
    <div class="col-lg-6">
        <div class="card card-kpi p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-down-arrow text-primary me-2"></i>年度減碳 KPI 達成進度</h6>
                <span class="badge bg-primary-subtle text-primary">目標: 較基準年 -5%</span>
            </div>
            <div class="text-secondary small mb-2">
                實際減碳幅度：<strong class="<?= $actualReductionPercent >= 5 ? 'text-success' : 'text-dark' ?>"><?= $actualReductionPercent ?>%</strong>
                (目標達成率：<strong><?= $targetProgress ?>%</strong>)
            </div>
            <div class="progress" style="height: 16px; border-radius: 8px;">
                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" 
                     style="width: <?= min(100, $targetProgress) ?>%;" 
                     aria-valuenow="<?= $targetProgress ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= $targetProgress ?>%
                </div>
            </div>
            <div class="d-flex justify-content-between text-muted small mt-2">
                <span>0% (未啟動)</span>
                <span>50% (中期檢討)</span>
                <span>100% (達標完成)</span>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-3 mb-4">
    <!-- Monthly Trend Chart -->
    <div class="col-lg-8">
        <div class="card card-kpi p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-graph-up me-2 text-primary"></i>各月份碳排走勢 (tCO₂e) - 範疇堆疊趨勢
                </h6>
                <span class="badge bg-light text-secondary border">月度核算</span>
            </div>
            <div style="height: 300px;">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Scope Donut Chart -->
    <div class="col-lg-4">
        <div class="card card-kpi p-3 h-100">
            <h6 class="fw-bold text-dark mb-3">
                <i class="bi bi-pie-chart me-2 text-success"></i>三大範疇排放佔比
            </h6>
            <div style="height: 250px; position: relative;">
                <canvas id="scopeDonutChart"></canvas>
            </div>
            <div class="mt-3 text-center small text-muted">
                範疇二外購電力為集團主要排放來源
            </div>
        </div>
    </div>
</div>

<!-- Plant Comparison & Approval Status -->
<div class="row g-3 mb-4">
    <!-- Plant Bar Chart -->
    <div class="col-lg-7">
        <div class="card card-kpi p-3 h-100">
            <h6 class="fw-bold text-dark mb-3">
                <i class="bi bi-buildings me-2 text-info"></i>各廠區/據點排放量比較 (tCO₂e)
            </h6>
            <div style="height: 250px;">
                <canvas id="plantBarChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Workflow Status Distribution -->
    <div class="col-lg-5">
        <div class="card card-kpi p-3 h-100">
            <h6 class="fw-bold text-dark mb-3">
                <i class="bi bi-kanban me-2 text-warning"></i>盤查數據四階段審批狀態
            </h6>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span><i class="bi bi-circle-fill text-secondary me-2"></i>草稿階段 (Draft)</span>
                    <span class="badge bg-secondary rounded-pill"><?= $summary['workflow']['draft'] ?> 筆</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span><i class="bi bi-circle-fill text-warning me-2"></i>待主管審核 (Pending)</span>
                    <span class="badge bg-warning text-dark rounded-pill"><?= $summary['workflow']['pending'] ?> 筆</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span><i class="bi bi-circle-fill text-success me-2"></i>審核通過 (Approved)</span>
                    <span class="badge bg-success rounded-pill"><?= $summary['workflow']['approved'] ?> 筆</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span><i class="bi bi-circle-fill text-primary me-2"></i>第三方查證鎖檔 (Locked)</span>
                    <span class="badge bg-primary rounded-pill"><?= $summary['workflow']['locked'] ?> 筆</span>
                </li>
            </ul>
            <div class="mt-3 text-end">
                <a href="index.php?route=ghg&year=<?= $year ?>" class="btn btn-outline-primary btn-sm">
                    前往活動清冊審核 <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Monthly Trend Chart
    const monthlyLabels = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
    const monthlyData = <?= json_encode($summary['monthly']) ?>;
    
    const scope1Data = [];
    const scope2Data = [];
    const scope3Data = [];
    for (let m = 1; m <= 12; m++) {
        scope1Data.push(monthlyData[m] ? monthlyData[m]['Scope1'] : 0);
        scope2Data.push(monthlyData[m] ? monthlyData[m]['Scope2'] : 0);
        scope3Data.push(monthlyData[m] ? monthlyData[m]['Scope3'] : 0);
    }

    const ctxTrend = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: '範疇一 (直接)',
                    data: scope1Data,
                    backgroundColor: '#DC2626',
                    borderRadius: 4
                },
                {
                    label: '範疇二 (電力)',
                    data: scope2Data,
                    backgroundColor: '#2563EB',
                    borderRadius: 4
                },
                {
                    label: '範疇三 (其他間接)',
                    data: scope3Data,
                    backgroundColor: '#10B981',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, title: { display: true, text: '公噸 CO₂e' } }
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            }
        }
    });

    // 2. Scope Donut Chart
    const ctxDonut = document.getElementById('scopeDonutChart').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: ['範疇一 (Scope 1)', '範疇二 (Scope 2)', '範疇三 (Scope 3)'],
            datasets: [{
                data: [
                    <?= (float)$summary['scopes']['Scope1'] ?>,
                    <?= (float)$summary['scopes']['Scope2'] ?>,
                    <?= (float)$summary['scopes']['Scope3'] ?>
                ],
                backgroundColor: ['#DC2626', '#2563EB', '#10B981'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // 3. Plant Bar Chart
    const plantNames = [];
    const plantTco2 = [];
    <?php foreach ($summary['plants'] as $p): ?>
        plantNames.push(<?= json_encode($p['unit_name']) ?>);
        plantTco2.push(<?= (float)$p['total_tco2e'] ?>);
    <?php endforeach; ?>

    const ctxPlant = document.getElementById('plantBarChart').getContext('2d');
    new Chart(ctxPlant, {
        type: 'bar',
        data: {
            labels: plantNames,
            datasets: [{
                label: '排放總量 (tCO₂e)',
                data: plantTco2,
                backgroundColor: '#1B365D',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: { title: { display: true, text: '公噸 CO₂e' } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
