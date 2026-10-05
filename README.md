# FOTOGRAFER — Android APK

Branch ini **khusus aplikasi Android/tablet**. Tidak ada source website di branch ini.

## Tugas APK
Canon EOS 600D → USB OTG → tablet → ambil JPEG otomatis → preview → upload ke website.

APK tidak menangani galeri customer, QR, atau download. Semua itu milik branch `website`.

## Konfigurasi di APK
Fotografer cukup mengisi:
- URL Website/API, contoh: `https://foto.domain.com`
- API Token
- Event aktif

Endpoint upload mengikuti website, misalnya `POST {URL}/api/upload.php`.

## Source
Source Android berada di folder `android/` pada branch ini.
