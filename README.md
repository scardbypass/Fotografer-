# FOTOGRAFER — Android APK

Aplikasi tablet fotografer untuk preview dan pengiriman foto ke server.

## Status implementasi (Oktober 2026)

Sudah ditambahkan di source:

- UI dark navy adaptif untuk HP/tablet.
- Settings Server URL HTTPS + Upload Token.
- Pemilihan multi-foto JPEG/PNG melalui document picker.
- Multipart upload dengan validasi JSON dan daftar URI pending yang dipulihkan saat aplikasi dibuka ulang.
- Pencarian USB PTP, izin USB, sesi PTP, polling object handles, impor JPEG baru ke penyimpanan internal dan upload otomatis ketika foto baru terdeteksi.
- Buffer pembacaan paket USB untuk mencegah data container terpotong.

**Belum diverifikasi:** build APK, pengujian Android fisik, serta kompatibilitas EOS 750D. PTP standar tidak menjamin EOS 750D akan menyediakan JPEG baru tanpa mode/vendor command Canon. Ini belum produk siap produksi.

**Belum ada:** WorkManager/foreground upload untuk melanjutkan proses ketika aplikasi ditutup; penyimpanan token terenkripsi; deduplikasi server dan pengujian transfer berjam-jam. Daftar pending yang tersimpan bukan background uploader.

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
Canon EOS 750D
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

Fitur berikut masih harus diselesaikan dan diuji pada Canon EOS 750D fisik:

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


## Pembaruan Oktober 2026

- UI dark navy adaptif portrait/landscape.
- Pengaturan Server URL HTTPS dan token tersedia dalam aplikasi.
- Pilih banyak JPG/PNG melalui Android document picker.
- Multipart upload memakai Bearer token dan memeriksa respons JSON.
- URI foto pending disimpan dan dipulihkan saat aplikasi dibuka ulang, selama izin dokumen masih berlaku.
- File gagal dipertahankan untuk percobaan upload manual berikutnya.

### Batasan yang masih ada

- Belum ada implementasi USB PTP Canon (permission, session, ObjectAdded, GetObject). Indikator USB bukan koneksi PTP.
- Belum ada background worker/foreground service untuk melanjutkan transfer saat aplikasi ditutup.
- Pending queue hanya menyimpan URI, bukan salinan foto lokal; file yang dihapus/dipindahkan atau izin yang dicabut bisa hilang dari antrean.
- Token tersimpan di SharedPreferences biasa; sebelum produksi perlu penyimpanan kredensial terenkripsi dan prosedur reset token.
- Belum diuji Gradle build maupun pada Canon EOS 750D fisik. Jangan gunakan sebagai solusi capture otomatis siap produksi.


### Pengujian lapangan Canon

1. Hubungkan Canon EOS 750D via USB OTG ke Android yang mendukung USB Host.
2. Buka aplikasi dan izinkan akses USB, lalu tekan Hubungkan Canon sekali lagi.
3. Ambil JPEG baru di kamera; periksa apakah preview muncul dan status upload berubah.
4. Jika tidak ada foto baru, periksa mode USB Canon; protokol EOS proprietary mungkin perlu implementasi tambahan.
5. Uji internet putus, izin USB dicabut, kamera dilepas, foto beruntun, dan server menolak token.

**Catatan:** project belum memiliki Gradle Wrapper dan belum berhasil dibuild/dites dalam sesi ini. Jangan menganggap perubahan source sebagai APK siap install.


## Pembaruan antrean background dan CI

- `UploadWorker.kt` menggunakan AndroidX WorkManager, jaringan wajib tersedia, backoff eksponensial dan daftar URI yang disimpan. Foto yang berhasil dikonfirmasi server dihapus dari daftar pending. JPEG impor Canon yang berhasil terkirim dapat dihapus dari cache internal.
- Pekerjaan upload baru disusun berurutan menggunakan `APPEND_OR_REPLACE` agar foto yang datang ketika worker berjalan tidak terlewat.
- `.github/workflows/android-build.yml` menyediakan build debug otomatis pada push branch `apk` dengan Gradle 8.9, JDK 17, SDK 35. Periksa tab Actions untuk status build dan unduh artifact hanya bila sukses.
- Android dapat menunda atau menghentikan pekerjaan background sesuai batas sistem. Ini **bukan** foreground service yang menjamin transfer berjalan terus ketika layar mati; untuk upload foto besar atau sesi panjang perlu foreground worker + notifikasi.
- Pengujian USB permission, PTP Canon EOS 750D, kompatibilitas protokol vendor Canon, dan hasil build aktual belum dikonfirmasi pada perangkat fisik. Jangan menyebutnya siap produksi sebelum lulus pengujian.
- Retry HTTP yang belum memiliki idempotency key bisa mengakibatkan foto ganda jika server telah menyimpan file tetapi respons terputus. Backend perlu deduplikasi berbasis upload ID/checksum.


## Kamera utama: Canon EOS 750D

Kamera fisik yang dipakai adalah Canon EOS 750D (bukan 600D). Prioritaskan pengujian USB OTG, PTP Still Image interface, USB permission, JPEG baru, impor byte asli, serta upload antrean pada EOS 750D. Dukungan PTP standar belum membuktikan kompatibilitas fitur remote capture/event EOS proprietary. Jangan menandai pengujian fisik sebagai lulus sebelum ada log dari kamera dan perangkat Android.
