LARAS v1.0.1 — STAGE 1E
Antarmuka Penghapusan Notifikasi dan Riwayat Rekomendasi

TUJUAN
- Menambahkan pemilihan satu/banyak data pada halaman notifikasi dan riwayat rekomendasi.
- Menampilkan preview jumlah data melalui endpoint Tahap 1D sebelum penghapusan.
- Mengganti konfirmasi browser dengan dialog Laras yang accessible.
- Menjaga penghapusan final tetap melalui endpoint backend yang sudah memiliki ownership guard.

FILE BARU
- resources/views/components/data-deletion-dialog.blade.php
- resources/js/features/data-deletion.js
- tests/Feature/DataDeletionInterfaceTest.php
- README-STAGE-1E.txt

FILE YANG DIGANTI
- resources/views/layouts/app.blade.php
- resources/views/notifications/index.blade.php
- resources/views/recommendations/history.blade.php
- resources/js/app.js
- resources/css/app.css

FITUR UI
NOTIFIKASI
- Pilih satu atau seluruh data pada halaman aktif.
- Hapus data terpilih.
- Hapus seluruh notifikasi yang sudah dibaca.
- Hapus notifikasi lebih lama dari 90 hari.
- Hapus seluruh notifikasi.
- Hapus satu notifikasi melalui tombol pada setiap item.

RIWAYAT REKOMENDASI
- Pilih satu atau seluruh data pada halaman aktif.
- Hapus data terpilih.
- Hapus riwayat lebih lama dari 180 hari.
- Hapus seluruh riwayat.
- Hapus satu riwayat melalui tombol pada setiap item.

DIALOG
- Preview dimuat melalui Fetch API sebelum tombol konfirmasi diaktifkan.
- Menampilkan jumlah dan rincian data yang terdampak.
- Dapat ditutup melalui tombol, klik backdrop, atau Escape.
- Tombol penghapusan dinonaktifkan saat preview kosong atau request sedang dikirim.
- Mendukung prefers-reduced-motion.

CATATAN BATASAN TAHAP
- Penghapusan masih melakukan redirect/reload setelah form berhasil dikirim.
- Session status masih memakai banner lama.
- Sistem toast global dan penghapusan tanpa reload akan dibangun pada tahap UI global berikutnya.
- Hanya notifikasi dan riwayat rekomendasi yang dikelola pada tahap ini.

PEMERIKSAAN
1. php artisan optimize:clear
2. php artisan test --filter=DataDeletionInterfaceTest
3. php artisan test --filter=DataDeletionFoundationTest
4. php artisan test --filter=NotificationCenterTest
5. php artisan test --filter=RecommendationInteractionTest
6. php artisan test
7. php vendor\bin\pint --test
8. npm run build
9. composer validate --strict

UJI MANUAL
1. Jalankan composer dev.
2. Buka /notifications.
3. Pilih beberapa notifikasi dan tekan Hapus terpilih.
4. Pastikan dialog menampilkan preview, lalu batalkan.
5. Ulangi dan konfirmasi penghapusan.
6. Uji Hapus yang dibaca, data lama, semua, dan tombol hapus per item.
7. Buka /recommendations/history dan ulangi skenario setara.
8. Pastikan Escape dan klik area backdrop menutup dialog.
9. Pastikan tidak ada confirm() bawaan browser.
10. Pastikan data pengguna lain tidak pernah tampil atau terhapus.
