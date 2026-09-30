<?php
// index.php - Front Controller

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/Auth.php';

// Controllers
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/DashboardController.php';
require_once __DIR__ . '/app/Controllers/GhgController.php';
require_once __DIR__ . '/app/Controllers/EnergyWaterController.php';
require_once __DIR__ . '/app/Controllers/SocialController.php';
require_once __DIR__ . '/app/Controllers/GovernanceController.php';
require_once __DIR__ . '/app/Controllers/ReportController.php';
require_once __DIR__ . '/app/Controllers/FactorController.php';
require_once __DIR__ . '/app/Controllers/AuditController.php';
require_once __DIR__ . '/app/Controllers/OrgController.php';
require_once __DIR__ . '/app/Controllers/UserController.php';
require_once __DIR__ . '/app/Controllers/ConfigController.php';
require_once __DIR__ . '/app/Controllers/ApiController.php';
require_once __DIR__ . '/app/Controllers/AiController.php';

$route = $_GET['route'] ?? '';

// Default route
if (empty($route)) {
    $route = Auth::check() ? 'dashboard' : 'login';
}

switch ($route) {
    // Auth
    case 'login':
        $ctrl = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->handleLogin();
        } else {
            $ctrl->showLogin();
        }
        break;

    case 'logout':
        (new AuthController())->handleLogout();
        break;

    // Dashboard
    case 'dashboard':
        (new DashboardController())->index();
        break;

    // Internal AI helpdesk
    case 'ai_assistant':
        (new AiController())->index();
        break;
    case 'ai_chat':
        (new AiController())->chat();
        break;
    case 'ai_settings':
        (new AiController())->settings();
        break;
    case 'ai_settings_save':
        (new AiController())->saveSettings();
        break;
    case 'ai_test':
        (new AiController())->test();
        break;

    // Built-in operator manual
    case 'manual':
        Auth::requireLogin();
        require __DIR__ . '/views/manual/index.php';
        break;

    // GHG Module
    case 'ghg':
        (new GhgController())->index();
        break;
    case 'ghg_create':
        (new GhgController())->create();
        break;
    case 'ghg_store':
        (new GhgController())->store();
        break;
    case 'ghg_edit':
        (new GhgController())->edit();
        break;
    case 'ghg_update':
        (new GhgController())->update();
        break;
    case 'ghg_workflow':
        (new GhgController())->workflow();
        break;
    case 'ghg_delete':
        (new GhgController())->delete();
        break;

    // Energy & Water
    case 'energy_water':
        (new EnergyWaterController())->index();
        break;
    case 'energy_water_create':
        (new EnergyWaterController())->create();
        break;
    case 'energy_water_store':
        (new EnergyWaterController())->store();
        break;
    case 'energy_water_edit':
        (new EnergyWaterController())->edit();
        break;
    case 'energy_water_delete':
        (new EnergyWaterController())->delete();
        break;

    // Social
    case 'social':
        $ctrl = new SocialController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->store();
        } else {
            $ctrl->index();
        }
        break;

    // Governance
    case 'governance':
        $ctrl = new GovernanceController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->store();
        } else {
            $ctrl->index();
        }
        break;

    // Reports & Disclosures
    case 'report_gri':
        (new ReportController())->gri();
        break;
    case 'report_export':
        (new ReportController())->exportCsv();
        break;
    case 'report_lock':
        (new ReportController())->lockYear();
        break;

    // Emission Factors
    case 'factors':
        $ctrl = new FactorController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $ctrl->update();
            } else {
                $ctrl->store();
            }
        } else {
            $ctrl->index();
        }
        break;

    // Audit Trail
    case 'audit':
        (new AuditController())->index();
        break;

    // Organizations
    case 'org':
        $ctrl = new OrgController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $ctrl->update();
            } else {
                $ctrl->store();
            }
        } else {
            $ctrl->index();
        }
        break;

    // Users
    case 'users':
        $ctrl = new UserController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $ctrl->update();
            } else {
                $ctrl->store();
            }
        } else {
            $ctrl->index();
        }
        break;

    // System Configurations (Super Admin Only)
    case 'configs':
        (new ConfigController())->index();
        break;
    case 'configs_update':
        (new ConfigController())->update();
        break;

    // APIs
    case 'api/factor-info':
        (new ApiController())->factorInfo();
        break;
    case 'api/calculate':
        (new ApiController())->calculate();
        break;
    case 'api/dashboard-summary':
        (new ApiController())->dashboardSummary();
        break;
    case 'api/emissions':
        (new ApiController())->emissions();
        break;

    default:
        http_response_code(404);
        echo "404 找不到頁面 - <a href='index.php'>返回首頁</a>";
        break;
}
