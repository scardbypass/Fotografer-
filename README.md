# FOTOGRAFER — Android APK

Branch ini langsung berisi project Android di root dan khusus APK tablet fotografer.

## Alur
Canon EOS 600D → USB OTG/PTP → JPEG diterima APK → **preview besar otomatis** → queue → upload ke website.

APK hanya membutuhkan **Server URL + Upload Token**. Folder tujuan ditentukan website berdasarkan token.

## UI tablet
- Foto hasil jepretan terbaru menjadi fokus utama layar.
- Status CAMERA dan SERVER selalu terlihat.
- Nama file + status transfer/upload di bagian bawah.
- Counter UP / QUEUE / FAIL.
- `showCapturedPhoto(file)` sudah disiapkan sebagai titik masuk JPEG dari layer Canon/PTP.
- `updateUploadState(...)` disiapkan untuk uploader/queue.

## Struktur
```
app/
build.gradle.kts
settings.gradle.kts
README.md
```

> Catatan: tampilan preview dan hook penerimaan JPEG sudah tersedia. Implementasi protokol Canon EOS 600D USB/PTP untuk mendownload ObjectAdded dari kamera masih tahap berikutnya dan wajib diuji pada Canon 600D fisik.
