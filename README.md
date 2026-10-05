# FOTOGRAFER — Instant Photo Delivery

Sistem fotografer event/wisata: hasil Canon langsung tampil di tablet, otomatis masuk server, lalu customer membuka foto melalui QR/link.

> **Branch main hanya untuk panduan. Source code tidak diletakkan di sini.**

## Struktur Branch

| Branch | Isi |
|---|---|
| `main` | Dokumentasi, alur kerja, arsitektur dan roadmap |
| `apk` | Source APK Android/tablet + Canon USB/PTP |
| `website` | Website, API, galeri, QR dan download |

## Alur Sistem

```text
Canon EOS 600D
      │ USB OTG
      ▼
Tablet Android + APK
      │
      ├─ Kamera terdeteksi
      ├─ Foto baru diterima otomatis
      ├─ Preview fullscreen
      ├─ Simpan local queue
      └─ Auto Upload HTTPS
      ▼
Server / VPS
      │
      ├─ API Upload
      ├─ Event / Sesi
      ├─ Original HD
      ├─ Thumbnail
      └─ Database
      ▼
Website Gallery
      │
      ├─ Foto muncul realtime
      ├─ Operator memilih foto customer
      ├─ Buat QR / link unik
      └─ Download
      ▼
HP Customer
```

## Alur Kerja Fotografer

1. Buat **Event**, misalnya `Alun-Alun Jombang - Malam Minggu`.
2. Sambungkan Canon EOS 600D ke tablet Android menggunakan USB OTG.
3. APK meminta izin USB dan membuka koneksi PTP.
4. Fotografer memotret normal dari tombol shutter Canon.
5. Ketika ada foto baru, APK mendeteksi event **ObjectAdded**.
6. JPEG diambil otomatis dari kamera.
7. Foto terbaru langsung tampil besar di tablet.
8. File masuk antrean upload.
9. Saat online, APK otomatis upload ke event aktif di server.
10. Server menyimpan original dan membuat thumbnail.
11. Foto muncul di galeri operator/customer.
12. Fotografer memilih foto milik customer.
13. Sistem membuat QR/link unik.
14. Customer scan QR dan membuka foto dari HP.
15. Customer memilih/download foto.

## APK Android — branch `apk`

Target: **Canon EOS 600D + Android tablet + USB OTG**.

Fitur:
- USB Host + permission
- Canon PTP/MTP
- ObjectAdded listener
- auto-transfer JPEG
- preview foto terbaru fullscreen
- thumbnail gallery
- pilih Event aktif
- server URL + API token
- background uploader
- persistent upload queue
- retry otomatis
- status Connected / Offline / Uploading
- Pending / Uploaded / Failed counter
- cache lokal
- opsi hapus cache setelah upload sukses

### Mode Offline

```text
JEPRET → JPEG → LOCAL QUEUE
                    │
             ┌──────┴──────┐
           ONLINE        OFFLINE
             │              │
           UPLOAD         PENDING
                            │
                    Internet kembali
                            │
                         AUTO RETRY
```

Foto tidak boleh hilang hanya karena jaringan putus.

## Website — branch `website`

Fitur yang dituju:
- login operator/admin
- Event management
- API upload bertoken
- original HD
- thumbnail/WebP
- realtime gallery
- halaman operator
- customer session
- pilih beberapa foto
- QR unik per customer
- link download
- download satu foto
- download pilihan sebagai ZIP
- watermark preview
- PIN opsional
- link expiry
- statistik foto/download

## Contoh Customer

```text
EVENT: Alun-Alun Jombang
CUSTOMER: A023
FOTO: 5
        │
        ▼
    BUAT QR
        │
        ▼
Customer Scan
        │
        ▼
/gallery/A023-x7k92
        │
        ▼
Lihat → Pilih → Download
```

Customer **tidak perlu memasang APK**.

## Target Realtime

```text
Jepret
  ↓
Canon selesai menyimpan
  ↓
APK menerima JPEG
  ↓
Preview tablet
  ↓
Upload
  ↓
Server membuat thumbnail
  ↓
Foto muncul di web
```

Kecepatan aktual bergantung ukuran JPEG, USB, performa tablet, internet dan server.

## Keamanan

- HTTPS untuk upload.
- API token melalui header, bukan URL.
- Validasi MIME dan ukuran file.
- Server membuat ulang nama file.
- Token acak untuk link customer.
- Original HD dapat dipisahkan dari preview publik.
- Rate limit API upload.
- File upload tidak boleh dieksekusi sebagai script.

## Fitur Lanjutan

Setelah sistem dasar stabil:
- watermark sebelum pembayaran
- QRIS
- setelah pembayaran sukses → HD terbuka
- branding fotografer
- beberapa operator/tablet
- beberapa kamera
- pencarian berdasarkan waktu/sesi
- auto-expire event
- backup object storage

## Prioritas Pengembangan

1. USB/PTP Canon 600D.
2. ObjectAdded + auto-transfer JPEG.
3. Preview tablet.
4. Upload queue yang tahan restart.
5. Backend Event/API.
6. Galeri realtime.
7. QR/link customer.
8. Pilih + ZIP download.
9. Watermark.
10. Pembayaran opsional.

### Catatan Canon 600D

Kompatibilitas PTP harus diuji dengan **Canon EOS 600D fisik**. Source tidak boleh dianggap production-ready sebelum pengujian koneksi, event foto baru, transfer JPEG, reconnect USB, dan pemotretan berulang.

---

**Repository:** `scardbypass/Fotografer-`

Gunakan branch sesuai komponen. **Jangan push source APK/website ke main.**
