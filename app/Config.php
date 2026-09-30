<?php
// app/Config.php

require_once __DIR__ . '/Database.php';

class Config {
    private static array $cache = [];

    public static function clearCache(): void {
        self::$cache = [];
    }

    public static function get(string $key, mixed $default = null): mixed {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT config_value, value_type FROM sys_system_configs WHERE config_key = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch();

            if (!$row) {
                return $default;
            }

            $val = match($row['value_type']) {
                'number' => is_numeric($row['config_value']) ? (strpos($row['config_value'], '.') !== false ? (float)$row['config_value'] : (int)$row['config_value']) : (float)$row['config_value'],
                'boolean' => (bool)$row['config_value'],
                'json' => json_decode($row['config_value'], true),
                default => (string)$row['config_value']
            };

            self::$cache[$key] = $val;
            return $val;
        } catch (Exception $e) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value, ?int $userId = null): bool {
        $pdo = Database::getConnection();
        $strValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        $stmt = $pdo->prepare("UPDATE sys_system_configs SET config_value = ?, updated_by = ?, updated_at = NOW() WHERE config_key = ?");
        $res = $stmt->execute([$strValue, $userId, $key]);
        self::$cache[$key] = $value;
        return $res;
    }

    /** Create or update a system configuration without requiring a migration. */
    public static function upsert(string $key, string $name, string $category, mixed $value, string $type = 'string', ?int $userId = null, string $description = ''): bool {
        $pdo = Database::getConnection();
        $strValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        $stmt = $pdo->prepare("\n            INSERT INTO sys_system_configs\n                (config_key, config_name, config_category, config_value, value_type, description, updated_by, updated_at)\n            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())\n            ON DUPLICATE KEY UPDATE\n                config_name = VALUES(config_name), config_category = VALUES(config_category),\n                config_value = VALUES(config_value), value_type = VALUES(value_type),\n                description = VALUES(description), updated_by = VALUES(updated_by), updated_at = NOW()\n        ");
        $ok = $stmt->execute([$key, $name, $category, $strValue, $type, $description, $userId]);
        self::$cache[$key] = $value;
        return $ok;
    }

    public static function getAllGrouped(): array {
        $pdo = Database::getConnection();
        $rows = $pdo->query("SELECT * FROM sys_system_configs ORDER BY config_category ASC, id ASC")->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['config_category']][] = $r;
        }
        return $grouped;
    }
}
