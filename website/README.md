# Website

PHP 8+ instant gallery backend.

## Instalasi
1. Upload isi folder `website` ke hosting/VPS.
2. `cp config.example.php config.php`
3. Isi `base_url` dan `upload_token` random yang panjang.
4. Pastikan PHP dapat menulis ke folder `storage/`.
5. APK mengirim multipart POST ke `/api/upload.php` dengan header `Authorization: Bearer TOKEN`, field `event`, dan file `photo`.
6. Galeri: `/?event=NAMA_EVENT`.

## MVP
- upload JPEG/PNG bertoken
- event terpisah
- galeri responsive
- refresh foto baru setiap 5 detik
- original image endpoint

## Roadmap
Thumbnail/WebP, SSE realtime, admin event, QR sesi customer, selection cart, ZIP download, watermark preview, payment unlock HD.
