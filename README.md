# FOTOGRAFER — Android APK

Aplikasi tablet fotografer untuk preview dan pengiriman foto ke server.

## Status implementasi

Yang sudah tersedia:

- UI landscape untuk tablet.
- Preview JPEG melalui `showCapturedPhoto(file)`.
- Status CAMERA / SERVER.
- Counter upload.
- HTTP uploader dengan Bearer Upload Token.
- Project Android yang dapat dibuka di Android Studio.

Yang **belum selesai**:

- USB permission flow Canon.
- PTP session Canon EOS 600D.
- Event `ObjectAdded` dan download JPEG otomatis dari kamera.
- Persistent upload queue/retry setelah aplikasi ditutup.
- Layar Settings untuk Server URL + Upload Token.

Jadi build APK saat ini adalah **development build**, belum APK operasional Canon 600D end-to-end.

## Kebutuhan build

- Android Studio versi modern yang mendukung Android Gradle Plugin 8.7.x.
- JDK 17.
- Android SDK 35.
- Internet saat Gradle pertama kali mengunduh dependency.
- Android tablet/HP Android 7.0 (API 24) atau lebih baru.
- Untuk penggunaan kamera nanti: perangkat Android harus mendukung USB Host/OTG.

## Build menggunakan Android Studio

1. Clone/download **branch `apk`**.
2. Buka folder root branch tersebut di Android Studio.
3. Tunggu **Gradle Sync** selesai.
4. Pastikan SDK 35 terpasang melalui SDK Manager.
5. Pilih **Build → Build App Bundle(s) / APK(s) → Build APK(s)**.
6. APK debug biasanya berada di:

```text
app/build/outputs/apk/debug/app-debug.apk
```

Project repository saat ini tidak menyertakan Gradle Wrapper. Cara paling mudah untuk testing adalah melalui Android Studio.

## Build command line

Jika komputer sudah memiliki Gradle yang kompatibel:

```bash
gradle assembleDebug
```

Untuk penggunaan jangka panjang sebaiknya generate dan commit Gradle Wrapper, lalu build dapat menggunakan:

```bash
./gradlew assembleDebug
```

Windows:

```powershell
gradlew.bat assembleDebug
```

## Install APK

Dengan ADB:

```bash
adb install -r app/build/outputs/apk/debug/app-debug.apk
```

Atau copy APK ke tablet dan install secara manual setelah mengizinkan instalasi dari sumber yang digunakan.

## Konfigurasi server

Arsitektur final APK hanya membutuhkan:

```text
Server URL   : https://foto.domainkamu.com
Upload Token : ft_xxxxxxxxxxxxxxxxx
```

Upload endpoint:

```text
POST /api/upload.php
Authorization: Bearer <Upload Token>
multipart field: photo
```

Folder/event **tidak dikirim APK**. Website menentukan folder berdasarkan Upload Token.

> Layar Settings belum diimplementasikan pada build sekarang. Jangan menganggap Server URL dan token sudah dapat diatur melalui UI sampai fitur tersebut ditambahkan.

## Alur final yang dituju

```text
Canon EOS 600D
      ↓ USB OTG / PTP
Android Tablet
      ↓ preview JPEG
Upload Queue
      ↓ HTTPS
Website API
      ↓
Event Storage
      ↓
Galeri pelanggan
```

## Checklist testing APK saat ini

- Project Gradle sync tanpa error.
- APK debug berhasil dibuat.
- APK dapat di-install.
- UI landscape terbuka.
- USB device generik dapat terdeteksi sebagai device presence.
- Preview hook dapat menampilkan file JPEG bila dipanggil oleh layer transfer.

Status CAMERA saat ini hanya mendeteksi keberadaan USB device. Itu **belum membuktikan bahwa device adalah Canon atau bahwa PTP sudah tersambung**.

## Sebelum dipakai di lapangan

Fitur berikut masih harus diselesaikan dan diuji pada Canon EOS 600D fisik:

1. USB permission.
2. Identifikasi Still Image/PTP interface.
3. Open PTP session.
4. Monitor ObjectAdded.
5. Download JPEG.
6. Tampilkan preview.
7. Simpan queue lokal.
8. Upload/retry otomatis.
9. Settings Server URL + Upload Token.
10. Tes cabut-pasang USB, internet putus, layar mati, dan banyak foto berurutan.
