<?php
// app/Database.php

class CustomPDO extends PDO {
    private string $prefix = '';

    public function setPrefix(string $prefix): void {
        $this->prefix = $prefix;
    }

    public function getPrefix(): string {
        return $this->prefix;
    }

    public function prepare($statement, $options = []) {
        if ($this->prefix !== '') {
            $statement = $this->applyPrefix($statement);
        }
        return parent::prepare($statement, $options);
    }

    public function query($statement, ...$args) {
        if ($this->prefix !== '') {
            $statement = $this->applyPrefix($statement);
        }
        return parent::query($statement, ...$args);
    }

    public function exec($statement) {
        if ($this->prefix !== '') {
            $statement = $this->applyPrefix($statement);
        }
        return parent::exec($statement);
    }

    private function applyPrefix(string $sql): string {
        $tables = [
            'sys_org_units', 'sys_roles', 'sys_users', 'esg_emission_factors',
            'esg_ghg_emissions', 'esg_energy_water', 'esg_social_metrics',
            'esg_governance_metrics', 'sys_audit_logs', 'sys_system_configs'
        ];
        foreach ($tables as $t) {
            // Negative lookbehind ensures we never double-prefix if already prefixed
            $sql = preg_replace('/(?<!' . preg_quote($this->prefix, '/') . ')\b' . preg_quote($t, '/') . '\b/', $this->prefix . $t, $sql);
        }
        return $sql;
    }
}

class Database {
    private static ?CustomPDO $instance = null;

    public static function getConnection(): CustomPDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = ($config['host'] === 'localhost')
                ? "mysql:host=localhost;dbname={$config['dbname']};charset={$config['charset']}"
                : "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
            
            $maxRetries = 3;
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    self::$instance = new CustomPDO($dsn, $config['username'], $config['password'], $config['options']);
                    if (!empty($config['prefix'])) {
                        self::$instance->setPrefix($config['prefix']);
                    }
                    break;
                } catch (PDOException $e) {
                    if ($attempt === $maxRetries) {
                        error_log('Database connection failed after retries: ' . $e->getMessage());
                        http_response_code(503);
                        exit('系統暫時無法連線至資料庫，請稍後再試');
                    }
                    usleep(150000); // 150ms backoff
                }
            }
        }
        return self::$instance;
    }

    public static function getPrefix(): string {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$instance ? self::$instance->getPrefix() : '';
    }
}
