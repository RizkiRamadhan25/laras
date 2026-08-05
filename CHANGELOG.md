# Changelog

Semua perubahan penting Laras didokumentasikan pada file ini.

Format mengikuti prinsip Keep a Changelog dan versi menggunakan Semantic Versioning.

## [Unreleased]

### Planned

- OCR dan pemindaian bukti transaksi.
- Lampiran dokumen transaksi.
- Rekomendasi adaptif berbasis data penggunaan.
- PWA dan dukungan offline.
- Evaluasi fondasi multi-user private beta.

## [1.0.1] - 2026-08-05

### Added

- Pilihan transfer internal antar-rekening Laras dan transfer eksternal ke pihak di luar Laras.
- Informasi penerima, bank atau institusi, dan nomor rekening untuk transfer eksternal.
- Badge jenis transfer pada riwayat dan detail transaksi.
- Ringkasan dampak saldo pada form transfer.
- Penghapusan permanen transaksi yang telah dibatalkan.
- Konfirmasi kuat penghapusan transaksi melalui password, teks `HAPUS TRANSAKSI`, dan persetujuan eksplisit.
- Regression test khusus kontrak transfer, form, tampilan, lifecycle, ownership, dan atomic deletion.
- Dukungan reduced motion dan pemulihan fokus keyboard pada pengurutan rekening.
- Test kondisi batas pengurutan rekening.

### Changed

- Aksi kegiatan berjalan asinkron tanpa reload penuh.
- Ringkasan dan daftar kegiatan diperbarui setelah aksi berhasil.
- Pengurutan rekening berjalan secara optimistis dengan server reconciliation.
- Transfer eksternal hanya mengurangi saldo rekening sumber.
- Transfer internal tetap mengurangi rekening sumber dan menambah rekening tujuan Laras.
- Tampilan nominal transfer eksternal diperlakukan sebagai dana keluar.
- Dokumentasi rilis diperbarui untuk Laras v1.0.1.

### Fixed

- Rollback pengurutan rekening ketika request gagal.
- Global lock untuk mencegah mutation pengurutan rekening secara bersamaan.
- Busy state dan status tombol batas pada pengurutan rekening.
- Fokus keyboard setelah rekening berpindah.
- Status kegiatan tetap dipertahankan setelah arsip dan pemulihan.
- Ikon kosong pada bagian tujuan transfer dan dampak saldo.
- Tampilan tujuan transfer eksternal pada riwayat dan detail.
- Akses file WebP yang sebelumnya menghasilkan response `403`.
- Test tampilan transfer yang gagal karena ketergantungan kategori biaya admin.

### Security

- Ownership rekening sumber dan tujuan divalidasi pada transfer internal.
- Rekening pengguna lain tidak dapat dijadikan tujuan transfer.
- Penghapusan transfer dijalankan secara atomik sebagai satu kelompok ledger.
- Saldo rekening dihitung ulang setelah penghapusan permanen.
- Pembatalan kedua tidak boleh menerapkan perubahan saldo ulang.
- Transfer gagal tidak boleh meninggalkan transaksi atau ledger entry parsial.

### Validation

- Full PHPUnit regression lulus.
- MySQL integration gate lulus.
- Laravel Pint lulus.
- Frontend production build lulus.
- `composer quality:full` lulus.
- `composer release:smoke` lulus dengan 0 failure dan 1 warning mail transport.
- `composer release:final` lulus.
- UAT desktop, tablet, dan mobile v1.0.1 lulus.

### Known limitations

- Aplikasi masih ditujukan untuk satu pengguna.
- OCR, lampiran transaksi, machine learning, dan PWA belum termasuk v1.0.1.
- Mail transport nyata perlu dikonfigurasi sebelum penggunaan multi-user.
- Penghapusan transaksi permanen hanya tersedia setelah transaksi dibatalkan.

## [1.0.0] - 2026-08-03

### Added

- Autentikasi pribadi dan provisioning akun awal.
- Onboarding preferensi dan rekening.
- Dashboard personal dan keuangan.
- Manajemen rekening, transaksi, kegiatan, prioritas, langganan, anggaran, rekomendasi, dan notifikasi.
- Analisis pengeluaran per kategori.
- Profil, preferensi, foto profil, keamanan sesi, ekspor data, dan penghapusan akun.
- Custom error pages, request ID, security headers, query monitoring, dan query-budget tests.
- Quality gate, dependency audit, release readiness command, dan production smoke test.
- Dokumentasi deployment, UAT, release checklist, dan release notes.

### Security

- Registrasi publik dinonaktifkan.
- Content Security Policy dan header keamanan response.
- Validasi upload dan normalisasi foto profil.
- Verifikasi password untuk ekspor dan penghapusan data.
- Hardening concurrency pada budget dan period synchronization.

### Known limitations

- Aplikasi ditujukan untuk satu pengguna.
- OCR, lampiran transaksi, machine learning, dan PWA belum termasuk MVP.
- Mail transport production harus dikonfigurasi secara terpisah.
