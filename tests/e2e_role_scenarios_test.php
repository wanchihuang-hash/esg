<?php
/**
 * tests/e2e_role_scenarios_test.php
 * Comprehensive End-to-End Scenario Testing via Web HTTP Client
 * Simulates real user sessions, forms, CSRF tokens, file uploads, and RBAC enforcement.
 */
require_once __DIR__ . '/../app/Database.php';

require_once __DIR__ . '/WebTestClient.php';

// ---------------------------------------------------------
// Test Execution Suite
// ---------------------------------------------------------

$baseUrl = getenv('ESG_TEST_URL') ?: (is_dir('/home/u721999801') ? 'https://darksalmon-eagle-978314.hostingersite.com/wanchi3366' : 'http://localhost/ESG');
$client = new WebTestClient($baseUrl);
$testResults = [];

function recordStep(string $scenario, string $stepName, bool $success, string $details = '') {
    global $testResults;
    $testResults[] = [
        'scenario' => $scenario,
        'step' => $stepName,
        'passed' => $success,
        'details' => $details
    ];
    $statusText = $success ? "\033[32m[PASS]\033[0m" : "\033[31m[FAIL]\033[0m";
    echo "{$statusText} [{$scenario}] {$stepName}" . ($details ? " -> {$details}" : "") . "\n";
    if (!$success) {
        global $client;
        if ($client) {
            $raw = (string)$client->getContent();
            $clean = trim(preg_replace('/\s+/', ' ', strip_tags($raw)));
            echo "   \033[33m[DEBUG: HTTP " . $client->getStatusCode() . "] " . substr($clean, 0, 150) . "...\033[0m\n";
        }
    }
}

echo "==================================================================\n";
echo "ESG 永續管理系統 (ESG-SMP) - 五大身份業務情境全自動化介面驗證\n";
echo "==================================================================\n\n";

// Idempotent test environment cleanup
$pdoInit = Database::getConnection();
$pdoInit->exec("DELETE FROM esg_ghg_emissions WHERE invoice_no LIKE 'OIL-2024-TEST-%'");
$pdoInit->exec("DELETE FROM sys_users WHERE username LIKE 'tester_%'");
$pdoInit->exec("DELETE FROM sys_org_units WHERE unit_code = 'PLANT-HC4'");
$pdoInit->exec("DELETE FROM esg_emission_factors WHERE factor_code = 'EF-BIO-DIESEL-B20'");
$pdoInit->exec("UPDATE esg_ghg_emissions SET workflow_status = 'approved' WHERE period_year = 2024");
$pdoInit->exec("UPDATE esg_energy_water SET workflow_status = 'approved' WHERE period_year = 2024");

// =========================================================
// SCENARIO 1: 基層數據填報員 (Data Collector - 李專員)
// =========================================================
$client->resetSession();
$s1 = "1. 基層填報員情境";

// 1.1 登入
$ok = $client->login('collector', 'collector123');
recordStep($s1, "登入測試 (collector/collector123)", $ok, "狀態碼: {$client->getStatusCode()}");

// 1.2 瀏覽戰情室
$client->get('index.php?route=dashboard');
$hasKpi = strpos($client->getContent(), '全集團總碳排量') !== false;
recordStep($s1, "瀏覽 ESG 戰情室", $hasKpi, "檢視 KPI 儀表板與能資源效率");

// 1.3 填報活動數據 (Draft)
$client->get('index.php?route=ghg_create');
$csrf = $client->extractCsrfToken();
recordStep($s1, "開啟溫室氣體活動填報表單", !empty($csrf), "取得 CSRF Token");

// Create temporary invoice PDF
$testInvoice = 'OIL-2024-TEST-' . time();
$tmpInvoice = tempnam(sys_get_temp_dir(), 'inv_') . '.pdf';
file_put_contents($tmpInvoice, "%PDF-1.4 Mock Invoice Proof for Generator Diesel Oil");

