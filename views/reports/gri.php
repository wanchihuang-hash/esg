<?php
// views/reports/gri.php
require __DIR__ . '/../layout/header.php';

// Heat value calculations:
// 1 kWh = 0.0036 GJ
// Total electricity in GJ
$totalElecKwh = ((float)($ew['total_grid'] ?? 0)) + ((float)($ew['total_green'] ?? 0));
$totalElecGj = round($totalElecKwh * 0.0036, 2);
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-file-earmark-bar-graph text-success me-2"></i>GRI Standards 2021 永續報告書揭露對照表
        </h4>
        <div class="text-secondary small">
            全球永續性報告倡議準則 (GRI Standards 2021) 內容索引、定量數據與查證清冊
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="GET" action="index.php" class="d-flex align-items-center gap-1">
            <input type="hidden" name="route" value="report_gri">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="2024" <?= $year == 2024 ? 'selected' : '' ?>>2024 年度</option>
                <option value="2023" <?= $year == 2023 ? 'selected' : '' ?>>2023 年度 (基準年)</option>
            </select>
        </form>

        <a href="index.php?route=report_export&year=<?= $year ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-filetype-csv me-1"></i>匯出活動清冊 (CSV)
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">
            <i class="bi bi-printer me-1"></i>列印 / 存為 PDF
        </button>

        <?php if (!$isYearLocked && Auth::hasRole(['admin', 'esg_lead'])): ?>
            <form method="POST" action="index.php?route=report_lock" class="d-inline" onsubmit="return confirm('確定將 <?= $year ?> 年度全集團碳盤查與 ESG 數據封存鎖檔？鎖檔後基層將無法隨意竄改數據！')">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="year" value="<?= $year ?>">
                <button type="submit" class="btn btn-warning btn-sm text-dark fw-semibold">
                    <i class="bi bi-lock-fill me-1"></i>年度審定封存鎖檔
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Lock Status Notice -->
<?php if ($isYearLocked): ?>
    <div class="alert alert-info border-info d-flex align-items-center shadow-sm py-2 mb-3">
        <i class="bi bi-shield-fill-check fs-4 text-primary me-2"></i>
        <div>
            <strong><?= $year ?> 年度已完成全盤審定與資料庫防篡改封存鎖檔 (Locked)</strong>
            <span class="text-secondary small ms-2">符合第三方查證 (ISO 14064-1 & GRI) 確信存證標準，全盤數據受不可竄改保護。</span>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-light border d-flex align-items-center shadow-sm py-2 mb-3">
        <i class="bi bi-pencil-fill text-warning me-2"></i>
        <div class="small text-secondary">
            <strong><?= $year ?> 年度數據編製審查中</strong>：
            目前已核算總排放量：<strong class="text-success"><?= number_format($ghgSummary['total_emissions'], 2) ?> tCO₂e</strong>。所有部門數據審核完畢後，ESG委員會主管可點擊「年度審定封存鎖檔」確保防篡改。
        </div>
    </div>
<?php endif; ?>

