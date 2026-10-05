<?php

declare(strict_types=1);

$configFile = dirname(__DIR__) . '/config.php';

if (!is_file($configFile)) {
    http_response_code(500);
    exit('config.php belum dibuat. Copy config.example.php menjadi config.php.');
}

$config = require $configFile;

$timezone = (string) ($config['app']['timezone'] ?? 'Asia/Jakarta');

if (!in_array($timezone, timezone_identifiers_list(), true)) {
    $timezone = 'Asia/Jakarta';
}

date_default_timezone_set($timezone);

return $config;
