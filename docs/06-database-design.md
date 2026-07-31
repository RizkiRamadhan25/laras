# Database Design — Laras

## 1. Informasi Dokumen

- **Nama proyek:** Laras
- **Tagline:** Selaraskan hari, tentukan langkah.
- **Jenis dokumen:** Database Design
- **Versi:** 1.0
- **Tanggal:** 31 Juli 2026
- **Database:** MySQL 8+
- **Framework:** Laravel
- **Dokumen terkait:**
  - `01-project-scope.md`
  - `02-user-flow.md`
  - `03-sitemap-and-page-list.md`
  - `04-feature-priority-and-development-phases.md`
  - `05-wireframe-specification.md`

---

## 2. Tujuan Dokumen

Dokumen ini menetapkan struktur database Laras, meliputi:

- Daftar tabel.
- Fungsi setiap tabel.
- Kolom dan tipe data.
- Primary key.
- Foreign key.
- Index.
- Unique constraint.
- Soft delete.
- Audit trail.
- Aturan saldo.
- Aturan transaksi.
- Relasi kegiatan.
- Relasi rekomendasi.
- Lampiran dan scan.
- Data awal.
- Strategi migration Laravel.

Dokumen ini menjadi dasar pembuatan:

- Migration.
- Model Eloquent.
- Factory.
- Seeder.
- Service transaksi.
- Unit test.
- ERD.

---

# 3. Prinsip Desain Database

Database Laras mengikuti prinsip berikut:

1. Nominal uang menggunakan `DECIMAL`, bukan `FLOAT` atau `DOUBLE`.
2. Saldo akun tidak diubah tanpa transaksi.
3. Semua perubahan penting memiliki audit trail.
4. Transfer antar-akun tidak dihitung sebagai pemasukan atau pengeluaran utama.
5. Transaksi draft, pending, failed, dan cancelled tidak mengubah saldo final.
6. Data keuangan penting menggunakan soft delete.
7. File bukti transaksi disimpan secara privat.
8. Status dan jenis data dikontrol menggunakan enum aplikasi.
9. Tabel referensi digunakan untuk data yang dapat berubah.
10. Foreign key dan index digunakan untuk menjaga integritas dan performa.
11. Data single-user tetap memiliki `user_id` agar struktur aman dan future-ready.
12. Timestamp menggunakan UTC di database dan ditampilkan dalam `Asia/Jakarta`.
13. JSON digunakan hanya untuk metadata fleksibel, bukan data utama yang sering dicari.
14. Database transaction wajib digunakan untuk operasi finansial.
15. Saldo dapat dihitung ulang dari ledger jika diperlukan.

---

# 4. Konvensi Penamaan

## 4.1 Tabel

Gunakan bentuk plural dan snake_case.

Contoh:

```text
users
accounts
transactions
activities
recommendations
```

## 4.2 Primary Key

Gunakan:

```text
id BIGINT UNSIGNED AUTO_INCREMENT
```

## 4.3 Foreign Key

Gunakan pola:

```text
<singular_table>_id
```

Contoh:

```text
user_id
account_id
category_id
transaction_id
```

## 4.4 Timestamp

Gunakan:

```text
created_at
updated_at
deleted_at
```

## 4.5 Boolean

Gunakan prefix:

```text
is_active
is_pinned
is_completed
include_in_total
```

---

# 5. Ringkasan Tabel

## 5.1 Core

- `users`
- `user_preferences`
- `onboarding_progress`

## 5.2 Kategori

- `categories`

## 5.3 Keuangan

- `accounts`
- `account_balance_snapshots`
- `transactions`
- `transaction_entries`
- `transaction_attachments`
- `budgets`
- `budget_periods`
- `scan_documents`
- `scan_extractions`

## 5.4 Kegiatan

- `activities`
- `activity_status_histories`
- `activity_priority_histories`

## 5.5 Rekomendasi

- `recommendations`
- `recommendation_feedback`

## 5.6 Catatan dan Notifikasi

- `notes`
- `notifications`

## 5.7 Audit dan Sistem

- `activity_logs`
- `login_histories`
- `backups`

---

# 6. users

## Fungsi

Menyimpan akun pengguna Laras.

Walaupun aplikasi hanya digunakan satu pengguna, tabel tetap dibuat normal agar autentikasi dan relasi data tetap aman.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| name | VARCHAR(100) | Tidak | - | Nama pengguna |
| email | VARCHAR(191) | Tidak | - | Email login |
| email_verified_at | TIMESTAMP | Ya | NULL | Verifikasi email |
| password | VARCHAR(255) | Tidak | - | Password hash |
| profile_photo_path | VARCHAR(255) | Ya | NULL | Foto profil |
| remember_token | VARCHAR(100) | Ya | NULL | Remember me |
| onboarding_completed_at | TIMESTAMP | Ya | NULL | Waktu setup selesai |
| last_login_at | TIMESTAMP | Ya | NULL | Login terakhir |
| is_active | BOOLEAN | Tidak | TRUE | Status akun |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Constraint

- `email` unique.
- Hanya satu user aktif pada MVP.

## Index

```text
UNIQUE(email)
INDEX(is_active)
INDEX(onboarding_completed_at)
```

---

# 7. user_preferences

## Fungsi

