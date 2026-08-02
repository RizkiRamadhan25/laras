LARAS — FASE 5B-2

Isi paket:
- Security headers middleware dan pengujian.
- Ekspor data ZIP yang meminta kata sandi, menulis JSON per dataset secara bertahap, menyertakan foto profil, dan memasukkan budget_alert_events.
- Normalisasi foto profil menjadi WebP 512 x 512 menggunakan Laravel Image API.
- Guard database untuk mencegah dua anggaran aktif pada kategori yang sama.
- Sinkronisasi periode memakai createOrFirst dan row lock.

Dependency yang harus dipasang sebelum test:
1. intervention/image:^4.0
2. ext-gd
3. ext-zip
4. ext-exif (untuk orientasi foto berdasarkan EXIF)

Paket tidak menimpa composer.json agar perubahan script composer dev pada Windows tetap aman.
