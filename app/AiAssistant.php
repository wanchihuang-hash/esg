<?php
// app/AiAssistant.php - bounded OpenAI Responses API integration for the ESG helpdesk

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';

class AiAssistant {
    public const MODELS = ['gpt-6-astra', 'gpt-6.1-sol', 'gpt-6-luna'];
    public const DEFAULT_MODEL = 'gpt-6.1-sol';
    public const DEFAULT_BASE_URL = 'https://api.openai.com/v1';
    public const WARNING_LIMIT = 3;
    public const SECURITY_COOLDOWN_SECONDS = 180;

    public static function settings(): array {
        return [
            'enabled' => in_array(strtolower((string)Config::get('ai_enabled', '0')), ['1', 'true', 'yes', 'on'], true),
            'model' => (string)Config::get('ai_model', self::DEFAULT_MODEL),
            'base_url' => rtrim((string)Config::get('ai_api_base_url', self::DEFAULT_BASE_URL), '/'),
            'api_key_enc' => (string)Config::get('ai_api_key_enc', ''),
            'system_prompt' => (string)Config::get('ai_system_prompt', ''),
            'max_output_tokens' => max(100, min(4000, (int)Config::get('ai_max_output_tokens', 800))),
            'warning_limit' => self::WARNING_LIMIT,
        ];
    }

    public static function hasApiKey(): bool {
        return self::decryptSecret(self::settings()['api_key_enc']) !== '';
    }

