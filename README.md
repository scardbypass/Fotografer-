# FOTOGRAFER — Website

Branch ini khusus **website + API** dan siap ditempatkan langsung sebagai web root. Tidak ada folder pembungkus `website/` dan tidak ada source APK.

## Struktur

```
admin/
api/
assets/
lib/
storage/
config.example.php
index.php
photo.php
README.md
```

Setiap Event/Folder memiliki Upload Token sendiri. APK hanya mengirim foto menggunakan Server URL + token; server menentukan folder tujuan dari token. Folder dapat diatur Public atau Private.