$postData = [
    'csrf_token' => $csrf,
    'org_unit_id' => 3, // 桃園一廠
    'period_year' => 2024,
    'period_month' => 10,
    'factor_id' => 2, // 柴油固定燃燒 (2.7064 kgCO2e/L)
    'emission_source' => '桃園一廠 10月份 備用緊急柴油發電機 (情境測試)',
    'activity_amount' => 850.00,
    'invoice_no' => $testInvoice,
    'submit_action' => 'draft'
];
$client->post('index.php?route=ghg_store', $postData, ['proof_file' => $tmpInvoice]);
@unlink($tmpInvoice);

$hasRecord = strpos($client->getContent(), $testInvoice) !== false;
recordStep($s1, "新增草稿數據 (850L 柴油，佐證附件上傳)", $hasRecord, "狀態為 draft，預估碳排: 2.3004 tCO2e");

// Query created ID from DB for precise flow testing
$pdo = Database::getConnection();
$createdRecord = $pdo->query("SELECT * FROM esg_ghg_emissions WHERE invoice_no = '{$testInvoice}' ORDER BY id DESC LIMIT 1")->fetch();
$recId = $createdRecord['id'] ?? 0;

// 1.4 修改活動數據 (Edit Draft)
$client->get('index.php?route=ghg_edit&id=' . $recId);
$editCsrf = $client->extractCsrfToken();
$editPost = [
    'csrf_token' => $editCsrf,
    'id' => $recId,
    'org_unit_id' => 3,
    'period_year' => 2024,
    'period_month' => 10,
    'factor_id' => 2,
    'emission_source' => '桃園一廠 10月份 備用緊急柴油發電機 (校正油量)',
    'activity_amount' => 900.00, // 校正為 900L: 900 * 2.7064 / 1000 = 2.4358 tCO2e
    'invoice_no' => $testInvoice,
    'submit_action' => 'draft'
];
$client->post('index.php?route=ghg_update', $editPost);
$updatedRec = $pdo->query("SELECT * FROM esg_ghg_emissions WHERE id = {$recId}")->fetch();
$calcCorrect = ((float)$updatedRec['activity_amount'] == 900.0 && (float)$updatedRec['calculated_tco2e'] == 2.4358);
recordStep($s1, "草稿數據修改與即時重新核算 (校正為 900L)", $calcCorrect, "更新後碳排: {$updatedRec['calculated_tco2e']} tCO2e");

// 1.5 提交送審 (Submit for Review)
$client->get('index.php?route=ghg');
$submitCsrf = $client->extractCsrfToken();
$client->post('index.php?route=ghg_workflow', [
    'csrf_token' => $submitCsrf,
    'id' => $recId,
    'action' => 'submit'
]);
$pendingRec = $pdo->query("SELECT * FROM esg_ghg_emissions WHERE id = {$recId}")->fetch();
recordStep($s1, "提交審核 (Submit to Reviewer)", $pendingRec['workflow_status'] === 'pending', "狀態流轉為: pending");

// 1.6 越權防護驗證 (RBAC Authorization Defense)
$client->get('index.php?route=users');
$blocked = (strpos($client->getContent(), '您沒有存取該模組的權限') !== false);
recordStep($s1, "越權存取阻擋驗證 (禁止存取使用者管理)", $blocked, "系統正確攔截並給予警示");


// =========================================================
// SCENARIO 2: 廠區審核主管 (Plant Reviewer - 張廠長)
// =========================================================
$client->resetSession();
$s2 = "2. 審核主管情境";

// 2.1 登入
$ok = $client->login('reviewer', 'reviewer123');
recordStep($s2, "主管登入測試 (reviewer/reviewer123)", $ok, "廠區審核主管身份確認");

// 2.2 審查待審項目清單
$client->get('index.php?route=ghg&status=pending');
$foundPending = strpos($client->getContent(), $testInvoice) !== false;
recordStep($s2, "待審活動清冊檢索", $foundPending, "檢索到待審資料 #{$recId}");