Menyimpan preferensi pengguna.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Relasi user |
| timezone | VARCHAR(50) | Tidak | Asia/Jakarta | Zona waktu |
| locale | VARCHAR(10) | Tidak | id | Bahasa |
| currency | CHAR(3) | Tidak | IDR | Mata uang |
| date_format | VARCHAR(20) | Tidak | d/m/Y | Format tanggal |
| time_format | VARCHAR(10) | Tidak | H:i | Format waktu |
| appearance | VARCHAR(20) | Tidak | system | light, dark, system |
| reduced_motion | BOOLEAN | Tidak | FALSE | Kurangi animasi |
| hide_balances | BOOLEAN | Tidak | FALSE | Sembunyikan saldo |
| sidebar_collapsed | BOOLEAN | Tidak | FALSE | Sidebar desktop |
| opening_animation_enabled | BOOLEAN | Tidak | TRUE | Animasi pembuka |
| default_expense_account_id | BIGINT UNSIGNED | Ya | NULL | Akun pengeluaran default |
| default_income_account_id | BIGINT UNSIGNED | Ya | NULL | Akun pemasukan default |
| default_payment_method | VARCHAR(50) | Ya | NULL | Metode default |
| low_balance_global_threshold | DECIMAL(18,2) | Ya | NULL | Batas global |
| allow_negative_balance | BOOLEAN | Tidak | FALSE | Kebijakan saldo negatif |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |

## Constraint

- `user_id` unique.
- Foreign key ke users.
- Akun default harus milik user yang sama, divalidasi pada service.

## Index

```text
UNIQUE(user_id)
INDEX(default_expense_account_id)
INDEX(default_income_account_id)
```

---

# 8. onboarding_progress

## Fungsi

Menyimpan progres onboarding agar tidak hilang saat refresh.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Relasi user |
| current_step | VARCHAR(50) | Tidak | welcome | Langkah aktif |
| profile_completed | BOOLEAN | Tidak | FALSE | Profil selesai |
| preferences_completed | BOOLEAN | Tidak | FALSE | Preferensi selesai |
| accounts_completed | BOOLEAN | Tidak | FALSE | Akun selesai |
| balances_completed | BOOLEAN | Tidak | FALSE | Saldo awal selesai |
| review_completed | BOOLEAN | Tidak | FALSE | Review selesai |
| draft_data | JSON | Ya | NULL | Data draft nonkritis |
| completed_at | TIMESTAMP | Ya | NULL | Waktu selesai |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |

## Index

```text
UNIQUE(user_id)
INDEX(current_step)
```

---

# 9. categories

## Fungsi

Menyimpan kategori kegiatan, pengeluaran, pemasukan, dan catatan.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik kategori |
| type | VARCHAR(30) | Tidak | - | activity, expense, income, note |
| name | VARCHAR(100) | Tidak | - | Nama kategori |
| slug | VARCHAR(120) | Tidak | - | Slug |
| icon | VARCHAR(100) | Ya | NULL | Nama ikon |
| color | VARCHAR(20) | Ya | NULL | Hex atau token |
| description | TEXT | Ya | NULL | Deskripsi |
| is_system | BOOLEAN | Tidak | FALSE | Kategori bawaan |
| is_active | BOOLEAN | Tidak | TRUE | Status |
| sort_order | SMALLINT UNSIGNED | Tidak | 0 | Urutan |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Constraint

Unique per user, type, dan slug.

```text
UNIQUE(user_id, type, slug)
```

## Index

```text
INDEX(user_id, type, is_active)
INDEX(name)
```

---

# 10. accounts

## Fungsi

Menyimpan rekening bank, bank digital, e-wallet, dan cash.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik akun |
| name | VARCHAR(100) | Tidak | - | Nama tampilan |
| institution_name | VARCHAR(150) | Ya | NULL | Nama institusi |
| account_type | VARCHAR(30) | Tidak | - | bank, digital_bank, e_wallet, cash |
| purpose_label | VARCHAR(100) | Ya | NULL | Pribadi, kampus, dll |
| masked_identifier | VARCHAR(100) | Ya | NULL | Nomor tersamarkan |
| currency | CHAR(3) | Tidak | IDR | Mata uang |
| initial_balance | DECIMAL(18,2) | Tidak | 0.00 | Saldo awal |
| initial_balance_date | DATE | Tidak | - | Tanggal berlaku |
| cached_balance | DECIMAL(18,2) | Tidak | 0.00 | Cache saldo saat ini |
| minimum_balance | DECIMAL(18,2) | Ya | NULL | Batas minimum |
| color | VARCHAR(20) | Ya | NULL | Warna |
| icon | VARCHAR(100) | Ya | NULL | Ikon |
| include_in_total | BOOLEAN | Tidak | TRUE | Masuk total saldo |
| is_active | BOOLEAN | Tidak | TRUE | Status |
| sort_order | SMALLINT UNSIGNED | Tidak | 0 | Urutan |
| notes | TEXT | Ya | NULL | Catatan |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Aturan

- `cached_balance` diperbarui melalui service.
- Saldo sumber kebenaran tetap berasal dari ledger.
- `initial_balance` bukan income.
- Akun dengan transaksi tidak boleh hard delete.
- `initial_balance_date` wajib.
- Saldo negatif mengikuti preferensi user.

## Index

```text
INDEX(user_id, is_active)
INDEX(user_id, include_in_total)
INDEX(account_type)
INDEX(initial_balance_date)
```

---

# 11. account_balance_snapshots

## Fungsi

Menyimpan snapshot saldo periodik untuk mempercepat laporan dan rekonsiliasi.

Tabel ini opsional pada MVP awal, tetapi disiapkan dalam desain.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| account_id | BIGINT UNSIGNED | Tidak | - | Relasi akun |
| snapshot_date | DATE | Tidak | - | Tanggal snapshot |
| balance | DECIMAL(18,2) | Tidak | - | Saldo saat itu |
| source | VARCHAR(30) | Tidak | system | system, manual, reconciliation |
| created_at | TIMESTAMP | Tidak | - | Dibuat |

## Constraint

```text
UNIQUE(account_id, snapshot_date, source)
```

## Index