    public static function encryptSecret(string $plain): string {
        $plain = trim($plain);
        if ($plain === '') return '';
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) throw new RuntimeException('API 金鑰加密失敗');
        return 'v1:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($cipher);
    }

    public static function decryptSecret(string $stored): string {
        if ($stored === '') return '';
        $parts = explode(':', $stored, 4);
        if (count($parts) !== 4 || $parts[0] !== 'v1') {
            // Backward-compatible read for an operator that already stored a key.
            return str_starts_with($stored, 'sk-') ? trim($stored) : '';
        }
        $plain = openssl_decrypt(base64_decode($parts[3], true), 'aes-256-gcm', self::encryptionKey(), OPENSSL_RAW_DATA, base64_decode($parts[1], true), base64_decode($parts[2], true));
        return $plain === false ? '' : $plain;
    }

    public static function warningCount(): int { return (int)($_SESSION['ai_warning_count'] ?? 0); }
    public static function securityCooldownRemaining(): int {
        $until = (int)($_SESSION['ai_security_disconnected_until'] ?? 0);
        if ($until <= time()) {
            unset($_SESSION['ai_security_disconnected_until']);
            return 0;
        }
        return $until - time();
    }
    public static function disconnected(): bool { return !empty($_SESSION['ai_disconnected']) || self::securityCooldownRemaining() > 0; }
    public static function resetConversation(): void { unset($_SESSION['ai_warning_count'], $_SESSION['ai_disconnected'], $_SESSION['ai_security_disconnected_until']); }

    public static function chat(string $question): array {
        $question = trim($question);
        $cooldown = self::securityCooldownRemaining();
        if ($cooldown > 0) return self::securityDisconnectResponse($cooldown, false);
        if (!empty($_SESSION['ai_disconnected'])) return ['ok' => false, 'disconnected' => true, 'message' => '本次 AI 客服連線已結束，請重新登入或聯絡系統管理員。'];
        if ($question === '' || mb_strlen($question, 'UTF-8') > 4000) return ['ok' => false, 'message' => '請輸入 1 至 4000 字的系統問題。'];
        if (self::isSecurityQuestion($question)) return self::securityDisconnectResponse(self::SECURITY_COOLDOWN_SECONDS, true);
        if (self::looksClearlyOutOfScope($question)) return self::warningResponse();

        $cfg = self::settings();
        if (!$cfg['enabled'] || !self::hasApiKey()) return ['ok' => false, 'message' => 'AI 客服尚未啟用或尚未設定 API 金鑰，請聯絡系統管理員。'];
        if (!in_array($cfg['model'], self::MODELS, true)) return ['ok' => false, 'message' => 'AI 模型設定不在允許清單中。'];

        $result = self::request($cfg, $question);
        if (!$result['ok']) return $result;
        $answer = trim($result['text']);
        if (str_starts_with($answer, '[OUT_OF_SCOPE]')) return self::warningResponse();
        return ['ok' => true, 'message' => $answer, 'warnings' => self::warningCount()];
    }

    public static function testConnection(array $overrides = []): array {
        $cfg = self::settings();
        if (!empty($overrides['api_key'])) $cfg['api_key_enc'] = self::encryptSecret((string)$overrides['api_key']);
        if (!empty($overrides['model'])) $cfg['model'] = (string)$overrides['model'];
        if (!empty($overrides['base_url'])) $cfg['base_url'] = rtrim((string)$overrides['base_url'], '/');
        if (self::decryptSecret($cfg['api_key_enc']) === '') return ['ok' => false, 'message' => '尚未設定 API 金鑰。'];
        if (!in_array($cfg['model'], self::MODELS, true)) return ['ok' => false, 'message' => '模型不在允許清單。'];
        $result = self::request($cfg, '請只回覆「連線測試成功」。', true);
        return $result['ok'] ? ['ok' => true, 'message' => 'OpenAI API 連線成功，模型回覆正常。'] : $result;
    }

    private static function request(array $cfg, string $question, bool $test = false): array {
        if (!function_exists('curl_init')) return ['ok' => false, 'message' => 'PHP cURL 擴充功能未啟用。'];
        $context = self::knowledgeContext();
        $defaultPrompt = '你是 ESG-SMP 企業 ESG 永續管理資訊系統的內部 AI 客服。你只能回答本系統的操作方式、角色權限、工作流程、報表、計算公式、資料庫結構與系統目前提供的統計資訊。不可回答政治、醫療、法律、投資、天氣、一般聊天、其他軟體或任何與本系統無關的問題。若問題不在範圍，回答必須以 [OUT_OF_SCOPE] 開頭，接著簡短說明只能協助 ESG-SMP。只能根據提供的系統內容回答；不確定時明確說明無法從系統資料確認，不得臆測。不要輸出密碼、API 金鑰、password_hash、Session 或其他秘密。使用繁體中文、條列清楚、直接給操作步驟。';
        $instructions = trim(($cfg['system_prompt'] !== '' ? $cfg['system_prompt'] . "\n" : '') . $defaultPrompt . "\n\n系統知識庫：\n" . $context);
        $payload = [
            'model' => $cfg['model'],
            'instructions' => $instructions,
            'input' => $question,
            'max_output_tokens' => $cfg['max_output_tokens'],
            'store' => false,
        ];
        $url = $cfg['base_url'] . '/responses';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . self::decryptSecret($cfg['api_key_enc']), 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 35,
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno || $body === false) return ['ok' => false, 'message' => 'AI 服務連線失敗，請稍後再試。'];
        $json = json_decode($body, true);
        if ($status < 200 || $status >= 300) {
            error_log('AI API error ' . $status . ': ' . substr((string)$body, 0, 500));
            return ['ok' => false, 'message' => $test ? 'API 回應錯誤，請檢查金鑰、模型與 API 網址。' : 'AI 服務暫時無法回覆，請稍後再試。'];
        }
        $text = (string)($json['output_text'] ?? '');
        if ($text === '' && !empty($json['output'])) {
            foreach ($json['output'] as $item) foreach (($item['content'] ?? []) as $content) if (($content['type'] ?? '') === 'output_text') $text .= (string)($content['text'] ?? '');
        }
        return trim($text) !== '' ? ['ok' => true, 'text' => trim($text)] : ['ok' => false, 'message' => 'AI 回傳空白內容，請稍後再試。'];
    }

    private static function warningResponse(): array {
        $_SESSION['ai_warning_count'] = self::warningCount() + 1;
        if (self::warningCount() >= self::WARNING_LIMIT) {
            $_SESSION['ai_disconnected'] = true;
            return ['ok' => false, 'disconnected' => true, 'warnings' => self::warningCount(), 'message' => '您已多次提出非本系統問題，AI 客服已中止本次連線。'];
        }
        return ['ok' => false, 'warning' => true, 'warnings' => self::warningCount(), 'message' => '抱歉，我只能協助 ESG-SMP 的操作、說明與資料庫相關問題。這是第 ' . self::warningCount() . ' 次範圍警告。'];
    }

    private static function securityDisconnectResponse(int $seconds, bool $newIncident): array {
        if ($newIncident) $_SESSION['ai_security_disconnected_until'] = time() + self::SECURITY_COOLDOWN_SECONDS;
        $remaining = self::securityCooldownRemaining();
        return [
            'ok' => false,
            'disconnected' => true,
            'security_disconnect' => true,
            'cooldown_seconds' => $remaining > 0 ? $remaining : $seconds,
            'message' => '嚴重警告：本客服不處理資安、漏洞、滲透、攻擊或其他安全測試問題。為保護系統，AI 客服已立即斷線，180 秒後才可重新連線。'
        ];
    }

    private static function isSecurityQuestion(string $q): bool {
        return (bool)preg_match('/(資安|資訊安全|網路安全|網絡安全|漏洞|弱點|滲透|滲測|滲透測試|駭客|黑客|攻擊|入侵|惡意程式|木馬|病毒|勒索軟體|社交工程|釣魚|提權|權限提升|密碼破解|暴力破解|安全測試|漏洞掃描|弱點掃描|SQL\s*Injection|SQL注入|XSS|CSRF|SSRF|RCE|DDoS|ransomware|malware|exploit|payload|reverse\s*shell|backdoor|API\s*key|token\s*安全)/iu', $q);
    }

    private static function looksClearlyOutOfScope(string $q): bool {
        return (bool)preg_match('/(天氣|股票|股價|投資建議|買房|食譜|醫療診斷|法律訴訟|選舉|政治|足球|籃球|電影推薦|寫程式|翻譯英文|旅遊行程|加密貨幣)/u', $q);
    }

    private static function knowledgeContext(): string {
        try {
            $pdo = Database::getConnection();
            $tables = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME")->fetchAll();
            $lines = ['模組：戰情室、GHG 碳盤查、能源與水資源、社會指標、公司治理、GRI 報告、排放係數、組織、使用者、稽核日誌、系統參數。', '工作流：GHG 為 draft → pending → approved/rejected → locked；正式 KPI 與報表只統計 approved／locked。', '公式：tCO2e = 活動量 × 排放係數 ÷ 1000；FR = 失能傷害次數 × 1,000,000 ÷ 總工時；SR = 損失工作日 × 1,000,000 ÷ 總工時。', '資料表與目前筆數：'];
            foreach ($tables as $t) $lines[] = $t['TABLE_NAME'] . ' (' . (int)$t['TABLE_ROWS'] . ' 筆)';
            $lines[] = '目前 GHG 年度／狀態彙總（僅統計數字，不含個資）：';
            $ghg = $pdo->query("SELECT period_year, workflow_status, COUNT(*) AS records, ROUND(SUM(calculated_tco2e), 4) AS tco2e FROM esg_ghg_emissions GROUP BY period_year, workflow_status ORDER BY period_year, workflow_status")->fetchAll();
            foreach ($ghg as $row) $lines[] = $row['period_year'] . ' ' . $row['workflow_status'] . ': ' . $row['records'] . ' 筆，' . $row['tco2e'] . ' tCO2e';
            $roles = $pdo->query("SELECT role_code, role_name FROM sys_roles ORDER BY id")->fetchAll();
            $lines[] = '角色：' . implode('、', array_map(static fn($r) => $r['role_code'] . '=' . $r['role_name'], $roles));
            $cols = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION")->fetchAll();
            $group = [];
            foreach ($cols as $c) $group[$c['TABLE_NAME']][] = $c['COLUMN_NAME'] . ':' . $c['COLUMN_TYPE'];
            $lines[] = '資料表欄位（不含密碼值）：';
            foreach ($group as $table => $fields) $lines[] = $table . ' [' . implode(', ', $fields) . ']';
            return implode("\n", $lines);
        } catch (Throwable $e) {
            return '目前無法讀取資料庫即時摘要；仍可回答一般系統操作。';
        }
    }

    private static function encryptionKey(): string {
        $source = (string)(getenv('ESG_AI_ENCRYPTION_KEY') ?: getenv('ESG_APP_KEY') ?: 'change-this-esg-ai-key-in-production');
        return hash('sha256', $source, true);
    }
}