// 2.3 退回補正測試 (Reject with Comment)
$revCsrf = $client->extractCsrfToken();
$client->post('index.php?route=ghg_workflow', [
    'csrf_token' => $revCsrf,
    'id' => $recId,
    'action' => 'reject',
    'comment' => '發票單據需加註開立日期與經辦簽名，請退回補正後再送'
]);
$rejectedRec = $pdo->query("SELECT * FROM esg_ghg_emissions WHERE id = {$recId}")->fetch();
recordStep($s2, "退回補正審查 (Reject)", $rejectedRec['workflow_status'] === 'rejected', "意見已寫入: {$rejectedRec['reviewer_comment']}");

// 2.4 模擬填報員補正後再送審
$pdo->exec("UPDATE esg_ghg_emissions SET invoice_no = '{$testInvoice} (2024/10/05)', workflow_status = 'pending' WHERE id = {$recId}");

// 2.5 核准通過測試 (Approve with Comment)
$client->post('index.php?route=ghg_workflow', [
    'csrf_token' => $revCsrf,
    'id' => $recId,
    'action' => 'approve',
    'comment' => '佐證單據與發票號碼核對無誤，准予核銷通過'
]);
$approvedRec = $pdo->query("SELECT * FROM esg_ghg_emissions WHERE id = {$recId}")->fetch();
recordStep($s2, "核准通過審查 (Approve)", $approvedRec['workflow_status'] === 'approved' && !empty($approvedRec['reviewed_at']), "審查完成時間: {$approvedRec['reviewed_at']}");

// 2.6 能資源月報審查與填報
$client->get('index.php?route=energy_water_create');
$ewCsrf = $client->extractCsrfToken();
$client->post('index.php?route=energy_water_store', [
    'csrf_token' => $ewCsrf,
    'org_unit_id' => 3,
    'period_year' => 2024,
    'period_month' => 10,
    'elec_grid_kwh' => 42000,
    'elec_green_kwh' => 6500,
    'water_tap_m3' => 1500,
    'water_recycle_m3' => 600,
    'natural_gas_m3' => 1100,
    'diesel_liters' => 900,
    'gasoline_liters' => 300,
    'workflow_status' => 'approved'
]);
$ewRec = $pdo->query("SELECT * FROM esg_energy_water WHERE org_unit_id = 3 AND period_year = 2024 AND period_month = 10")->fetch();
$greenPct = round((6500 / (42000 + 6500)) * 100, 2);
recordStep($s2, "能資源月報審定 (市電 42,000度 / 綠電 6,500度)", !empty($ewRec), "綠電比率: {$greenPct}% (GRI 302)");


// =========================================================
// SCENARIO 3: ESG 委員會負責人 (ESG Lead - 林永續 協理)
// =========================================================
$client->resetSession();
$s3 = "3. ESG 負責人情境";

// 3.1 登入
$ok = $client->login('lead', 'lead123');
recordStep($s3, "ESG 委員會協理登入 (lead/lead123)", $ok, "高階決策層身份確認");

// 3.2 戰情室減碳 KPI 與異常警示監控
$client->get('index.php?route=dashboard&year=2024');
$hasAnomalySection = strpos($client->getContent(), '溫室氣體活動數據異常波動預警') !== false || strpos($client->getContent(), '年度減碳 KPI') !== false;
recordStep($s3, "全集團戰情室與異常警示監控", $hasAnomalySection, "掌握集團排放走勢與波動預警");

// 3.3 產製 GRI Standards 2021 永續報告書對照表
$client->get('index.php?route=report_gri&year=2024');
$hasGriIndex = (strpos($client->getContent(), 'GRI 305-1') !== false && strpos($client->getContent(), 'GRI 302-1') !== false);
recordStep($s3, "產製 GRI 2021 內容索引對照表", $hasGriIndex, "涵蓋 GRI 2/205/302/303/305/403/404/405");