```text
INDEX(snapshot_date)
INDEX(account_id, snapshot_date)
```

---

# 12. transactions

## Fungsi

Menyimpan header transaksi keuangan.

Transaksi utama:

- Income.
- Expense.
- Internal Transfer.
- Balance Adjustment.
- Refund.
- Cashback.
- Account Fee.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| parent_transaction_id | BIGINT UNSIGNED | Ya | NULL | Refund/reversal/related |
| transfer_group_uuid | CHAR(36) | Ya | NULL | Grup transfer |
| category_id | BIGINT UNSIGNED | Ya | NULL | Kategori |
| type | VARCHAR(30) | Tidak | - | Jenis transaksi |
| status | VARCHAR(30) | Tidak | completed | Status |
| source | VARCHAR(40) | Tidak | manual | Sumber input |
| title | VARCHAR(191) | Ya | NULL | Judul singkat |
| description | TEXT | Ya | NULL | Tujuan/deskripsi |
| merchant_name | VARCHAR(191) | Ya | NULL | Merchant |
| sender_name | VARCHAR(191) | Ya | NULL | Pengirim |
| recipient_name | VARCHAR(191) | Ya | NULL | Penerima |
| payment_method | VARCHAR(50) | Ya | NULL | Metode |
| necessity_level | VARCHAR(30) | Ya | NULL | Kebutuhan |
| main_amount | DECIMAL(18,2) | Tidak | 0.00 | Nominal utama |
| admin_fee | DECIMAL(18,2) | Tidak | 0.00 | Biaya admin |
| tax_amount | DECIMAL(18,2) | Tidak | 0.00 | Pajak |
| discount_amount | DECIMAL(18,2) | Tidak | 0.00 | Diskon |
| cashback_amount | DECIMAL(18,2) | Tidak | 0.00 | Cashback langsung |
| total_amount | DECIMAL(18,2) | Tidak | 0.00 | Total final |
| transaction_date | DATE | Tidak | - | Tanggal |
| transaction_time | TIME | Ya | NULL | Waktu |
| occurred_at | DATETIME | Tidak | - | Timestamp gabungan |
| reference_number | VARCHAR(191) | Ya | NULL | Referensi |
| location | VARCHAR(191) | Ya | NULL | Lokasi |
| notes | TEXT | Ya | NULL | Catatan |
| metadata | JSON | Ya | NULL | Metadata fleksibel |
| confirmed_at | TIMESTAMP | Ya | NULL | Konfirmasi |
| cancelled_at | TIMESTAMP | Ya | NULL | Dibatalkan |
| refunded_at | TIMESTAMP | Ya | NULL | Refund |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Aturan Total

Expense:

```text
total_amount =
main_amount
+ admin_fee
+ tax_amount
- discount_amount
- cashback_amount
```

Income:

```text
total_amount = main_amount
```

Transfer:

```text
total_amount = main_amount
```

Biaya admin transfer dicatat melalui entry expense terpisah atau entry ledger terpisah.

## Index

```text
INDEX(user_id, occurred_at)
INDEX(user_id, type, status)
INDEX(category_id)
INDEX(merchant_name)
INDEX(reference_number)
INDEX(transfer_group_uuid)
INDEX(parent_transaction_id)
INDEX(source)
```

---

# 13. transaction_entries

## Fungsi

Menyimpan ledger movement untuk setiap akun.

Tabel ini adalah sumber utama perubahan saldo.

Satu transaksi dapat memiliki satu atau lebih entry.

## Contoh

### Expense Rp50.000 dari BCA

```text
Account BCA
direction = debit
amount = 50.000
```

### Income Rp500.000 ke BCA

```text
Account BCA
direction = credit
amount = 500.000
```

### Transfer BCA ke SeaBank Rp500.000 + admin Rp2.500

```text
BCA      debit  500.000  transfer_principal
SeaBank  credit 500.000  transfer_principal
BCA      debit    2.500  admin_fee
```

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| transaction_id | BIGINT UNSIGNED | Tidak | - | Header transaksi |
| account_id | BIGINT UNSIGNED | Tidak | - | Akun |
| entry_type | VARCHAR(40) | Tidak | - | principal, admin_fee, tax, adjustment, refund |
| direction | VARCHAR(10) | Tidak | - | debit atau credit |
| amount | DECIMAL(18,2) | Tidak | - | Nilai positif |
| balance_before | DECIMAL(18,2) | Tidak | - | Saldo sebelum |
| balance_after | DECIMAL(18,2) | Tidak | - | Saldo sesudah |
| effective_at | DATETIME | Tidak | - | Waktu berlaku |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Constraint

- `amount > 0` divalidasi aplikasi.
- `direction` hanya debit/credit.
- Entry hanya dibuat untuk transaksi final.
- Satu account tidak boleh memiliki entry duplikat untuk tipe yang sama tanpa alasan.

## Index

```text
INDEX(account_id, effective_at)
INDEX(transaction_id)
INDEX(account_id, direction)
INDEX(entry_type)
```

---

# 14. Aturan Ledger dan Saldo

## 14.1 Definisi Direction

- `credit`: menambah saldo.
- `debit`: mengurangi saldo.

## 14.2 Rumus Saldo

```text
Current Balance =
Initial Balance
+ Sum(Credit Entries)
- Sum(Debit Entries)
```

## 14.3 Cached Balance

`accounts.cached_balance` diperbarui setelah entry berhasil dibuat.

Urutan:

```text
Lock account row
→ Ambil cached_balance
→ Hitung balance_after
→ Simpan transaction entry
→ Update cached_balance
→ Commit database transaction
```

## 14.4 Rekalkulasi

Jika terjadi edit atau delete:

```text
Ambil seluruh entry akun dari tanggal terdampak
→ Urutkan berdasarkan effective_at dan id
→ Hitung ulang balance_before dan balance_after
→ Update cached_balance
```

## 14.5 Larangan

- Tidak boleh update `cached_balance` langsung dari controller.
- Tidak boleh menghapus entry tanpa rekalkulasi.
- Tidak boleh membuat entry untuk draft.
- Tidak boleh menggunakan float.

---

# 15. transaction_attachments

## Fungsi

Menyimpan metadata lampiran transaksi.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| transaction_id | BIGINT UNSIGNED | Ya | NULL | Relasi transaksi |
| scan_document_id | BIGINT UNSIGNED | Ya | NULL | Relasi scan |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| original_name | VARCHAR(255) | Tidak | - | Nama asli |
| stored_name | VARCHAR(255) | Tidak | - | Nama aman |
| storage_disk | VARCHAR(50) | Tidak | private | Disk |
| storage_path | VARCHAR(500) | Tidak | - | Path |
| mime_type | VARCHAR(100) | Tidak | - | MIME |
| extension | VARCHAR(20) | Ya | NULL | Ekstensi |
| file_size | BIGINT UNSIGNED | Tidak | 0 | Byte |
| checksum | VARCHAR(128) | Ya | NULL | Hash file |
| attachment_type | VARCHAR(50) | Tidak | receipt | receipt, transfer_proof, invoice |
| is_primary | BOOLEAN | Tidak | FALSE | Lampiran utama |
| uploaded_at | TIMESTAMP | Tidak | - | Waktu unggah |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Index

```text
INDEX(transaction_id)
INDEX(scan_document_id)
INDEX(user_id)
INDEX(checksum)
```

---

# 16. budgets

## Fungsi

Menyimpan definisi anggaran.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| category_id | BIGINT UNSIGNED | Tidak | - | Kategori expense |
| name | VARCHAR(150) | Tidak | - | Nama anggaran |
| amount | DECIMAL(18,2) | Tidak | - | Batas |
| period_type | VARCHAR(20) | Tidak | monthly | weekly, monthly, custom |
| warning_threshold_percent | DECIMAL(5,2) | Tidak | 80.00 | Peringatan |
| start_date | DATE | Tidak | - | Mulai |
| end_date | DATE | Tidak | - | Selesai |
| is_recurring | BOOLEAN | Tidak | FALSE | Berulang |
| is_active | BOOLEAN | Tidak | TRUE | Status |
| notes | TEXT | Ya | NULL | Catatan |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Index

```text
INDEX(user_id, is_active)
INDEX(category_id)
INDEX(start_date, end_date)
```

---

# 17. budget_periods

## Fungsi

Menyimpan realisasi anggaran per periode.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| budget_id | BIGINT UNSIGNED | Tidak | - | Relasi budget |
| period_start | DATE | Tidak | - | Awal |
| period_end | DATE | Tidak | - | Akhir |
| budget_amount | DECIMAL(18,2) | Tidak | - | Batas periode |
| used_amount | DECIMAL(18,2) | Tidak | 0.00 | Terpakai |
| remaining_amount | DECIMAL(18,2) | Tidak | 0.00 | Sisa |
| usage_percent | DECIMAL(7,2) | Tidak | 0.00 | Persentase |
| status | VARCHAR(30) | Tidak | safe | safe, warning, exceeded |
| calculated_at | TIMESTAMP | Ya | NULL | Kalkulasi |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |

## Constraint

```text
UNIQUE(budget_id, period_start, period_end)
```

---

# 18. activities

## Fungsi

Menyimpan kegiatan pengguna.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| category_id | BIGINT UNSIGNED | Ya | NULL | Kategori |
| title | VARCHAR(191) | Tidak | - | Judul |
| description | TEXT | Ya | NULL | Deskripsi |
| status | VARCHAR(30) | Tidak | planned | Status |
| importance_level | TINYINT UNSIGNED | Tidak | 3 | 1–5 |
| impact_level | TINYINT UNSIGNED | Tidak | 3 | 1–5 |
| estimated_duration_minutes | INT UNSIGNED | Ya | NULL | Estimasi |
| actual_duration_minutes | INT UNSIGNED | Ya | NULL | Aktual |
| start_date | DATE | Ya | NULL | Mulai |
| scheduled_at | DATETIME | Ya | NULL | Jadwal |
| deadline_at | DATETIME | Ya | NULL | Deadline |
| started_at | DATETIME | Ya | NULL | Mulai aktual |
| completed_at | DATETIME | Ya | NULL | Selesai |
| cancelled_at | DATETIME | Ya | NULL | Dibatalkan |
| postponed_count | SMALLINT UNSIGNED | Tidak | 0 | Jumlah tunda |
| priority_score | DECIMAL(6,2) | Tidak | 0.00 | Skor |
| priority_level | VARCHAR(20) | Tidak | low | Level |
| priority_reason | TEXT | Ya | NULL | Alasan |
| notes | TEXT | Ya | NULL | Catatan |
| metadata | JSON | Ya | NULL | Data tambahan |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Validasi

- importance 1–5.
- impact 1–5.
- duration positif.
- completed_at hanya untuk completed.
- cancelled_at hanya untuk cancelled.
- deadline boleh kosong.
- scheduled_at sebaiknya tidak setelah deadline tanpa peringatan.

## Index

```text
INDEX(user_id, status)
INDEX(user_id, priority_level)
INDEX(deadline_at)
INDEX(scheduled_at)
INDEX(category_id)
INDEX(priority_score)
```

---

# 19. activity_status_histories

## Fungsi

