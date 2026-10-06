<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| FOTOGRAFER — Configuration
|--------------------------------------------------------------------------
|
| 1. Copy file ini menjadi: config.php
| 2. Ganti base_url sesuai domain/subdomain.
| 3. Ganti username admin.
| 4. Isi password_hash dengan hash password admin.
|
| config.php tidak ikut Git karena sudah ada di .gitignore.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    'app' => [
        'name' => 'FOTOGRAFER',
        'base_url' => 'https://foto.domainkamu.com',
        'timezone' => 'Asia/Jakarta',
    ],

    /*
    |--------------------------------------------------------------------------
    | Administrator
    |--------------------------------------------------------------------------
    |
    | Buat hash:
    | php -r "echo password_hash('password-kamu', PASSWORD_DEFAULT), PHP_EOL;"
    |
    */

    'admin' => [
        'username' => 'admin',
        'password_hash' => 'GANTI_DENGAN_HASH_PASSWORD',
        'session_name' => 'fotografer_admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Penyimpanan Foto
    |--------------------------------------------------------------------------
    */

    'storage' => [
        'path' => __DIR__ . '/storage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload
    |--------------------------------------------------------------------------
    */

    'upload' => [
        'max_size_mb' => 30,

        'allowed_mime' => [
            'image/jpeg',
            'image/png',
        ],
    ],

];