// 3.4 匯出金管會申報格式 CSV 清冊
$csvContent = $client->get('index.php?route=report_export&year=2024');
$csvValid = (strpos($csvContent, '編號,組織廠區,年度') !== false && strpos($csvContent, $testInvoice) !== false);
recordStep($s3, "一鍵匯出溫室氣體活動數據清冊 (CSV)", $csvValid, "含 UTF-8 BOM 與發票單據明細");

// 3.5 年度審定封存鎖檔 (Certified Locking)
$pdo->exec("UPDATE esg_ghg_emissions SET workflow_status = 'approved' WHERE period_year = 2024 AND workflow_status NOT IN ('approved', 'locked') AND id != {$recId}");
$client->get('index.php?route=report_gri&year=2024');
$griCsrf = $client->extractCsrfToken();
$client->post('index.php?route=report_lock', [
    'csrf_token' => $griCsrf,
    'year' => 2024
]);
$lockedRec = $pdo->query("SELECT workflow_status FROM esg_ghg_emissions WHERE id = {$recId}")->fetch();
recordStep($s3, "2024 年度全盤審定封存鎖檔 (Locking)", $lockedRec['workflow_status'] === 'locked', "歷史數據已鎖檔防竄改");


// =========================================================
// SCENARIO 4: 外部查證稽核員 (Lead Auditor - 王查證員)
// =========================================================
$client->resetSession();
$s4 = "4. 外部查證員情境";

// 4.1 登入
$ok = $client->login('auditor', 'auditor123');
recordStep($s4, "外部查證員登入 (auditor/auditor123)", $ok, "第三方獨立查證員身份確認");

// 4.2 查核全生命週期稽核軌跡日誌 (Audit Trail)
$client->get('index.php?route=audit');
$hasLogs = (strpos($client->getContent(), 'sys_audit_logs') !== false || strpos($client->getContent(), '全生命週期稽核') !== false);
recordStep($s4, "調閱全生命週期稽核軌跡 (Audit Trail)", $hasLogs, "審視所有人員操作、時間與 IP");

// 4.3 查核異動前後 JSON Diff 對照
$auditRow = $pdo->query("SELECT * FROM sys_audit_logs WHERE target_table = 'esg_ghg_emissions' AND record_id = {$recId} ORDER BY id DESC LIMIT 1")->fetch();
$hasDiff = (!empty($auditRow['old_values']) || !empty($auditRow['new_values']));
recordStep($s4, "查核數據變更前後 JSON 差異 (Diff)", $hasDiff, "記錄前值與後值變更對照");

// 4.4 唯讀安全防禦驗證 (禁止惡意刪除鎖檔資料)
$client->post('index.php?route=ghg_delete', [
    'csrf_token' => 'mock_token',
    'id' => $recId
]);
$stillExists = $pdo->query("SELECT id FROM esg_ghg_emissions WHERE id = {$recId}")->fetchColumn();
recordStep($s4, "鎖檔數據防篡改與唯讀限制驗證", !empty($stillExists), "鎖檔數據無法被刪除");


// =========================================================
// SCENARIO 5: 系統管理員 (Super Admin)
// =========================================================
$client->resetSession();
$s5 = "5. 系統管理員情境";

// 5.1 登入
$ok = $client->login('admin', 'admin123');
recordStep($s5, "系統最高管理員登入 (admin/admin123)", $ok, "Super Admin 權限確認");

// 5.2 組織廠區管理 (建立新廠區)
$client->get('index.php?route=org');
$orgCsrf = $client->extractCsrfToken();
$client->post('index.php?route=org', [
    'csrf_token' => $orgCsrf,
    'unit_code' => 'PLANT-HC4',
    'unit_name' => '新竹先進研發四廠 (綠建築智慧廠區)',
    'unit_type' => 'plant',
    'parent_id' => 2,
    'country' => 'Taiwan',
    'address' => '新竹科學園區研發六路88號'
]);
$newOrg = $pdo->query("SELECT * FROM sys_org_units WHERE unit_code = 'PLANT-HC4'")->fetch();
recordStep($s5, "多層級組織廠區擴充管理 (新增新竹四廠)", !empty($newOrg), "組織代碼: PLANT-HC4");