Menyimpan riwayat perubahan status kegiatan.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| activity_id | BIGINT UNSIGNED | Tidak | - | Relasi |
| from_status | VARCHAR(30) | Ya | NULL | Status awal |
| to_status | VARCHAR(30) | Tidak | - | Status baru |
| reason | TEXT | Ya | NULL | Alasan |
| previous_scheduled_at | DATETIME | Ya | NULL | Jadwal lama |
| new_scheduled_at | DATETIME | Ya | NULL | Jadwal baru |
| previous_deadline_at | DATETIME | Ya | NULL | Deadline lama |
| new_deadline_at | DATETIME | Ya | NULL | Deadline baru |
| changed_at | TIMESTAMP | Tidak | - | Waktu |
| created_at | TIMESTAMP | Tidak | - | Dibuat |

## Index

```text
INDEX(activity_id, changed_at)
INDEX(to_status)
```

---

# 20. activity_priority_histories

## Fungsi

Menyimpan perubahan skor prioritas.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| activity_id | BIGINT UNSIGNED | Tidak | - | Relasi |
| previous_score | DECIMAL(6,2) | Ya | NULL | Skor lama |
| new_score | DECIMAL(6,2) | Tidak | - | Skor baru |
| previous_level | VARCHAR(20) | Ya | NULL | Level lama |
| new_level | VARCHAR(20) | Tidak | - | Level baru |
| factors | JSON | Tidak | - | Faktor skor |
| explanation | TEXT | Tidak | - | Penjelasan |
| calculated_at | TIMESTAMP | Tidak | - | Waktu |
| created_at | TIMESTAMP | Tidak | - | Dibuat |

## Index

```text
INDEX(activity_id, calculated_at)
INDEX(new_level)
```

---

# 21. recommendations

## Fungsi

Menyimpan rekomendasi berbasis aturan dan adaptif.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| related_type | VARCHAR(100) | Ya | NULL | Polymorphic type |
| related_id | BIGINT UNSIGNED | Ya | NULL | Polymorphic id |
| type | VARCHAR(50) | Tidak | - | activity, finance, budget, account |
| rule_code | VARCHAR(100) | Tidak | - | Kode aturan |
| title | VARCHAR(191) | Tidak | - | Judul |
| message | TEXT | Tidak | - | Isi |
| reason | TEXT | Tidak | - | Alasan |
| priority | VARCHAR(20) | Tidak | medium | Prioritas |
| confidence_score | DECIMAL(5,2) | Ya | NULL | 0–100 |
| supporting_data | JSON | Ya | NULL | Data pendukung |
| suggested_action | VARCHAR(100) | Ya | NULL | Aksi |
| status | VARCHAR(30) | Tidak | active | Status |
| generated_at | TIMESTAMP | Tidak | - | Dibuat |
| expires_at | TIMESTAMP | Ya | NULL | Kedaluwarsa |
| acted_at | TIMESTAMP | Ya | NULL | Ditindak |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Aturan

- `reason` wajib.
- Duplikasi dicegah menggunakan kombinasi rule, related object, dan periode aktif.
- Recommendation expired tidak ditampilkan sebagai aktif.

## Index

```text
INDEX(user_id, status)
INDEX(type, priority)
INDEX(rule_code)
INDEX(related_type, related_id)
INDEX(expires_at)
```

---

# 22. recommendation_feedback

## Fungsi

Menyimpan respons pengguna terhadap rekomendasi.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| recommendation_id | BIGINT UNSIGNED | Tidak | - | Relasi |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| response | VARCHAR(30) | Tidak | - | followed, scheduled, postponed, ignored, not_relevant |
| reason | TEXT | Ya | NULL | Alasan |
| scheduled_for | DATETIME | Ya | NULL | Jika dijadwalkan |
| outcome | VARCHAR(50) | Ya | NULL | Hasil |
| metadata | JSON | Ya | NULL | Data tambahan |
| responded_at | TIMESTAMP | Tidak | - | Waktu |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |

## Index

```text
INDEX(recommendation_id)
INDEX(user_id, response)
INDEX(responded_at)
```

---

# 23. notes

## Fungsi

Menyimpan catatan singkat.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| category_id | BIGINT UNSIGNED | Ya | NULL | Kategori |
| title | VARCHAR(191) | Ya | NULL | Judul |
| content | LONGTEXT | Tidak | - | Isi |
| is_pinned | BOOLEAN | Tidak | FALSE | Pin |
| color | VARCHAR(20) | Ya | NULL | Label warna |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Index

```text
INDEX(user_id, is_pinned)
INDEX(category_id)
FULLTEXT(title, content)
```

---

# 24. notifications

## Fungsi

Menyimpan notifikasi dalam aplikasi.

Laravel notification table dapat digunakan dan diperluas.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | CHAR(36) | Tidak | UUID | Primary key |
| type | VARCHAR(255) | Tidak | - | Notification class |
| notifiable_type | VARCHAR(255) | Tidak | - | Model |
| notifiable_id | BIGINT UNSIGNED | Tidak | - | User |
| data | JSON | Tidak | - | Isi |
| read_at | TIMESTAMP | Ya | NULL | Dibaca |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |

## Index

```text
INDEX(notifiable_type, notifiable_id)
INDEX(read_at)
```

---

# 25. scan_documents

## Fungsi

