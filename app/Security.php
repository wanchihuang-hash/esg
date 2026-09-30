<?php
// app/Security.php - shared request validation and upload hardening

require_once __DIR__ . '/Config.php';

class Security {
    public static function requirePost(string $fallbackRoute): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Method Not Allowed');
        }
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            set_flash('danger', '安全權杖無效，請重新操作');
            redirect($fallbackRoute);
        }
    }

    public static function intInRange(mixed $value, int $min, int $max, string $label): int {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        if ($filtered === false || $filtered < $min || $filtered > $max) {
            throw new InvalidArgumentException("{$label}格式或範圍不正確");
        }
        return $filtered;
    }

    public static function nonNegativeNumber(mixed $value, string $label): float {
        if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0) {
            throw new InvalidArgumentException("{$label}必須是非負數值");
        }
        return (float)$value;
    }

    public static function requiredText(mixed $value, int $maxLength, string $label): string {
        $text = trim((string)$value);
        if ($text === '' || mb_strlen($text, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException("{$label}不可空白且不得超過 {$maxLength} 字");
        }
        return $text;
    }

    /** Returns the relative saved path, null for no upload, or throws on invalid input. */
    public static function storeProofUpload(array $file): ?string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new RuntimeException('附件上傳失敗');
        }

        $maxBytes = max(1, (int)Config::get('workflow_max_upload_size_mb', 10)) * 1024 * 1024;
        if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > $maxBytes) {
            throw new RuntimeException('附件大小超過系統限制');
        }

        $configured = strtolower((string)Config::get('workflow_allowed_file_types', 'pdf,jpg,jpeg,png,xlsx,csv'));
        $allowedExtensions = array_values(array_filter(array_map('trim', explode(',', $configured))));
        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('不支援的附件格式');
        }

        $allowedMimes = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
            'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        ];
        if (!isset($allowedMimes[$extension])) {
            throw new RuntimeException('附件格式未受系統支援');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMimes[$extension], true)) {
            throw new RuntimeException('附件內容與副檔名不符');
        }

        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true)) {
            throw new RuntimeException('附件儲存目錄無法建立');
        }
        $filename = 'proof_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
            throw new RuntimeException('附件儲存失敗');
        }
        return 'uploads/' . $filename;
    }
}
