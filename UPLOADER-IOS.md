# Web Uploader iPhone / iPad

Buka `https://domainmu/uploader.php` di Safari. Gunakan Share → Add to Home Screen untuk pintasan seperti aplikasi.

1. Admin membuat event dan menyalin upload token.
2. Fotografer mengisi alamat server HTTPS dan token.
3. Pilih foto dari Photos atau Files, kemudian mulai upload.
4. Foto disimpan original oleh `api/upload.php`, dipetakan ke event sesuai token.

**Penting:** Safari tidak mendukung kendali Canon EOS 600D USB PTP langsung. Transfer foto ke iPhone/iPad terlebih dahulu memakai perangkat/metode yang kompatibel. Safari harus tetap terbuka selama proses upload. Untuk file besar, atur `upload_max_filesize` dan `post_max_size` pada PHP hosting. Video memerlukan dukungan MIME backend dan gallery video terpisah; jangan menganggap video sudah dapat ditonton di galeri.

Token hanya tersimpan sementara di kolom input, tidak di localStorage. Jangan membagikan token kepada pelanggan.
