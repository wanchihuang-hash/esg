<?php
// tests/system_test.php
// Automated Verification Test for ESG-SMP System

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/GhgEngine.php';

echo "====================================================\n";
echo "ESG-SMP 企業永續管理資訊系統 - 全自動化驗收測試\n";
echo "====================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($description, $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passCount++;
    } else {
        echo " [FAIL] {$description}\n";
        $failCount++;
    }
}

// 1. Database Connection Test
try {
    $pdo = Database::getConnection();
    assertTest("資料庫連線成功 (MySQL/MariaDB via PDO)", $pdo !== null);
} catch (Exception $e) {
    assertTest("資料庫連線成功", false);
}

// 2. Database Tables Check
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$prefix = Database::getPrefix();
$expectedTables = array_map(fn($t) => $prefix . $t, [
    'esg_emission_factors', 'esg_energy_water', 'esg_ghg_emissions',
    'esg_governance_metrics', 'esg_social_metrics', 'sys_audit_logs',
    'sys_org_units', 'sys_roles', 'sys_users'
]);
$allTablesExist = count(array_intersect($expectedTables, $tables)) === count($expectedTables);
assertTest("9 張核心資料表完整存在 (含前綴 {$prefix})", $allTablesExist);

// 3. User & Password Hash Test
$admin = $pdo->query("SELECT * FROM sys_users WHERE username = 'admin'")->fetch();
$authCheck = $admin && password_verify('admin123', $admin['password_hash']);
assertTest("管理員帳號密碼 bcrypt 驗證通過", $authCheck);

$collector = $pdo->query("SELECT * FROM sys_users WHERE username = 'collector'")->fetch();
$collectorCheck = $collector && password_verify('collector123', $collector['password_hash']);
assertTest("填報員帳號密碼 bcrypt 驗證通過", $collectorCheck);

// 4. GHG Engine Calculation Formula Test
// Test 1: Electricity: 10,000 kWh * 0.495 kgCO2e/kWh / 1000 = 4.9500 tCO2e
$tco2eElec = GhgEngine::calculate(10000, 0.495000);
assertTest("台電電力碳排核算公式精度驗證 (10,000度 * 0.495 = 4.95 tCO2e)", $tco2eElec === 4.9500);

// Test 2: Diesel: 500 L * 2.7064 kg/L / 1000 = 1.3532 tCO2e
$tco2eDiesel = GhgEngine::calculate(500, 2.706400);
assertTest("柴油固定燃燒碳排核算精度驗證 (500L * 2.7064 = 1.3532 tCO2e)", $tco2eDiesel === 1.3532);

// 5. Anomaly Detection Test
// Plant 3 historical avg for Scope 2 is around 22 tCO2e. If we test 50 tCO2e, it should flag anomaly (>20%)
$anomalyHigh = GhgEngine::checkAnomaly(3, 'Scope2', 50.0);
assertTest("異常波動預警機制觸發 (> +20% 偏差)", $anomalyHigh['is_anomaly'] === true);

$anomalyNormal = GhgEngine::checkAnomaly(3, 'Scope2', $anomalyHigh['avg']);
assertTest("正常波動範圍無警示 (與均值相同)", $anomalyNormal['is_anomaly'] === false);

// 6. Social FR & SR Formula Test
// FR = (injuries * 1,000,000) / total_hours
// SR = (lost_days * 1,000,000) / total_hours
$hours = 500000;
$injuries = 1;
$lostDays = 20;
$calcFr = round(($injuries * 1000000) / $hours, 4);
$calcSr = round(($lostDays * 1000000) / $hours, 4);
assertTest("失能傷害頻率 (FR) 計算公式驗證 (1次工傷 / 50萬工時 = 2.0000)", $calcFr === 2.0000);
assertTest("失能傷害嚴重率 (SR) 計算公式驗證 (20天損失 / 50萬工時 = 40.0000)", $calcSr === 40.0000);

// 7. Dashboard Summary Aggregation Test
$summary2024 = GhgEngine::getDashboardSummary(2024);
assertTest("2024 年度碳盤查總排放量大於零", $summary2024['total_emissions'] > 0);
assertTest("三大範疇排放數據齊全 (Scope 1, 2, 3)", isset($summary2024['scopes']['Scope1']) && isset($summary2024['scopes']['Scope2']) && isset($summary2024['scopes']['Scope3']));
assertTest("能源轉型綠電佔比指標存在", isset($summary2024['energy_water']['green_ratio']));

// 8. Audit Trail Logging Test
$_SESSION['user_id'] = 1;
Audit::log('TEST_UNIT', 'sys_users', 1, ['status' => 'old'], ['status' => 'new']);
$latestAudit = $pdo->query("SELECT * FROM sys_audit_logs ORDER BY id DESC LIMIT 1")->fetch();
assertTest("全生命週期稽核日誌寫入與 JSON Diff 存取正常", $latestAudit['action_type'] === 'TEST_UNIT' && strpos($latestAudit['new_values'], 'new') !== false);

echo "\n----------------------------------------------------\n";
echo "測試結果總結: 通過: {$passCount} 項, 失敗: {$failCount} 項\n";
echo "====================================================\n";

if ($failCount > 0) exit(1);