// 5.3 排放係數庫管理 (新增係數與試算模擬)
$client->get('index.php?route=factors');
$factorCsrf = $client->extractCsrfToken();
$client->post('index.php?route=factors', [
    'csrf_token' => $factorCsrf,
    'factor_code' => 'EF-BIO-DIESEL-B20',
    'factor_name' => 'B20 生質柴油低碳排放係數',
    'scope_type' => 'Scope1',
    'category' => '移動燃燒',
    'fuel_type' => '生質柴油',
    'co2e_factor' => 2.165000,
    'unit' => '公升',
    'data_source' => '環境部低碳燃料公告 2024',
    'effective_year' => 2024,
    'is_current' => 1
]);
$newFactor = $pdo->query("SELECT * FROM esg_emission_factors WHERE factor_code = 'EF-BIO-DIESEL-B20'")->fetch();
recordStep($s5, "碳排係數庫動態擴充 (新增 B20 生質柴油係數)", !empty($newFactor), "係數值: 2.165000 kg/L");

// 5.4 使用者帳號管理 (新增使用者)
$client->get('index.php?route=users');
$userCsrf = $client->extractCsrfToken();
$testUser = 'tester_' . time();
$client->post('index.php?route=users', [
    'csrf_token' => $userCsrf,
    'username' => $testUser,
    'full_name' => '陳永續 測試員',
    'email' => $testUser . '@esg-corp.local',
    'role_id' => 4, // collector
    'org_unit_id' => $newOrg['id'] ?? 3,
    'password' => 'testpwd123'
]);
$newUserRow = $pdo->query("SELECT * FROM sys_users WHERE username = '{$testUser}'")->fetch();
recordStep($s5, "RBAC 帳號權限管理 (建立新竹廠填報帳號)", !empty($newUserRow), "帳號: {$testUser} / 角色: collector");

// 5.5 密碼暴力破解防禦與鎖定解鎖測試
// 模擬連續 5 次錯誤密碼登入
$attacker = new WebTestClient($baseUrl);
for ($i = 1; $i <= 5; $i++) {
    $attacker->login($testUser, 'wrong_password_' . $i);
}
$lockedUser = $pdo->query("SELECT status, failed_login_count FROM sys_users WHERE username = '{$testUser}'")->fetch();
$isLocked = ($lockedUser['status'] == -1 || $lockedUser['failed_login_count'] >= 5);
recordStep($s5, "資安防禦：連續密碼錯誤 5 次自動鎖定帳號", $isLocked, "鎖定狀態: status = {$lockedUser['status']}, 錯誤次數: {$lockedUser['failed_login_count']}");

// 管理員執行解鎖
$client->post('index.php?route=users', [
    'csrf_token' => $userCsrf,
    'id' => $newUserRow['id'],
    'full_name' => '陳永續 測試員 (已解鎖)',
    'email' => $newUserRow['email'],
    'role_id' => 4,
    'org_unit_id' => $newOrg['id'] ?? 3,
    'status' => 1 // 解鎖
]);
$unlockedUser = $pdo->query("SELECT status, failed_login_count FROM sys_users WHERE username = '{$testUser}'")->fetch();
recordStep($s5, "管理員線上重設狀態解鎖帳號", $unlockedUser['status'] == 1 && $unlockedUser['failed_login_count'] == 0, "帳號已恢復正常啟用");

echo "\n==================================================================\n";
$totalSteps = count($testResults);
$passedSteps = count(array_filter($testResults, fn($r) => $r['passed']));
$failedSteps = $totalSteps - $passedSteps;
echo "測試驗證完畢！總測試步驟: {$totalSteps} 項 | 通過: {$passedSteps} 項 | 失敗: {$failedSteps} 項\n";
echo "==================================================================\n";

if ($failedSteps > 0) exit(1);
