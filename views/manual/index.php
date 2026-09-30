<?php
// views/manual/index.php - Built-in operator manual
require __DIR__ . '/../layout/header.php';
?>
<div class="manual-hero p-4 p-lg-5 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <span class="badge bg-success-subtle text-success-emphasis mb-2">新手管理者指南 · <?= e(APP_VERSION) ?></span>
            <h1 class="display-6 fw-bold mb-2">ESG-SMP 系統操作手冊</h1>
            <p class="lead mb-0">從登入、填報、審核到年度封存，使用搜尋就能快速找到下一步該怎麼做。</p>
        </div>
        <div class="col-lg-4 text-lg-end"><i class="bi bi-journal-richtext" style="font-size: 6rem; opacity:.28"></i></div>
    </div>
</div>

<div class="manual-search card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="input-group input-group-lg">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input id="manualSearch" type="search" class="form-control border-start-0" placeholder="搜尋：例如「上傳憑證」、「鎖檔」、「FR」、「使用者」" aria-label="搜尋操作手冊">
            <button id="manualClear" class="btn btn-outline-secondary d-none" type="button">清除</button>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3" aria-label="手冊分類">
            <button class="manual-chip active rounded-pill px-3 py-1" data-manual-filter="all">全部</button>
            <button class="manual-chip rounded-pill px-3 py-1" data-manual-filter="start">開始使用</button>
            <button class="manual-chip rounded-pill px-3 py-1" data-manual-filter="data">資料填報</button>
            <button class="manual-chip rounded-pill px-3 py-1" data-manual-filter="review">審核與報告</button>
            <button class="manual-chip rounded-pill px-3 py-1" data-manual-filter="admin">系統管理</button>
            <button class="manual-chip rounded-pill px-3 py-1" data-manual-filter="help">問題排除</button>
            <span class="manual-muted small align-self-center ms-auto">快捷鍵：按 <kbd>/</kbd> 開始搜尋、<kbd>Esc</kbd> 清除</span>
        </div>
    </div>
</div>

<div id="manualNoResults" class="manual-no-results alert alert-warning">找不到符合內容。請改用「資料」、「權限」、「審核」或「附件」等較短關鍵字。</div>

<section id="start" class="manual-section mb-5" data-manual-item data-manual-category="start" data-manual-keywords="登入 角色 權限 開始">
    <div class="d-flex align-items-center mb-3"><span class="icon-wrap me-2"><i class="bi bi-flag"></i></span><h2 class="h4 mb-0">1. 第一次使用：先了解畫面</h2></div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="manual-card p-4">
                <p>登入後左側是功能選單，中央是目前工作的頁面，右上角顯示您的姓名、角色與所屬單位。不同角色能看到的按鈕不同；看不到某功能通常代表權限限制，而不是系統故障。</p>
                <div class="manual-step"><span class="manual-step-num">1</span><div><strong>登入</strong><br><span class="manual-muted">輸入管理員提供的帳號與密碼。連續錯誤達系統上限會暫時鎖定帳號。</span></div></div>
                <div class="manual-step"><span class="manual-step-num">2</span><div><strong>確認單位與角色</strong><br><span class="manual-muted">右上角查看「所屬單位」；填報者通常只能處理自己的廠區。</span></div></div>
                <div class="manual-step"><span class="manual-step-num">3</span><div><strong>從 ESG 戰情室開始</strong><br><span class="manual-muted">先看年度、排放總量、Scope 分布及待處理件數，再進入對應模組。</span></div></div>
                <div class="manual-callout p-3 mt-3"><i class="bi bi-lightbulb me-2"></i><strong>小提示：</strong>手冊固定在左側選單最下方；任何頁面都能回來查詢。</div>
            </div>
        </div>
        <div class="col-lg-5"><div class="screen-mock" role="img" aria-label="ESG戰情室畫面示意"><div class="screen-bar"><span>ESG-SMP 永續平台</span><span>管理者 · admin</span></div><div class="screen-body"><div class="screen-side"><div class="selected">戰情室</div><div>碳盤查</div><div>能資源</div><div>GRI 報告</div><div>操作手冊</div></div><div class="screen-main"><div class="screen-title">ESG 戰情室 · 2024 年度</div><div class="screen-kpis"><div class="screen-kpi">總碳排<strong>403.97</strong>tCO₂e</div><div class="screen-kpi">Scope 1<strong>125.4</strong></div><div class="screen-kpi">待審核<strong>10</strong>筆</div></div><div class="screen-table"></div></div></div></div><div class="small text-muted mt-2">畫面示意：實際數值依您的年度與權限顯示。</div></div>
    </div>
