<?php
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Security.php';
require_once __DIR__ . '/../Audit.php';
require_once __DIR__ . '/../AiAssistant.php';

class AiController {
    public function index(): void {
        Auth::requireLogin();
        $status = ['enabled' => AiAssistant::settings()['enabled'], 'key_configured' => AiAssistant::hasApiKey(), 'model' => AiAssistant::settings()['model']];
        require __DIR__ . '/../../views/ai/index.php';
    }

    public function chat(): void {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=UTF-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) { http_response_code(405); echo json_encode(['ok' => false, 'message' => '請求方法或安全權杖無效'], JSON_UNESCAPED_UNICODE); return; }
        $result = AiAssistant::chat((string)($_POST['message'] ?? ''));
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function settings(): void {
        Auth::requireRole('admin');
        $cfg = AiAssistant::settings();
        require __DIR__ . '/../../views/ai/settings.php';
    }

    public function saveSettings(): void {
        Auth::requireRole('admin');
        Security::requirePost('ai_settings');
        try {
            $model = (string)($_POST['model'] ?? AiAssistant::DEFAULT_MODEL);
            if (!in_array($model, AiAssistant::MODELS, true)) throw new InvalidArgumentException('模型不在允許清單。');
            $base = trim((string)($_POST['base_url'] ?? AiAssistant::DEFAULT_BASE_URL));
            if (!filter_var($base, FILTER_VALIDATE_URL) || parse_url($base, PHP_URL_SCHEME) !== 'https') throw new InvalidArgumentException('API 網址必須使用 HTTPS。');
            $prompt = trim((string)($_POST['system_prompt'] ?? ''));
            if (mb_strlen($prompt, 'UTF-8') > 4000) throw new InvalidArgumentException('自訂提示詞不可超過 4000 字。');
            $maxTokens = Security::intInRange($_POST['max_output_tokens'] ?? 800, 100, 4000, '最大回覆 Token');
        } catch (Throwable $e) { set_flash('danger', $e->getMessage()); redirect('ai_settings'); }
        $adminId = Auth::id();
        Config::upsert('ai_enabled', 'AI 客服啟用', 'security', isset($_POST['enabled']) ? '1' : '0', 'string', $adminId, '是否啟用 ESG-SMP AI 客服');
        Config::upsert('ai_model', 'AI 模型', 'security', $model, 'string', $adminId, '僅允許 2026/5 後 GPT-6 模型');
        Config::upsert('ai_api_base_url', 'OpenAI API 網址', 'security', rtrim($base, '/'), 'string', $adminId, 'Responses API 的 v1 基底網址');
        Config::upsert('ai_system_prompt', 'AI 客服補充提示詞', 'security', $prompt, 'string', $adminId, '不可取代系統限制');
        Config::upsert('ai_max_output_tokens', '最大回覆 Token', 'security', (string)$maxTokens, 'number', $adminId, '單次回覆上限');
        $key = trim((string)($_POST['api_key'] ?? ''));
        if ($key !== '') Config::upsert('ai_api_key_enc', 'OpenAI API 金鑰（加密）', 'security', AiAssistant::encryptSecret($key), 'string', $adminId, 'AES-256-GCM 加密儲存，不在畫面回顯');
        Audit::log('UPDATE_AI_SETTINGS', 'sys_system_configs', null, null, ['model' => $model, 'api_key_changed' => $key !== '', 'enabled' => isset($_POST['enabled'])]);
        set_flash('success', 'AI 客服設定已更新。');
        redirect('ai_settings');
    }

    public function test(): void {
        Auth::requireRole('admin');
        Security::requirePost('ai_settings');
        $result = AiAssistant::testConnection([
            'api_key' => trim((string)($_POST['api_key'] ?? '')),
            'model' => (string)($_POST['model'] ?? ''),
            'base_url' => trim((string)($_POST['base_url'] ?? '')),
        ]);
        set_flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect('ai_settings');
    }
}
