# Matriks Penghapusan Data Laras

## Ringkasan

| Data | Kondisi saat ini | Aksi utama | Sampah/restore | Hapus permanen | Auto-cleanup |
|---|---|---|---|---|---|
| Foto profil | Sudah bisa dihapus | Hapus langsung | Tidak | Ya | Tidak |
| Notifikasi | Hanya tandai dibaca | Hapus langsung / bulk | Tidak | Ya | Opsional 90 hari setelah dibaca |
| Recommendation interaction | Belum ada hapus | Hapus langsung / bulk | Tidak | Ya | Opsional 180 hari |
| Aktivitas | Soft delete + restore tersedia | Arsipkan ke Sampah | Ya | Ya | Opsional 30 hari di Sampah |
| Anggaran | Soft delete tersedia, belum ada route hapus | Nonaktifkan lalu Sampah | Ya | Ya | Tidak |
| Langganan | Soft delete tersedia, archive service ada | Batalkan lalu arsipkan | Ya, perlu route restore | Ya | Tidak |
| Subscription billing | Belum ada hapus | Hapus bersama langganan atau kategori billing | Tidak | Ya | Tidak |
| Transaksi | Soft delete tersedia, UI hanya membatalkan | Batalkan | Ya hanya jika fitur Sampah transaksi disetujui | Ya dengan rebuild saldo | Tidak |
| Transfer | Satu transaction dengan beberapa entry | Batalkan sebagai satu unit | Sama dengan transaksi | Ya sebagai satu unit | Tidak |
| Rekening | Soft delete + restore tersedia | Arsipkan | Ya | Ya dengan syarat | Tidak |
| Finance category | Soft delete tersedia, belum ada UI penuh | Nonaktifkan/arsipkan | Ya | Terbatas | Tidak |
| Security event | Belum ada hapus | Hapus berdasarkan rentang | Tidak | Ya dengan password | Retensi minimal 365 hari disarankan |
| Ekspor sementara | Dibuat saat request | Hapus otomatis | Tidak | Ya | 24 jam |
| Akun | Permanent delete service tersedia | Hapus seluruh akun | Tidak | Ya | Tidak |

## 1. Foto profil

### Aksi

- hapus file foto aktif;
- set `profile_photo_path` menjadi `null`;
- fallback avatar kembali ke inisial.

### Konfirmasi

Level 1.

### Ownership

Hanya foto milik user aktif.

## 2. Notifikasi

### Aksi yang ditambahkan

- hapus satu notifikasi;
- hapus semua notifikasi yang sudah dibaca;
- hapus notifikasi berdasarkan pilihan;
- hapus semua notifikasi;
- hapus notifikasi dibaca yang lebih lama dari 30, 60, atau 90 hari.

### Implementasi

Hard delete langsung karena notifikasi bukan sumber kebenaran finansial.

### Konfirmasi

Level 1 untuk satu data, Level 2 untuk bulk.

### Dampak

Menghapus notifikasi tidak menghapus transaksi, budget, langganan, atau billing yang dirujuk.

## 3. Recommendation interaction

### Aksi

- hapus satu histori;
- hapus berdasarkan jenis interaksi;
- hapus berdasarkan rentang tanggal;
- hapus semua histori rekomendasi.

### Implementasi

Hard delete langsung.

### Konfirmasi

Level 1 atau Level 2 untuk bulk.

### Dampak

Rekomendasi selanjutnya dapat berubah karena histori feedback berkurang.

## 4. Aktivitas

### Aksi utama

- tombol `Hapus` berarti pindahkan ke Sampah melalui soft delete;
- tampilan `Sampah` terpisah dari `Arsip` jika keduanya ingin dibedakan secara UI;
- restore tersedia;
- force delete tersedia dari Sampah.

### Catatan skema saat ini

Model sudah memakai `SoftDeletes`. Saat ini route destroy berperan sebagai archive. Pada implementasi UI baru, label dan arti harus dibuat konsisten: `Arsipkan` untuk menyimpan histori, `Pindahkan ke Sampah` untuk rencana permanent delete.

### Konfirmasi

Level 2; force delete bulk memakai Level 3.

### Auto-cleanup

30 hari di Sampah, hanya bila user mengaktifkannya.

## 5. Anggaran

### Aksi utama

1. nonaktifkan anggaran aktif;
2. pindahkan ke Sampah;
3. restore;
4. force delete dari Sampah.

### Permanent delete

Menghapus:

- budget alert events;
- budget periods;
- budget.

Tidak menghapus transaksi yang pernah dihitung dalam anggaran.

### Syarat

- budget harus nonaktif;
- `active_finance_category_id` harus `null` sebelum soft delete;
- force delete dijalankan dalam transaction.

### Konfirmasi

Level 2; bulk force delete Level 3.

