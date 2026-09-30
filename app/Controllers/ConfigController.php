<?php
// app/Controllers/ConfigController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Config.php';
require_once __DIR__ . '/../Audit.php';

class ConfigController {
    public function index() {
        // Strictly only super_admin ('admin') can view and adjust
        Auth::requireRole('admin');
        $groupedConfigs = Config::getAllGrouped();
        require __DIR__ . '/../../views/config/index.php';
    }

    public function update() {
        // Strictly only super_admin ('admin') can adjust
        Auth::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('configs');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效');
            redirect('configs');
        }

        $configs = $_POST['configs'] ?? [];
        if (!is_array($configs)) {
            redirect('configs');
        }

        $pdo = Database::getConnection();
        $adminId = Auth::id();
        $updatedCount = 0;
        $diffChanges = [];

        foreach ($configs as $key => $newVal) {
            $stmt = $pdo->prepare("SELECT config_value, config_name FROM sys_system_configs WHERE config_key = ?");
            $stmt->execute([$key]);
            $oldRow = $stmt->fetch();

            if ($oldRow && $oldRow['config_value'] != $newVal) {
                Config::set($key, trim($newVal), $adminId);
                $diffChanges[$key] = [
                    'name' => $oldRow['config_name'],
                    'old' => $oldRow['config_value'],
                    'new' => trim($newVal)
                ];
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            Audit::log('UPDATE_CONFIG', 'sys_system_configs', null, null, $diffChanges);
            set_flash('success', "系統參數已成功更新！共修改 {$updatedCount} 項配置，已即時生效。");
        } else {
            set_flash('info', "未偵測到參數變更。");
        }

        redirect('configs');
    }
}
