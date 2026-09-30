<?php
// app/Audit.php

require_once __DIR__ . '/Database.php';

class Audit {
    public static function log(string $actionType, string $targetTable, ?int $recordId = null, ?array $oldValues = null, ?array $newValues = null): void {
        try {
            $pdo = Database::getConnection();
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                // The schema requires a user id. Never attribute anonymous or
                // background actions to an unrelated administrator account.
                error_log('Audit log skipped: no authenticated user');
                return;
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System';

            $prefixedTable = (Database::getPrefix() !== '' && !str_starts_with($targetTable, Database::getPrefix())) ? Database::getPrefix() . $targetTable : $targetTable;

            $stmt = $pdo->prepare("
                INSERT INTO sys_audit_logs 
                (user_id, action_type, target_table, record_id, old_values, new_values, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                strtoupper($actionType),
                $prefixedTable,
                $recordId,
                $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                $ip,
                substr($ua, 0, 250)
            ]);
        } catch (Exception $e) {
            // Silently log or ignore audit log errors so business transaction doesn't break
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
}
