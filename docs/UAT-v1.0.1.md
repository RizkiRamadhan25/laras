# User Acceptance Test — Laras v1.0.1

**Tanggal pelaksanaan:** 5 Agustus 2026  
**Pelaksana:** Developer Laras  
**Status akhir:** LULUS

## Lingkup

UAT v1.0.1 memverifikasi:

- kegiatan asinkron;
- pengurutan rekening;
- transaksi pemasukan dan pengeluaran;
- transfer internal;
- transfer eksternal;
- pembatalan dan penghapusan transaksi;
- aksesibilitas;
- responsive layout;
- production readiness.

## Lingkungan pengujian

| Komponen | Nilai |
|---|---|
| Sistem operasi | Windows |
| Backend | Laravel 13 / PHP 8.3 |
| Database utama pengujian | MySQL |
| Frontend | Blade, Alpine.js, Tailwind CSS 4, Vite 8 |
| Browser | Chromium-based browser |
| Mode | Development dan production smoke environment |

## Hasil pengujian

### 1. Autentikasi dan onboarding

| Skenario | Hasil |
|---|---|
| Login pengguna aktif | PASS |
| Logout | PASS |
| Reset password tetap tersedia | PASS |
| Route pribadi terlindungi autentikasi | PASS |
| Onboarding pengguna baru | PASS |
| Registrasi publik tidak tersedia | PASS |

### 2. Kegiatan

| Skenario | Hasil |
|---|---|
| Mulai kegiatan tanpa reload penuh | PASS |
| Selesaikan kegiatan tanpa reload penuh | PASS |
| Batalkan dan buka kembali | PASS |
| Arsip dan pulihkan kegiatan | PASS |
| Status selesai tetap dipertahankan setelah restore | PASS |
| Ringkasan dan daftar diperbarui | PASS |
| Error request memulihkan busy state | PASS |
| Fokus keyboard dipulihkan | PASS |

### 3. Rekening

| Skenario | Hasil |
|---|---|
| Pindah rekening ke atas dan bawah | PASS |
| Urutan tetap setelah reload | PASS |
| Tombol batas pertama dan terakhir | PASS |
| Global lock mencegah request bersamaan | PASS |
| Rollback ketika jaringan gagal | PASS |
| Busy state dan aria state | PASS |
| Fokus keyboard setelah perpindahan | PASS |
| Reduced motion | PASS |

### 4. Transfer internal

| Skenario | Hasil |
|---|---|
| Rekening sumber berkurang | PASS |
| Rekening tujuan Laras bertambah | PASS |
| Biaya admin mengurangi sumber | PASS |
| Sumber dan tujuan yang sama ditolak | PASS |
| Rekening pengguna lain ditolak | PASS |
| Riwayat menampilkan jenis transfer | PASS |
| Detail menampilkan sumber dan tujuan | PASS |
| Pembatalan mengembalikan saldo | PASS |
| Penghapusan permanen menghapus satu kelompok ledger | PASS |

### 5. Transfer eksternal

| Skenario | Hasil |
|---|---|
| Rekening sumber berkurang | PASS |
| Rekening Laras lain tidak bertambah | PASS |
| Nama penerima wajib | PASS |
| Institusi dan nomor rekening tersimpan | PASS |
| Riwayat menampilkan dana keluar | PASS |
| Detail menampilkan tujuan eksternal | PASS |
| Biaya admin dicatat terpisah | PASS |
| Saldo tidak cukup ditolak | PASS |
| Kegagalan tidak meninggalkan entry parsial | PASS |
| Pembatalan dan penghapusan atomik | PASS |

### 6. Penghapusan permanen

| Skenario | Hasil |
|---|---|
| Hanya transaksi cancelled yang dapat dihapus | PASS |
| Password wajib | PASS |
| Teks `HAPUS TRANSAKSI` wajib | PASS |
| Persetujuan eksplisit wajib | PASS |
| Seluruh entry transaksi terhapus | PASS |
| Saldo dihitung ulang | PASS |
| Pengguna lain tidak memiliki akses | PASS |

### 7. Antarmuka dan responsive layout

| Resolusi | Hasil |
|---|---|
| 1920 × 1080 | PASS |
| 1366 × 768 | PASS |
| Tablet portrait | PASS |
| Tablet landscape | PASS |
| Mobile 360 px | PASS |
| Mobile 390 px | PASS |

Pemeriksaan visual:

- tidak ada horizontal overflow;
- label form tetap terbaca;
- tombol dapat dijangkau;
- dialog tidak keluar layar;
- toast tidak menutupi tindakan utama;
- ikon transfer tampil;
- tidak ada gambar Laras yang menghasilkan response `403`.

### 8. Quality dan release readiness

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit full regression | PASS |
| MySQL integration gate | PASS |
| Laravel Pint | PASS |
| Frontend build | PASS |
| `composer quality:full` | PASS |
| `composer release:smoke` | PASS |
| `composer release:final` | PASS |
| Health endpoint `/up` | PASS |
| Security headers production | PASS |
| Custom 404 page | PASS |
| Pending migrations | PASS — tidak ada |

## Catatan

Release readiness menghasilkan satu warning non-blocking:

```text
MAIL_MAILER=log
```

Warning diterima untuk penggunaan personal. Konfigurasi mail transport nyata wajib disiapkan sebelum Laras digunakan oleh pengguna lain.

## Kesimpulan

Seluruh acceptance criteria utama v1.0.1 dinyatakan lulus. Tidak ditemukan blocker yang mencegah rilis Laras v1.0.1.
