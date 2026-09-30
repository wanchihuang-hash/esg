<?php
/**
 * tests/config_test.php
 * Automated verification for Super Admin only configuration management
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/WebTestClient.php';

echo "====================================================\n";
echo "系統參數與可調整性 (Super Admin 專用) 驗證測試\n";
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

// 1. Database table existence and default configs
$threshold = Config::get('ghg_anomaly_threshold');
assertTest("讀取異常波動門檻值 (預設 20%)", $threshold == 20);

$baseYear = Config::get('ghg_base_year');
assertTest("讀取盤查基準年 (預設 2023)", $baseYear == 2023);

$reductionTarget = Config::get('ghg_reduction_target');
assertTest("讀取年度減碳目標 (預設 5.0%)", $reductionTarget == 5.0);

$categories = Config::getAllGrouped();
assertTest("包含 5 大配置類別 (ghg, energy, workflow, security, company)", count($categories) >= 5);

// 2. HTTP access testing with roles
$baseUrl = getenv('ESG_TEST_URL') ?: (is_dir('/home/u721999801') ? 'https://darksalmon-eagle-978314.hostingersite.com/wanchi3366' : 'http://localhost/ESG');
$client = new WebTestClient($baseUrl);

// Non-admin roles should be blocked
$rolesToBlock = ['collector', 'reviewer', 'lead', 'auditor'];
foreach ($rolesToBlock as $r) {
    $client->resetSession();
    $client->login($r, $r . '123');
    $client->get('index.php?route=configs');
    $blocked = (strpos($client->getContent(), '您沒有存取該模組的權限') !== false);
    assertTest("非管理員角色 [{$r}] 存取參數設定遭系統嚴格阻擋 (RBAC)", $blocked);
}

// Admin role should be allowed
$client->resetSession();
$client->login('admin', 'admin123');
$client->get('index.php?route=configs');
$allowed = (strpos($client->getContent(), '系統全域參數與可調整性配置') !== false);
assertTest("Super Admin (admin) 成功進入參數設定介面", $allowed);

// Admin updates parameters
$csrf = $client->extractCsrfToken();
$client->post('index.php?route=configs_update', [
    'csrf_token' => $csrf,
    'configs' => [
        'ghg_anomaly_threshold' => '25',
        'ghg_reduction_target' => '6.0'
    ]
]);

Config::clearCache();
$newThreshold = Config::get('ghg_anomaly_threshold');
$newTarget = Config::get('ghg_reduction_target');
assertTest("Super Admin 成功調整異常門檻為 25%，減碳目標為 6.0%", $newThreshold == 25 && $newTarget == 6.0);

// Restore to defaults
Config::set('ghg_anomaly_threshold', 20, 1);
Config::set('ghg_reduction_target', 5.0, 1);
assertTest("恢復預設參數 (20% / 5.0%)", Config::get('ghg_anomaly_threshold') == 20);

echo "\n----------------------------------------------------\n";
echo "測試結果總結: 通過: {$passCount} 項, 失敗: {$failCount} 項\n";
echo "====================================================\n";

if ($failCount > 0) exit(1);
