<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| FOTOGRAFER — Configuration
|--------------------------------------------------------------------------
|
| 1. Copy file ini menjadi: config.php
| 2. Ganti base_url sesuai domain/subdomain.
| 3. Atur satu atau beberapa PIN admin.
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
    | Semua PIN di bawah dapat digunakan untuk masuk ke panel admin.
    |
    */

    'admin' => [
        'pins' => [
            '829341',
            '010101',
            '654321',
        ],
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
        'max_size_mb' => 100,

        'allowed_mime' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'video/mp4',
            'video/quicktime',
            'video/webm',
        ],
    ],

];
