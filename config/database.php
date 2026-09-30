<?php
// config/database.php

$isHostinger = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'hostingersite.com') !== false) 
                || (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'hostingersite.com') !== false)
                || (is_dir('/home/u721999801'));

if ($isHostinger) {
    return [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'u721999801_20260930',
        'username' => 'u721999801_20260930',
        'password' => 't122935116T@@',
        'charset' => 'utf8mb4',
        'prefix' => 'wanchi3366_',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
}

return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'esg_system',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
