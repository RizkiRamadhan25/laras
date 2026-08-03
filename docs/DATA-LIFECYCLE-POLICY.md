# Kebijakan Siklus Hidup Data Laras

Versi: 1.0  
Target rilis: Laras v1.0.1  
Status: Disetujui untuk implementasi bertahap

## 1. Tujuan

Dokumen ini menetapkan bagaimana data pengguna Laras dinonaktifkan, diarsipkan, dipindahkan ke sampah, dipulihkan, dan dihapus permanen.

Tujuan utamanya:

- pengguna dapat membersihkan data lama;
- penghapusan tidak merusak saldo, ledger, anggaran, billing, dan analitik;
- tidak ada data pengguna lain yang ikut berubah;
- data finansial tidak hilang tanpa konfirmasi kuat;
- penghapusan akun tetap atomik dan bebas error foreign key;
- Laras siap dikembangkan menjadi aplikasi multi-user.

## 2. Istilah resmi

### Nonaktifkan

Data tetap tersimpan dan tetap menjadi bagian histori, tetapi tidak lagi digunakan untuk proses baru.

### Arsipkan

Data disembunyikan dari daftar aktif menggunakan `deleted_at` atau status domain yang sesuai. Data masih dapat dipulihkan.

### Sampah

Data berstatus soft-deleted dan ditampilkan dalam area Sampah. Retensi default 30 hari. Penghapusan otomatis hanya berlaku untuk jenis data nonfinansial yang secara eksplisit diizinkan.

### Batalkan

Khusus transaksi dan proses finansial. Efek saldo dibalik atau dinetralkan, tetapi histori audit tetap tersimpan.

### Hapus permanen

Record dan dependency terkait benar-benar dihapus. Tindakan tidak dapat dipulihkan.

## 3. Prinsip wajib

1. Semua operasi harus dibatasi oleh `user_id` atau relasi ownership yang setara.
2. Route model binding tidak boleh menjadi satu-satunya kontrol ownership.
3. Aksi permanen harus dijalankan dalam database transaction.
4. File fisik dihapus setelah database transaction berhasil.
5. Transaksi finansial tidak dihapus otomatis karena umur data.
6. Transfer diperlakukan sebagai satu transaksi utuh beserta seluruh entry.
7. Saldo rekening berasal dari ledger, bukan sekadar nilai yang dikurangi langsung.
8. Setelah transaksi permanen dihapus, saldo dan penggunaan anggaran harus dihitung ulang.
9. User harus melihat preview dampak sebelum penghapusan permanen.
10. Aksi destruktif wajib menggunakan password dan confirmation phrase pada data berisiko tinggi.
11. Semua operasi bulk harus idempotent dan aman dari klik ganda.
12. Tidak boleh menggunakan `SET FOREIGN_KEY_CHECKS = 0` sebagai solusi penghapusan.

## 4. Tingkat konfirmasi

### Level 1 — Ringan

Untuk notifikasi tunggal, foto profil, dan rekomendasi tunggal.

- confirm dialog;
- tidak perlu password;
- toast setelah berhasil.

### Level 2 — Sedang

Untuk aktivitas, anggaran, langganan, dan bulk delete data nonfinansial.

- confirm dialog;
- tampilkan nama dan jumlah data;
- tombol destruktif memiliki loading state;
- tidak perlu password kecuali penghapusan permanen bulk.

### Level 3 — Tinggi

Untuk transaksi, rekening dengan histori, security history, dan penghapusan permanen banyak data.

- password saat ini;
- confirmation phrase;
- preview dependency;
- export data ditawarkan terlebih dahulu;
- transaction database dan lock yang sesuai.

### Level 4 — Kritis

Untuk penghapusan akun.

- password saat ini;
- confirmation phrase `HAPUS AKUN`;
- seluruh session dihentikan;
- seluruh data child dihapus sebelum parent;
- file pengguna dibersihkan setelah transaksi sukses.

## 5. Retensi otomatis

### Boleh dibersihkan otomatis

- file ekspor sementara: 24 jam;
- file upload sementara: 24 jam;
- session kedaluwarsa: mengikuti konfigurasi session;
- password reset token kedaluwarsa: mengikuti konfigurasi auth;
- notifikasi yang sudah dibaca: 90 hari, hanya saat auto-cleanup diaktifkan user;
- recommendation interaction: 180 hari, hanya saat auto-cleanup diaktifkan user;
- aktivitas di Sampah: 30 hari, hanya saat auto-empty-trash diaktifkan user.

### Tidak boleh dibersihkan otomatis

- transaksi dan transaction entries;
- transfer;
- rekening;
- subscription billing yang terhubung ke transaksi;
- anggaran dan periodenya;
- security events terbaru;
- seluruh akun pengguna.

## 6. Pusat Kelola Data

Lokasi yang direncanakan:

`Pengaturan → Data & Privasi → Kelola Data`

Area ini menampilkan:

- jumlah record per kategori;
- jumlah data aktif, arsip, dan sampah;
- ukuran file yang memang dapat dihitung;
- aksi hapus berdasarkan pilihan, tanggal, atau usia data;
- preview dependency sebelum eksekusi;
- riwayat operasi penghapusan dalam session berjalan;
- shortcut ekspor data.

## 7. Aturan per domain

Aturan terperinci berada di `docs/DATA-DELETION-MATRIX.md`.

## 8. Urutan implementasi

1. Fondasi deletion preview dan ownership guard.
2. Notifikasi dan recommendation interaction.
3. Aktivitas dan Sampah.
4. Anggaran dan periode.
5. Langganan dan billing.
6. Transaksi permanen dan perhitungan ulang saldo.
7. Rekening kosong dan rekening dengan histori.
8. Security history.
9. Pusat Kelola Data.
10. Scheduler retention opsional.

## 9. Syarat multi-user

Semua endpoint baru wajib memiliki test berikut:

- user A dapat mengubah data miliknya;
- user A tidak dapat melihat data user B;
- user A tidak dapat menghapus data user B;
- bulk action hanya memproses ID milik user aktif;
- preview tidak membocorkan nama, nilai, atau jumlah dependency user lain;
- job dan scheduler selalu membawa atau menyaring `user_id`.

## 10. Definition of Done

Satu jenis data dianggap selesai ketika:

- policy ownership tersedia;
- preview dampak tersedia;
- operasi single dan bulk diuji;
- restore diuji bila memakai soft delete;
- permanent delete diuji di SQLite dan MySQL;
- tidak ada foreign-key failure;
- tidak ada perubahan saldo yang salah;
- toast dan confirm dialog menggunakan komponen global;
- halaman tetap aman untuk lebih dari satu user.
