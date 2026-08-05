# Release Notes — Laras v1.0.1

**Tanggal rilis:** 5 Agustus 2026  
**Jenis rilis:** Patch release  
**Status:** Stable

## Ringkasan

Laras v1.0.1 berfokus pada penyempurnaan interaksi inti, konsistensi data keuangan, aksesibilitas, dan kesiapan rilis. Versi ini memperkenalkan pemisahan transfer internal dan eksternal, memperkuat lifecycle transaksi, serta menyelesaikan pengurutan rekening dan aksi kegiatan tanpa reload penuh.

## Sorotan utama

### Transfer internal dan eksternal

Transfer sekarang memiliki dua tujuan yang jelas:

- **Antar-rekening Laras** memindahkan dana dari rekening sumber ke rekening tujuan Laras.
- **Ke pihak luar Laras** mengurangi saldo rekening sumber tanpa menambah saldo rekening Laras lain.

Transfer eksternal dapat menyimpan:

- nama penerima atau tujuan;
- bank atau institusi;
- nomor rekening, virtual account, atau identitas tujuan;
- biaya admin;
- nomor referensi dan catatan transaksi.

Riwayat dan detail transaksi menampilkan jenis transfer, sumber dana, tujuan, serta dampaknya pada saldo.

### Lifecycle transfer yang konsisten

Pembatalan dan penghapusan transfer diperlakukan sebagai satu kelompok ledger:

- pembatalan mengembalikan saldo rekening yang terdampak;
- pembatalan kedua tidak mengubah saldo lagi;
- penghapusan permanen hanya tersedia setelah transaksi dibatalkan;
- seluruh ledger entry dihapus dalam satu database transaction;
- saldo rekening dihitung ulang setelah penghapusan;
- penghapusan memerlukan password dan konfirmasi kuat.

### Interaksi kegiatan tanpa reload

Aksi kegiatan diperbarui agar berjalan secara asinkron:

- mulai;
- selesai;
- batalkan;
- buka kembali;
- arsip;
- pulihkan.

Daftar dan ringkasan diperbarui setelah server mengonfirmasi perubahan. Error request dipulihkan tanpa meninggalkan antarmuka dalam kondisi sibuk.

### Pengurutan rekening yang lebih tangguh

Pengurutan rekening kini mencakup:

- optimistic movement;
- server reconciliation;
- rollback ketika request gagal;
- global mutation lock;
- busy state daftar;
- perlindungan tombol batas;
- pemulihan fokus keyboard;
- reduced-motion support.

## Perbaikan antarmuka

- Ikon tujuan transfer dan dampak saldo tidak lagi tampil sebagai kotak kosong.
- Transfer eksternal tampil sebagai dana keluar.
- Field internal dan eksternal hanya aktif saat relevan.
- Input lama dipertahankan ketika validasi gagal.
- Tampilan telah diperiksa pada desktop, tablet, dan mobile.
- Akses file WebP yang sebelumnya menghasilkan response `403` telah diperbaiki.

## Keamanan dan integritas data

- Rekening tujuan transfer internal harus dimiliki oleh pengguna yang sama.
- Rekening sumber dan tujuan tidak boleh sama.
- Input rekening tujuan Laras diabaikan untuk transfer eksternal.
- Transfer gagal tidak meninggalkan ledger entry parsial.
- Penghapusan permanen dilindungi password dan konfirmasi eksplisit.
- Operasi sensitif menggunakan database transaction dan locking yang sesuai.

## Validasi rilis

Pemeriksaan berikut telah diselesaikan:

```text
php artisan test
php vendor/bin/phpunit -c phpunit.mysql.xml
php vendor/bin/pint --test
npm run build
composer quality:full
composer release:smoke
composer release:final
```

Production smoke test menghasilkan:

```text
0 failure
1 warning: MAIL_MAILER=log
```

Warning tersebut diterima untuk penggunaan personal. Mail transport nyata tetap diperlukan sebelum penggunaan oleh pengguna lain.

## Upgrade dari v1.0.0

1. Tarik source code v1.0.1.
2. Jalankan dependency installation bila lock file berubah.
3. Jalankan migration.
4. Buat ulang frontend production assets.
5. Bersihkan dan buat ulang cache production.
6. Jalankan release readiness dan smoke test.

```powershell
composer install --no-dev --optimize-autoloader
npm ci
php artisan migrate --force
npm run build
php artisan optimize:clear
php artisan optimize
composer release:smoke
```

Selalu lakukan backup database sebelum deployment.

## Keterbatasan yang masih berlaku

- Laras masih merupakan aplikasi single-user.
- OCR dan lampiran bukti transaksi belum tersedia.
- PWA penuh dan offline synchronization belum tersedia.
- Rekomendasi belum menggunakan machine-learning model.
- Mail transport nyata belum dikonfigurasi secara default.
