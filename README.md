# FOTOGRAFER — Website & API

Branch ini langsung berisi website/API di root dan cocok untuk **shared hosting PHP 8+** maupun VPS.

## Instalasi shared hosting

1. Upload isi branch `website` ke folder domain/subdomain.
2. Copy `config.example.php` menjadi `config.php`.
3. Buat password hash:
   ```bash
   php -r "echo password_hash('PASSWORD_KAMU', PASSWORD_DEFAULT), PHP_EOL;"
   ```
4. Masukkan hash tersebut ke `admin.password_hash` di `config.php`.
5. Ubah `app.base_url` sesuai domain.
6. Pastikan folder `storage/` writable oleh PHP.
7. Buka `/admin/login.php`.

`config.php` sudah masuk `.gitignore`, jadi password/config server tidak ikut ter-push ke GitHub.

## Struktur

```
admin/
  login.php
  logout.php
  events.php
api/
  upload.php
assets/
lib/
  auth.php
  events.php
storage/
config.example.php
index.php
photo.php
```

## Alur upload

Admin membuat Event/Folder → website menampilkan Upload Token → token dimasukkan ke APK → APK mengirim JPEG menggunakan Bearer token → API mencari event pemilik token → foto disimpan ke folder event tersebut.

Tidak ada token upload global. Setiap event memiliki token sendiri dan token asli hanya ditampilkan saat dibuat/regenerate.

## Public / Private

- **Public**: galeri dan file foto dapat dibuka melalui URL event.
- **Private**: galeri serta akses langsung `photo.php` ditolak.
- Upload tetap dapat berjalan selama Upload Token event valid.

> Sebelum dipakai produksi, admin mutation sebaiknya ditambah CSRF token dan storage sebaiknya dipindahkan di luar public web root jika hosting mendukung.
