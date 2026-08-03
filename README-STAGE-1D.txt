LARAS v1.0.1 — TAHAP 1D
Fondasi Backend Penghapusan Data Berisiko Rendah

TUJUAN
- Menambahkan ownership guard terpusat.
- Menambahkan preview dampak penghapusan berbentuk JSON.
- Menambahkan penghapusan notifikasi.
- Menambahkan penghapusan riwayat rekomendasi.
- Memastikan operasi hanya menyentuh data milik user aktif.
- Belum menambahkan tombol/antarmuka penghapusan. UI dibuat pada tahap berikutnya.

FILE BARU
- app/Enums/DataDeletionScope.php
- app/Http/Requests/PurgeNotificationsRequest.php
- app/Http/Requests/PurgeRecommendationInteractionsRequest.php
- app/Services/OwnedResourceGuard.php
- app/Services/DataDeletionScopeService.php
- app/Services/DataDeletionPreviewService.php
- app/Services/DataDeletionService.php
- tests/Feature/DataDeletionFoundationTest.php

FILE DIGANTI
- app/Http/Controllers/NotificationController.php
- app/Http/Controllers/RecommendationController.php
- routes/web.php

CAKUPAN NOTIFIKASI
- selected: hapus notifikasi terpilih, maksimal 100.
- read: hapus seluruh notifikasi yang sudah dibaca.
- older: hapus notifikasi yang lebih lama dari jumlah hari tertentu.
- all: hapus seluruh notifikasi user aktif.
- single: hapus satu notifikasi melalui route khusus.

CAKUPAN RIWAYAT REKOMENDASI
- selected: hapus riwayat terpilih, maksimal 100.
- older: hapus riwayat lebih lama dari jumlah hari tertentu.
- all: hapus seluruh riwayat rekomendasi user aktif.
- single: hapus satu riwayat melalui route khusus.

ROUTE BARU
POST   /notifications/deletion-preview
DELETE /notifications/purge
DELETE /notifications/{notification}

POST   /recommendations/history/deletion-preview
DELETE /recommendations/history/purge
DELETE /recommendations/history/{interaction}

PENTING
- Preview dan penghapusan selected menolak seluruh request jika satu ID bukan milik user aktif.
- Penghapusan berjalan di dalam database transaction.
- Tidak ada migration pada tahap ini.
- Tidak ada penghapusan transaksi, rekening, langganan, atau anggaran pada tahap ini.
- Session flash masih digunakan. Tampilan toast dibuat pada fase UI global.