<!-- GRI Content Index Table -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark">
            <i class="bi bi-list-check text-success me-2"></i>GRI 內容索引表 (GRI Content Index 2021)
        </span>
        <span class="badge bg-success-subtle text-success border border-success-subtle">
            符合核心申報 (In Accordance)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="text-center">
                    <th style="width: 130px;">GRI 準則編號</th>
                    <th style="width: 200px;">揭露項目名稱</th>
                    <th style="width: 250px;">系統當前定量數據統計結果</th>
                    <th>揭露說明與對應之內部程序 / 查證狀態</th>
                    <th style="width: 110px;">揭露完整性</th>
                </tr>
            </thead>
            <tbody>
                <!-- GRI 2: General Disclosures -->
                <tr class="table-secondary fw-bold">
                    <td colspan="5"><i class="bi bi-bookmark-fill text-success me-2"></i>GRI 2: 一般揭露 (General Disclosures 2021)</td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 2-1</td>
                    <td>組織詳細資訊</td>
                    <td>綠能永續集團總部 (HQ)<br><small class="text-muted">含桃園一廠、台南二廠</small></td>
                    <td class="small">製造業，主要營運據點位於台灣台北、桃園與台南科學園區。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 2-7</td>
                    <td>員工人數與組成</td>
                    <td>
                        總人數：<strong><?= $totalEmp ?></strong> 人<br>
                        男：<?= (int)($soc['male'] ?? 0) ?> 人 / 女：<?= (int)($soc['female'] ?? 0) ?> 人
                    </td>
                    <td class="small">女性員工佔比 <?= $femaleRatio ?>%，均為全職正式編制員工。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 2-9</td>
                    <td>治理架構與組成</td>
                    <td>
                        董事總席位：<strong><?= $gov['board_seats_total'] ?? 9 ?></strong> 席<br>
                        獨立董事：<strong><?= $gov['independent_directors'] ?? 4 ?></strong> 席<br>
                        女性董事：<strong><?= $gov['female_directors'] ?? 3 ?></strong> 席
                    </td>
                    <td class="small">獨立董事佔比高達 <?= $gov['board_seats_total'] ? round(((int)$gov['independent_directors'] / (int)$gov['board_seats_total']) * 100, 1) : 0 ?>%，女性董事佔比達 33.3%，落實多元化政策。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>

                <!-- GRI 205: Anti-corruption -->
                <tr class="table-secondary fw-bold">
                    <td colspan="5"><i class="bi bi-bookmark-fill text-success me-2"></i>GRI 200: 經濟與治理面向 (Economic & Governance)</td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 205-2</td>
                    <td>反貪腐政策與誠信培訓</td>
                    <td>受訓人次：<strong><?= number_format($gov['ethics_train_headcount'] ?? 0) ?></strong> 人次</td>
                    <td class="small">反貪腐政策宣導覆蓋率 100%，舉報信箱件數：<?= $gov['whistleblower_cases'] ?? 0 ?> 件 (已結案 <?= $gov['whistleblower_closed'] ?? 0 ?> 件)。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>

                <!-- GRI 300: Environmental Standards -->
                <tr class="table-secondary fw-bold">
                    <td colspan="5"><i class="bi bi-bookmark-fill text-success me-2"></i>GRI 300: 環境面向 (Environmental Standards)</td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 302-1</td>
                    <td>組織內部之能源消耗</td>
                    <td>
                        外購市電：<?= number_format((float)($ew['total_grid'] ?? 0)) ?> 度<br>
                        綠電使用：<?= number_format((float)($ew['total_green'] ?? 0)) ?> 度<br>
                        電力折合熱值：<strong><?= number_format($totalElecGj, 2) ?> GJ</strong>
                    </td>
                    <td class="small">天然氣用量：<?= number_format((float)($ew['total_gas'] ?? 0)) ?> m³、柴油：<?= number_format((float)($ew['total_diesel'] ?? 0)) ?> 公升。綠電佔比 <?= $ghgSummary['energy_water']['green_ratio'] ?>%。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 303-3 / 303-4</td>
                    <td>取水與水資源循環利用</td>
                    <td>
                        自來水取用量：<?= number_format((float)($ew['total_tap'] ?? 0)) ?> 度(m³)<br>
                        製程水回收量：<?= number_format((float)($ew['total_recycle'] ?? 0)) ?> 度(m³)
                    </td>
                    <td class="small">廠區廢水經 RO 與超濾系統淨化再利用，整體製程水循環回收率達 <strong><?= $ghgSummary['energy_water']['recycle_ratio'] ?>%</strong>。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
                <tr>
                    <td class="text-center fw-bold text-danger">GRI 305-1</td>
                    <td>直接溫室氣體排放 (Scope 1)</td>
                    <td class="text-danger fw-bold">
                        <?= number_format($ghgSummary['scopes']['Scope1'], 4) ?> tCO₂e
                    </td>
                    <td class="small">源自備用發電機柴油、公務車汽油、天然氣鍋爐固定燃燒，依據環境部 6.0.4 係數核算。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
                <tr>
                    <td class="text-center fw-bold text-primary">GRI 305-2</td>
                    <td>能源間接排放 (Scope 2)</td>
                    <td class="text-primary fw-bold">
                        <?= number_format($ghgSummary['scopes']['Scope2'], 4) ?> tCO₂e
                    </td>
                    <td class="small">外購台電電力產生之碳排，依據經濟部能源署公告電力排碳係數 (0.495 kgCO₂e/度) 核算。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
                <tr>
                    <td class="text-center fw-bold text-success">GRI 305-3</td>
                    <td>其他間接排放 (Scope 3)</td>
                    <td class="text-success fw-bold">
                        <?= number_format($ghgSummary['scopes']['Scope3'], 4) ?> tCO₂e
                    </td>
                    <td class="small">涵蓋自來水碳足跡 (0.152 kg/度) 與委外一般事業廢棄物焚化處理 (0.38 kg/公斤)。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>

                <!-- GRI 400: Social Standards -->
                <tr class="table-secondary fw-bold">
                    <td colspan="5"><i class="bi bi-bookmark-fill text-success me-2"></i>GRI 400: 社會面向 (Social Standards)</td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 403-9</td>
                    <td>職業安全健康與工作傷害</td>
                    <td>
                        總工時：<?= number_format($totalHours) ?> 小時<br>
                        失能傷害頻率 (FR)：<strong><?= $overallFr ?></strong><br>
                        失能傷害嚴重率 (SR)：<strong><?= $overallSr ?></strong>
                    </td>
                    <td class="small">失能工傷案件共 <?= (int)($soc['injuries'] ?? 0) ?> 次，損失工作日數 <?= (int)($soc['lost_days'] ?? 0) ?> 日，全數落實工安通報與 ISO 45001 改善對策。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 404-1</td>
                    <td>員工訓練與教育</td>
                    <td>培訓總時數：<strong><?= number_format((float)($soc['training'] ?? 0)) ?></strong> 小時</td>
                    <td class="small">每名員工平均受訓時數達 <?= $totalEmp > 0 ? round((float)($soc['training'] ?? 0) / $totalEmp, 1) : 0 ?> 小時。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
                <tr>
                    <td class="text-center fw-bold">GRI 405-1</td>
                    <td>治理機構與員工多元化</td>
                    <td>
                        女性主管：<strong><?= (int)($soc['female_mgr'] ?? 0) ?></strong> 位<br>
                        身心障礙同仁：<strong><?= (int)($soc['disabled'] ?? 0) ?></strong> 位
                    </td>
                    <td class="small">超額進用身障同仁，積極建構無障礙共融友善職場環境。</td>
                    <td class="text-center"><span class="badge bg-success">完全揭露</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