</section>

<section id="data" class="manual-section mb-5" data-manual-item data-manual-category="data" data-manual-keywords="GHG 碳盤查 活動量 排放係數 附件 憑證 發票 草稿 送審 能源 水 FR SR 社會 治理">
    <div class="d-flex align-items-center mb-3"><span class="icon-wrap me-2"><i class="bi bi-pencil-square"></i></span><h2 class="h4 mb-0">2. 資料填報</h2></div>
    <div class="accordion" id="manualDataAccordion">
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#ghgGuide">2.1 填報溫室氣體活動數據（GHG）</button></h3><div id="ghgGuide" class="accordion-collapse collapse show"><div class="accordion-body"><ol><li>左側選擇「碳盤查活動清冊」，按「新增活動數據」。</li><li>選擇所屬廠區、年度、月份與排放係數；系統會自動帶出 Scope 與單位。</li><li>輸入活動量，例如用電度數、柴油公升數；系統即時計算 <code>活動量 × 排放係數 ÷ 1000</code>。</li><li>填入發票／憑單編號，並上傳 PDF、圖片或 Excel 佐證。</li><li>按「儲存草稿」暫存，確認無誤後按「提交審核」。</li></ol><div class="manual-callout p-3"><strong>注意：</strong>提交後不能由一般填報者直接修改；若被退回，請依審核意見修正後重新送審。</div></div></div></div>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#energyGuide">2.2 填報能資源與水消耗</button></h3><div id="energyGuide" class="accordion-collapse collapse"><div class="accordion-body">進入「能資源與水消耗」→「新增月報」，輸入市電、綠電、自來水、回收水、天然氣、柴油與汽油數值。相同廠區／年度／月份再次保存會更新原月報；已封存月份不可修改。畫面上的綠電比例與水回收率由系統自動計算。</div></div></div>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#socialGuide">2.3 填報社會與治理指標</button></h3><div id="socialGuide" class="accordion-collapse collapse"><div class="accordion-body">管理者在「DEI 與安衛指標」填入員工、工時、傷害、培訓、志工與捐贈資料，系統自動計算 FR／SR；在「董事治理與 TCFD 風險」填入董事會、吹哨、資安與裁罰資料。年度封存後，相關年度不可再改。</div></div></div>
    </div>
</section>

<section id="review" class="manual-section mb-5" data-manual-item data-manual-category="review" data-manual-keywords="審核 退回 核准 鎖檔 GRI CSV 報告 匯出 稽核">
    <div class="d-flex align-items-center mb-3"><span class="icon-wrap me-2"><i class="bi bi-check2-square"></i></span><h2 class="h4 mb-0">3. 審核、報告與封存</h2></div>
    <div class="row g-4">
        <div class="col-md-4"><div class="manual-card p-4"><h3 class="h6"><i class="bi bi-search me-2"></i>審核者</h3><p class="small">在 GHG 清冊用狀態篩選「待審核」，開啟附件與計算值，確認來源、期間、係數及發票。</p><span class="badge text-bg-warning">Pending</span></div></div>
        <div class="col-md-4"><div class="manual-card p-4"><h3 class="h6"><i class="bi bi-arrow-return-left me-2"></i>退回補正</h3><p class="small">資料不完整時選「退回」，在審核意見寫明缺少的內容；填報者修改後重新提交。</p><span class="badge text-bg-danger">Rejected</span></div></div>
        <div class="col-md-4"><div class="manual-card p-4"><h3 class="h6"><i class="bi bi-lock me-2"></i>年度封存</h3><p class="small">確認該年度沒有草稿、待審或退回資料後，由 admin／ESG Lead 在 GRI 報告頁執行封存。</p><span class="badge text-bg-primary">Locked</span></div></div>
    </div>
    <div class="manual-card p-4 mt-4"><div class="row align-items-center g-4"><div class="col-lg-5"><div class="screen-mock" role="img" aria-label="GHG清冊審核畫面示意"><div class="screen-bar"><span>碳盤查活動清冊</span><span>狀態：待審核</span></div><div class="screen-body"><div class="screen-side"><div>年度</div><div class="selected">待審</div><div>範疇</div></div><div class="screen-main"><div class="screen-title">桃園大園一廠 · 2024/10</div><div class="screen-kpi">柴油發電機　<strong>2.4358 tCO₂e</strong></div><div class="screen-table"></div></div></div></div></div><div class="col-lg-7"><h3 class="h5">報告操作順序</h3><div class="manual-step"><span class="manual-step-num">1</span><div>先在「GRI 準則內容索引」確認年度彙總與未封存筆數。</div></div><div class="manual-step"><span class="manual-step-num">2</span><div>使用「匯出 GHG 清冊 (CSV)」提供查證或內部分析。</div></div><div class="manual-step"><span class="manual-step-num">3</span><div>確認所有資料核准後，再執行年度封存；封存後請勿再以資料庫直接修改。</div></div></div></div></div>
