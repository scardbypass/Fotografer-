<?php

declare(strict_types=1);

/**
 * FOTOGRAFER
 * Copy file ini menjadi config.php, lalu sesuaikan nilainya.
 *
 * Untuk membuat password hash:
 * php -r "echo password_hash('PASSWORD_KAMU', PASSWORD_DEFAULT), PHP_EOL;"
 */
return [
    'app' => [
        'name' => 'FOTOGRAFER',
        'base_url' => 'https://foto.example.com',
        'timezone' => 'Asia/Jakarta',
    ],

    'admin' => [
        'username' => 'admin',
        'password_hash' => 'GANTI_DENGAN_PASSWORD_HASH',
        'session_name' => 'fotografer_admin',
    ],

    'storage' => __DIR__ . '/storage',

    'upload' => [
        'max_size_mb' => 30,
        'allowed_mime' => [
            'image/jpeg',
            'image/png',
        ],
    ],
];