Menyimpan proses pemindaian dokumen.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| attachment_id | BIGINT UNSIGNED | Ya | NULL | File sumber |
| transaction_id | BIGINT UNSIGNED | Ya | NULL | Hasil final |
| document_type | VARCHAR(50) | Tidak | unknown | receipt, transfer_proof, payment_proof |
| status | VARCHAR(30) | Tidak | uploaded | Status |
| ocr_provider | VARCHAR(50) | Ya | NULL | Provider |
| ocr_raw_text | LONGTEXT | Ya | NULL | Hasil mentah |
| overall_confidence | DECIMAL(5,2) | Ya | NULL | 0–100 |
| failure_reason | TEXT | Ya | NULL | Error |
| processed_at | TIMESTAMP | Ya | NULL | Diproses |
| reviewed_at | TIMESTAMP | Ya | NULL | Ditinjau |
| confirmed_at | TIMESTAMP | Ya | NULL | Dikonfirmasi |
| rejected_at | TIMESTAMP | Ya | NULL | Ditolak |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |
| deleted_at | TIMESTAMP | Ya | NULL | Soft delete |

## Index

```text
INDEX(user_id, status)
INDEX(transaction_id)
INDEX(document_type)
INDEX(created_at)
```

---

# 26. scan_extractions

## Fungsi

Menyimpan hasil ekstraksi per field.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| scan_document_id | BIGINT UNSIGNED | Tidak | - | Relasi scan |
| field_name | VARCHAR(100) | Tidak | - | merchant, amount, date |
| raw_value | TEXT | Ya | NULL | Nilai mentah |
| normalized_value | TEXT | Ya | NULL | Nilai normal |
| confidence_score | DECIMAL(5,2) | Ya | NULL | 0–100 |
| bounding_box | JSON | Ya | NULL | Posisi |
| needs_review | BOOLEAN | Tidak | TRUE | Perlu cek |
| user_corrected_value | TEXT | Ya | NULL | Koreksi |
| is_accepted | BOOLEAN | Tidak | FALSE | Diterima |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |

## Constraint

```text
UNIQUE(scan_document_id, field_name)
```

## Index

```text
INDEX(scan_document_id)
INDEX(field_name)
INDEX(needs_review)
```

---

# 27. activity_logs

## Fungsi

Menyimpan audit trail umum.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Ya | NULL | Aktor |
| subject_type | VARCHAR(191) | Ya | NULL | Model terkait |
| subject_id | BIGINT UNSIGNED | Ya | NULL | ID terkait |
| action | VARCHAR(100) | Tidak | - | create, update, delete |
| description | TEXT | Ya | NULL | Ringkasan |
| old_values | JSON | Ya | NULL | Sebelum |
| new_values | JSON | Ya | NULL | Sesudah |
| metadata | JSON | Ya | NULL | Tambahan |
| ip_address | VARCHAR(45) | Ya | NULL | IP |
| user_agent | TEXT | Ya | NULL | Browser |
| created_at | TIMESTAMP | Tidak | - | Dibuat |

## Index

```text
INDEX(user_id, created_at)
INDEX(subject_type, subject_id)
INDEX(action)
```

---

# 28. login_histories

## Fungsi

Menyimpan riwayat login.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Ya | NULL | User |
| email_attempted | VARCHAR(191) | Ya | NULL | Email |
| was_successful | BOOLEAN | Tidak | FALSE | Hasil |
| ip_address | VARCHAR(45) | Ya | NULL | IP |
| user_agent | TEXT | Ya | NULL | Browser |
| failure_reason | VARCHAR(191) | Ya | NULL | Alasan |
| logged_in_at | TIMESTAMP | Tidak | - | Waktu |

## Index

```text
INDEX(user_id, logged_in_at)
INDEX(email_attempted)
INDEX(was_successful)
```

---

# 29. backups

## Fungsi

Menyimpan metadata backup.

## Struktur

| Kolom | Tipe | Null | Default | Keterangan |
|---|---|---:|---|---|
| id | BIGINT UNSIGNED | Tidak | Auto | Primary key |
| user_id | BIGINT UNSIGNED | Tidak | - | Pemilik |
| type | VARCHAR(30) | Tidak | manual | manual, scheduled, export |
| status | VARCHAR(30) | Tidak | pending | Status |
| storage_disk | VARCHAR(50) | Ya | NULL | Disk |
| storage_path | VARCHAR(500) | Ya | NULL | Path |
| file_size | BIGINT UNSIGNED | Ya | NULL | Ukuran |
| checksum | VARCHAR(128) | Ya | NULL | Hash |
| started_at | TIMESTAMP | Ya | NULL | Mulai |
| completed_at | TIMESTAMP | Ya | NULL | Selesai |
| failed_at | TIMESTAMP | Ya | NULL | Gagal |
| failure_reason | TEXT | Ya | NULL | Alasan |
| created_at | TIMESTAMP | Tidak | - | Dibuat |
| updated_at | TIMESTAMP | Tidak | - | Diperbarui |

## Index

```text
INDEX(user_id, created_at)
INDEX(status)
```

---

# 30. Enum Aplikasi

Disarankan menggunakan PHP backed enum dan kolom database `VARCHAR`.

## AccountType

```text
bank
digital_bank
e_wallet
cash
```

## TransactionType

```text
income
expense
internal_transfer
balance_adjustment
refund
cashback
account_fee
```

## TransactionStatus

```text
draft
pending
completed
failed
cancelled
refunded
```

## TransactionSource

```text
manual
receipt_scan
payment_proof_scan
transfer_proof_scan
imported
```

## EntryDirection

```text
credit
debit
```

## ActivityStatus

```text
planned
in_progress
completed
postponed
cancelled
```

## PriorityLevel

```text
low
medium
high
critical
```

## NecessityLevel

```text
essential
important
optional
unnecessary
```

## RecommendationStatus

```text
active
followed
scheduled
postponed
ignored
not_relevant
expired
```

## ScanStatus

```text
uploaded
processing
needs_review
confirmed
rejected
failed
```

---

# 31. Foreign Key Rules

## Restrict

Gunakan `RESTRICT` untuk:

- Account yang memiliki transaction entry.
- Category yang masih digunakan.
- Transaction yang memiliki ledger entry.

