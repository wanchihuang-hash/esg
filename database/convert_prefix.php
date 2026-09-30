<?php
$sourceSql = file_get_contents(__DIR__ . '/esg_dump_local.sql');

$prefix = 'wanchi3366_';
$tables = [
    'sys_org_units',
    'sys_roles',
    'sys_users',
    'esg_emission_factors',
    'esg_ghg_emissions',
    'esg_energy_water',
    'esg_social_metrics',
    'esg_governance_metrics',
    'sys_audit_logs',
    'sys_system_configs'
];

$transformedSql = $sourceSql;

// Replace backtick quoted table names: `sys_users` -> `wanchi3366_sys_users`
foreach ($tables as $table) {
    $transformedSql = preg_replace('/`' . preg_quote($table, '/') . '`/', '`' . $prefix . $table . '`', $transformedSql);
    // Also replace references in target_table inserts
    $transformedSql = preg_replace('/\'\b' . preg_quote($table, '/') . '\b\'/', '\'' . $prefix . $table . '\'', $transformedSql);
}

// Remove any USE or CREATE DATABASE statements if present
$transformedSql = preg_replace('/CREATE DATABASE.*?;/i', '', $transformedSql);
$transformedSql = preg_replace('/USE `.*?`;/i', '', $transformedSql);

file_put_contents(__DIR__ . '/wanchi3366_database.sql', $transformedSql);
echo "wanchi3366_database.sql generated successfully. Length: " . strlen($transformedSql) . "\n";