</section>

<section id="admin" class="manual-section mb-5" data-manual-item data-manual-category="admin" data-manual-keywords="管理員 使用者 角色 組織 係數 系統參數 權限">
    <div class="d-flex align-items-center mb-3"><span class="icon-wrap me-2"><i class="bi bi-gear"></i></span><h2 class="h4 mb-0">4. 管理員維護</h2></div>
    <div class="table-responsive manual-card"><table class="table align-middle mb-0"><thead><tr><th>功能</th><th>用途</th><th>建議作法</th></tr></thead><tbody><tr><td>使用者帳號權限</td><td>建立帳號、指定角色與所屬組織</td><td>採最小權限；離職或停用帳號立即停權。</td></tr><tr><td>組織廠區架構</td><td>維護集團／公司／廠區／部門階層</td><td>先建立組織，再建立使用者與資料。</td></tr><tr><td>排放係數庫</td><td>新增或更新 Scope 1／2／3 係數</td><td>保留來源、有效年度；已使用係數不要任意覆寫。</td></tr><tr><td>系統參數設定</td><td>異常門檻、基準年、減碳目標、登入安全設定</td><td>調整前記錄原因，調整後用稽核日誌確認。</td></tr><tr><td>稽核日誌</td><td>查詢登入、填報、修改、審核、匯出與封存</td><td>定期匯出保存，不刪除稽核紀錄。</td></tr></tbody></table></div>
</section>

<section id="help" class="manual-section mb-4" data-manual-item data-manual-category="help" data-manual-keywords="問題 故障 錯誤 登入 附件 權限 資料 查不到">
    <div class="d-flex align-items-center mb-3"><span class="icon-wrap me-2"><i class="bi bi-life-preserver"></i></span><h2 class="h4 mb-0">5. 常見問題排除</h2></div>
    <div class="accordion" id="manualHelpAccordion">
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#faq1">我看不到某個功能，怎麼辦？</button></h3><div id="faq1" class="accordion-collapse collapse show"><div class="accordion-body">先確認右上角角色。管理功能只有 admin 可見；係數、社會與治理維護通常需要 admin 或 ESG Lead。若角色正確仍看不到，請提供帳號、頁面與時間給系統管理員查核權限。</div></div></div>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq2">為什麼資料不能修改？</button></h3><div id="faq2" class="accordion-collapse collapse"><div class="accordion-body">常見原因是資料已送審、已核准、已封存，或資料不屬於您的組織。若狀態為退回，請依審核意見修正；若為 Locked，請由管理者確認是否需要正式更正流程。</div></div></div>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq3">附件上傳失敗怎麼辦？</button></h3><div id="faq3" class="accordion-collapse collapse"><div class="accordion-body">確認檔案是 PDF、JPG、PNG 或 Excel／CSV，且未超過系統設定大小。重新命名為英數字檔名後再試；仍失敗時記錄檔案格式、大小與錯誤訊息，交給管理員。</div></div></div>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq4">儀表板數值和我輸入的數值不同？</button></h3><div id="faq4" class="accordion-collapse collapse"><div class="accordion-body">儀表板只統計已核准或已封存資料；草稿、待審與退回資料不會納入正式 KPI。請先檢查年度、廠區、Scope 與工作流狀態篩選。</div></div></div>
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq5">忘記密碼或帳號被鎖定？</button></h3><div id="faq5" class="accordion-collapse collapse"><div class="accordion-body">請聯絡系統管理員，不要共用其他人的帳號。管理員可在「使用者帳號權限」重設密碼、啟用帳號並清除失敗次數。</div></div></div>
    </div>
    <div class="manual-callout manual-danger p-3 mt-4"><i class="bi bi-shield-exclamation me-2"></i><strong>安全提醒：</strong>不要把密碼、附件或資料庫帳號貼在公開聊天室；回報問題時只提供必要的錯誤訊息與時間。</div>
</section>

<script src="assets/js/manual.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