## Cascade

Gunakan `CASCADE` secara terbatas untuk:

- Status history ketika activity dihapus permanen.
- Priority history ketika activity dihapus permanen.
- Scan extraction ketika scan document dihapus permanen.
- Recommendation feedback ketika recommendation dihapus permanen.

## Set Null

Gunakan `SET NULL` untuk:

- Category pada activity atau transaction jika kategori diarsipkan.
- Related object recommendation bila objek hilang.
- Parent transaction tertentu jika diperlukan.

Catatan: pada data keuangan, hard delete sebisa mungkin dihindari.

---

# 32. Index Strategis

## Transaksi

```text
(user_id, occurred_at)
(user_id, type, status)
(category_id, occurred_at)
(merchant_name)
(reference_number)
```

## Ledger

```text
(account_id, effective_at)
(transaction_id)
```

## Activities

```text
(user_id, status, deadline_at)
(user_id, priority_score)
```

## Recommendations

```text
(user_id, status, priority)
(rule_code, related_type, related_id)
```

## Search

- FULLTEXT notes.
- Merchant dan transaction title menggunakan index biasa.
- Global search awal dapat menggunakan LIKE terkontrol.
- Search engine khusus belum diperlukan pada MVP.

---

# 33. Data Awal Seeder

## User

Seeder membuat satu user demo/development.

Production password tidak boleh disimpan di repository.

## Default Accounts

```text
BCA
Mandiri
BNI Pribadi
BNI Mahasiswa
SeaBank
Cash
```

Saldo awal tidak diisi oleh seeder production.

## Default Activity Categories

```text
Kuliah
Proyek
Belajar
Pribadi
Administrasi
Lainnya
```

## Default Expense Categories

```text
Makanan dan Minuman
Transportasi
Pendidikan
Kuliah
Teknologi
Internet
Hiburan
Kesehatan
Belanja
Langganan
Biaya Administrasi
Pajak
Lainnya
```

## Default Income Categories

```text
Uang Bulanan
Beasiswa
Freelance
Penjualan
Refund
Bunga
Hadiah
Lainnya
```

## Default Note Categories

```text
Umum
Ide
Kuliah
Proyek
Keuangan
```

---

# 34. Migration Order

Urutan migration yang disarankan:

```text
1. users
2. user_preferences
3. onboarding_progress
4. categories
5. accounts
6. transactions
7. transaction_entries
8. transaction_attachments
9. budgets
10. budget_periods
11. activities
12. activity_status_histories
13. activity_priority_histories
14. recommendations
15. recommendation_feedback
16. notes
17. notifications
18. scan_documents
19. scan_extractions
20. account_balance_snapshots
21. activity_logs
22. login_histories
23. backups
```

Foreign key tambahan yang circular dapat dibuat melalui migration terpisah.

---

# 35. Model Eloquent

Model utama:

```text
User
UserPreference
OnboardingProgress
Category
Account
Transaction
TransactionEntry
TransactionAttachment
Budget
BudgetPeriod
Activity
ActivityStatusHistory
ActivityPriorityHistory
Recommendation
RecommendationFeedback
Note
ScanDocument
ScanExtraction
ActivityLog
LoginHistory
Backup
```

---

# 36. Relasi Utama

## User

```text
User hasOne UserPreference
User hasOne OnboardingProgress
User hasMany Accounts
User hasMany Categories
User hasMany Transactions
User hasMany Activities
User hasMany Recommendations
User hasMany Notes
```

## Account

```text
Account belongsTo User
Account hasMany TransactionEntries
Account hasMany BalanceSnapshots
```

## Transaction

```text
Transaction belongsTo User
Transaction belongsTo Category
Transaction belongsTo ParentTransaction
Transaction hasMany TransactionEntries
Transaction hasMany Attachments
Transaction hasMany ChildTransactions
```

## Activity

```text
Activity belongsTo User
Activity belongsTo Category
Activity hasMany StatusHistories
Activity hasMany PriorityHistories
Activity morphMany Recommendations
```

## Recommendation

```text
Recommendation belongsTo User
Recommendation morphTo Related
Recommendation hasMany Feedback
```

---

# 37. Aturan Transaksi Database

Operasi berikut wajib berada dalam `DB::transaction()`:

- Create expense.
- Create income.
- Create transfer.
- Create balance adjustment.
- Edit transaction.
- Cancel transaction.
- Delete/archive transaction.
- Refund.
- Recalculate account balance.
- Confirm scan transaction.

## Contoh Alur Expense

```text
Begin transaction
→ Lock account
→ Validate balance
→ Create transaction header
→ Calculate total
→ Create debit entry
→ Update cached balance
→ Create audit log
→ Commit
```

## Contoh Alur Transfer

```text
Begin transaction
→ Lock source and destination accounts
→ Validate accounts
→ Validate balance
→ Create transfer transaction
→ Create source debit entry
→ Create destination credit entry
→ Create admin debit entry if needed
→ Update both cached balances
→ Create audit log
→ Commit
```

---

# 38. Concurrency dan Row Locking

Untuk mencegah saldo tidak konsisten:

```text
SELECT ... FOR UPDATE
```

Gunakan pada account yang terlibat sebelum membuat entry.

Urutan lock transfer harus konsisten berdasarkan account ID agar mengurangi risiko deadlock.

Contoh:

```text
Sort source and destination account IDs
→ Lock account dengan ID terkecil
→ Lock account berikutnya
```

---

# 39. Soft Delete Strategy

Soft delete digunakan untuk:

- Users.
- Categories.
- Accounts.
- Transactions.
- Transaction entries.
- Attachments.
- Activities.
- Recommendations.
- Notes.
- Scan documents.
- Budgets.