## 6. Langganan

### Aksi utama

1. pause atau cancel;
2. archive setelah status cancelled/expired;
3. restore route ditambahkan;
4. force delete dari arsip.

### Permanent delete

Menghapus subscription dan seluruh subscription billing melalui dependency yang terkontrol.

Transaksi finansial yang pernah dibuat oleh billing tidak ikut dihapus otomatis. Hubungan billing ke transaksi hilang karena billing dihapus, tetapi transaksi tetap menjadi histori keuangan.

### Syarat

- status cancelled atau expired;
- tidak ada scheduled billing yang masih aktif;
- preview menampilkan jumlah billing dan transaksi terkait.

### Konfirmasi

Level 2; permanent delete Level 3.

## 7. Transaksi pemasukan dan pengeluaran

### Aksi utama

`Batalkan transaksi` tetap menjadi pilihan standar.

Pembatalan:

- mempertahankan transaction dan entries;
- mengubah status menjadi cancelled;
- mengembalikan efek saldo;
- memperbarui penggunaan anggaran.

### Permanent delete

Hanya tersedia melalui menu lanjutan.

Urutan:

1. lock transaction milik user;
2. catat account ID dan budget period terdampak;
3. hapus seluruh transaction entries;
4. hapus transaction;
5. rebuild saldo semua rekening terdampak dari initial balance dan posted entries;
6. sinkronkan kembali budget usage;
7. biarkan `subscription_billings.transaction_id` menjadi `null` bila billing masih ada;
8. commit transaction.

### Konfirmasi

Level 3.

### Transfer

Satu transfer dihapus sebagai satu transaction lengkap. Tidak boleh menghapus hanya entry sumber atau tujuan.

## 8. Rekening

### Aksi utama

Arsipkan dan restore tetap menjadi perilaku utama.

### Hapus permanen rekening kosong

Diizinkan jika:

- tidak memiliki transaction entries, termasuk transaksi cancelled;
- tidak dipakai subscription aktif maupun arsip;
- tidak menjadi dependency proses lain;
- bukan satu-satunya rekening yang diperlukan onboarding, jika aturan itu masih berlaku.

### Hapus permanen rekening dengan histori

Dijalankan sebagai operasi lanjutan:

1. preview semua transaksi, transfer, billing, dan langganan terkait;
2. user memilih membatalkan operasi atau menghapus seluruh histori terkait;
3. seluruh transaksi terkait dihapus per transaction, bukan per entry;
4. subscription harus dipindahkan ke rekening lain atau ikut dihapus;
5. saldo dan anggaran disinkronkan;
6. rekening di-force-delete.

### Konfirmasi

Level 3.

## 9. Finance category

### Aksi utama

- kategori sistem: hanya dapat dinonaktifkan atau disembunyikan;
- kategori kustom: dapat diarsipkan;
- kategori kosong: dapat dihapus permanen;
- kategori terpakai: harus menampilkan dependency.

### Dependency

- transaction entries menggunakan `nullOnDelete`, sehingga transaksi tetap ada tanpa kategori;
- subscriptions menggunakan `restrictOnDelete`;
- budgets menggunakan `restrictOnDelete`.

### Permanent delete kategori terpakai

User harus memilih:

- pindahkan subscriptions/budgets ke kategori lain;
- hapus subscriptions/budgets terkait;
- batalkan operasi.

## 10. Security event

### Aksi

- hapus event lebih lama dari 90/180/365 hari;
- hapus berdasarkan pilihan;
- hapus semua histori keamanan.

### Rekomendasi retensi

Simpan minimal 365 hari secara default untuk membantu audit keamanan. User tetap memiliki opsi permanent delete dengan password.

### Konfirmasi

Level 3.

## 11. Penghapusan akun

### Aksi

Child-first permanent deletion di dalam database transaction.

Urutan inti:

1. session, notification, reset token;
2. budget alert event, period, budget;
3. subscription billing, subscription;
4. transaction entry, transaction;
5. passkey, recommendation interaction, security event, activity, preference;
6. account dan finance category;
7. user;
8. profile files setelah database commit.

### Konfirmasi

Level 4.

## 12. Route dan service yang direncanakan

Nama final dapat berubah saat implementasi, tetapi struktur tanggung jawabnya:

- `DataDeletionPreviewService` — menghitung dependency dan dampak;
- `NotificationDeletionService`;
- `RecommendationHistoryDeletionService`;
- `ActivityDeletionService`;
- `BudgetDeletionService`;
- `SubscriptionDeletionService`;
- `TransactionDeletionService`;
- `AccountPermanentDeletionService`;
- `SecurityHistoryDeletionService`;
- `DataRetentionService`;
- `PurgeExpiredUserData` command.

Semua service wajib menerima `User $user` sebagai boundary ownership.
