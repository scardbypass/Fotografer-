# FOTOGRAFER — Website & API

Website penerima upload foto dari APK dan galeri pelanggan. Branch ini ditujukan untuk PHP 8.1+ dan dapat diuji lebih dulu di shared hosting/cPanel.

## 1. Kebutuhan hosting

- PHP 8.1 atau lebih baru
- extension PHP `fileinfo`
- HTTPS/SSL aktif
- permission tulis PHP pada folder `storage/`
- batas upload hosting minimal sama dengan `upload.max_size_mb`

Untuk foto maksimal 30 MB, atur hosting kurang lebih:

```ini
upload_max_filesize = 32M
post_max_size = 35M
max_execution_time = 120
memory_limit = 128M
```

Nilai yang tersedia tergantung provider hosting.

## 2. Upload website ke hosting

Download/clone **branch `website`**, lalu upload seluruh isi branch ke document root subdomain, misalnya:

```text
public_html/foto/
├── admin/
├── api/
├── assets/
├── lib/
├── storage/
├── config.example.php
├── index.php
└── photo.php
```

Jangan membuat folder `website/` lagi di dalam document root.

## 3. Membuat config.php

Copy:

```text
config.example.php → config.php
```

Buat password hash dengan PHP:

```bash
php -r "echo password_hash('PASSWORD_KAMU', PASSWORD_DEFAULT), PHP_EOL;"
```

Kemudian edit bagian berikut:

```php
'app' => [
    'name' => 'FOTOGRAFER',
    'base_url' => 'https://foto.domainkamu.com',
    'timezone' => 'Asia/Jakarta',
],

'admin' => [
    'username' => 'admin',
    'password_hash' => 'HASIL_PASSWORD_HASH',
    'session_name' => 'fotografer_admin',
],
```

`config.php` di-ignore Git dan tidak boleh dipush ke repository.

## 4. Permission storage

Umumnya gunakan permission folder `755`. Jika PHP hosting tidak dapat menulis, ikuti permission yang direkomendasikan provider. Hindari `777` kecuali benar-benar diperlukan untuk diagnosis sementara.

File `storage/.htaccess` memblokir akses HTTP langsung pada Apache. Foto publik tetap dilayani melalui `photo.php`.

## 5. Login admin

Buka:

```text
https://foto.domainkamu.com/admin/login.php
```

Login menggunakan username/password dari `config.php`.

Login menggunakan session, password hash, regenerasi session ID, HttpOnly cookie, SameSite cookie, CSRF token, dan pembatasan percobaan login sederhana.

## 6. Membuat event

1. Login admin.
2. Isi nama event.
3. Pilih **Private** atau **Public**.
4. Klik **Buat event**.
5. Copy Upload Token yang muncul.
6. Simpan token ke konfigurasi APK.

Token asli hanya ditampilkan saat dibuat/regenerate. Server hanya menyimpan SHA-256 hash token.

## 7. Menguji API upload tanpa APK

Contoh menggunakan curl:

```bash
curl -X POST \
  -H "Authorization: Bearer ft_TOKEN_KAMU" \
  -F "photo=@foto.jpg" \
  https://foto.domainkamu.com/api/upload.php
```

Respons berhasil berbentuk JSON dengan `"ok": true`.

Jika mendapat HTTP 413, naikkan `upload_max_filesize` dan `post_max_size` pada hosting.

## 8. Membuka galeri

Untuk event public:

```text
https://foto.domainkamu.com/?event=slug-event
```

Event private menolak galeri dan akses langsung melalui `photo.php`.

## 9. Checklist sebelum online

- HTTPS aktif.
- `config.php` sudah dibuat dan password default tidak digunakan.
- `storage/` writable oleh PHP.
- `storage/.htaccess` ter-upload.
- Directory listing storage tidak dapat dibuka.
- Login admin berhasil.
- Event dapat dibuat.
- Upload token berhasil menerima JPEG.
- Token salah menghasilkan HTTP 401.
- Event private tidak dapat dibuka publik.
- Regenerate token membuat token lama tidak berlaku.

## 10. Shared hosting vs VPS

Shared hosting cukup untuk testing dan event kecil. VPS lebih cocok jika volume foto tinggi, membutuhkan thumbnail worker, storage besar, queue, backup otomatis, atau kontrol Nginx/PHP-FPM.

Pada Nginx, `.htaccess` tidak berlaku. Saat pindah VPS, blok akses langsung ke `/storage/` pada konfigurasi Nginx.

## Catatan arsitektur

Metadata event saat ini disimpan di JSON agar setup shared hosting sederhana. Untuk banyak tablet/upload paralel dan pemakaian produksi besar, migrasi ke SQLite/MySQL disarankan agar update counter/metadata lebih aman terhadap concurrency.