Data soft-deleted:

- Tidak tampil di UI normal.
- Tetap tersedia untuk audit.
- Tidak mengubah saldo tanpa proses reversal/recalculation.
- Hanya dapat dihapus permanen melalui prosedur khusus.

---

# 40. Audit Strategy

Setiap perubahan penting menyimpan:

- User.
- Action.
- Subject.
- Nilai lama.
- Nilai baru.
- Timestamp.
- IP.
- User agent.
- Metadata.

Khusus transaksi:

- Nominal lama.
- Nominal baru.
- Saldo terdampak.
- Alasan edit.
- Waktu.
- Entry sebelum dan sesudah.

---

# 41. Keamanan Data

## Password

- Hash menggunakan Laravel Hash.
- Tidak pernah disimpan plaintext.

## Nomor Rekening

- Simpan hanya nomor tersamarkan untuk MVP.
- Hindari menyimpan nomor lengkap jika tidak diperlukan.

## Lampiran

- Private disk.
- Signed atau authenticated route.
- Validasi MIME.
- Batas ukuran.
- Nama acak.
- Checksum.

## Logs

Jangan menyimpan:

- Password.
- Token.
- Nomor rekening lengkap.
- Isi file sensitif.
- Session cookie.

---

# 42. Precision Nominal

Gunakan:

```text
DECIMAL(18,2)
```

Cukup untuk nominal IDR personal.

Di PHP:

- Gunakan string atau integer minor units dalam service jika diperlukan.
- Jangan melakukan kalkulasi uang dengan float.
- Pertimbangkan library money untuk fase lanjutan.

---

# 43. Timezone

Database menyimpan DATETIME/TIMESTAMP konsisten.

Strategi:

```text
Simpan UTC
Tampilkan Asia/Jakarta
```

Tanggal transaksi lokal harus dikonversi dengan benar.

Field penting:

- `occurred_at`
- `effective_at`
- `deadline_at`
- `scheduled_at`

---

# 44. Retention dan Backup

## Retention

- Transaction dan ledger dipertahankan.
- Audit log dipertahankan minimal selama aplikasi digunakan.
- Scan raw text dapat dibersihkan setelah periode tertentu bila diperlukan.
- Attachment dapat diarsipkan.

## Backup

Backup mencakup:

- Database.
- Private attachments.
- Config non-secret.
- Metadata versi.

Backup tidak boleh mencakup:

- `.env` dalam repository.
- Cache.
- Log berlebihan.
- Build sementara.

---

# 45. Testing Database

## Unit Test

- Expense calculation.
- Income calculation.
- Transfer calculation.
- Balance adjustment.
- Cached balance.
- Recalculation.
- Priority score.
- Budget usage.

## Feature Test

- Create account.
- Create expense.
- Create transfer.
- Edit transaction.
- Cancel transaction.
- Soft delete.
- Attachment access.
- Scan confirmation.

## Integrity Test

- Foreign key.
- Unique constraint.
- Decimal precision.
- Row locking.
- Rollback.
- Duplicate transfer prevention.
- Status rules.

---

# 46. Scope MVP Database

## Wajib pada MVP

- users
- user_preferences
- onboarding_progress
- categories
- accounts
- transactions
- transaction_entries
- activities
- activity_status_histories
- activity_priority_histories
- recommendations
- recommendation_feedback
- activity_logs

## Setelah Keuangan Inti Stabil

- budgets
- budget_periods
- transaction_attachments
- notes
- notifications
- login_histories

## MVP Lanjutan

- scan_documents
- scan_extractions
- account_balance_snapshots
- backups

---

# 47. Keputusan Final Database

Keputusan final:

- MySQL digunakan.
- Nominal menggunakan DECIMAL(18,2).
- Ledger menggunakan transaction_entries.
- accounts.cached_balance hanya cache.
- Initial balance bukan income.
- Transfer internal memiliki beberapa ledger entry.
- Biaya admin transfer menjadi debit terpisah.
- Draft dan pending tidak mengubah saldo.
- Edit transaksi membutuhkan rekalkulasi.
- Data keuangan menggunakan soft delete.
- Semua operasi finansial menggunakan database transaction.
- Row locking digunakan pada account.
- Audit log wajib.
- Scan menghasilkan draft.
- Saldo berubah setelah konfirmasi.
- Enum aplikasi disimpan sebagai VARCHAR.
- Database menyimpan user_id meski single-user.
- Nomor rekening lengkap tidak wajib disimpan.
- Lampiran disimpan secara privat.

---

# 48. Acceptance Criteria

Desain database dianggap siap apabila:

- Semua fitur MVP memiliki tabel pendukung.
- Saldo dapat dihitung dari ledger.
- Transfer tidak menyebabkan double counting.
- Saldo awal tidak masuk income.
- Edit dan delete dapat direkalkulasi.
- Kegiatan mendukung priority engine.
- Rekomendasi mendukung feedback.
- Lampiran mendukung private storage.
- Scan mendukung confidence per field.
- Index utama sudah ditentukan.
- Foreign key rules jelas.
- Migration order tersedia.
- Model Eloquent dapat ditentukan.
- Data awal seeder tersedia.
- Testing database sudah direncanakan.

---

# 49. Tahap Berikutnya

Tahap berikutnya adalah membuat:

```text
docs/07-erd-specification.md
```

Dokumen ERD akan memvisualisasikan:

- Entitas.
- Primary key.
- Foreign key.
- Cardinality.
- Relasi inti.
- Relasi keuangan.
- Relasi kegiatan.
- Relasi rekomendasi.
- Relasi scan dan lampiran.

Setelah ERD disetujui, proyek siap masuk ke setup Laravel.
