# Changelog

Semua perubahan penting Laras didokumentasikan pada file ini.

Format mengikuti prinsip Keep a Changelog dan versi menggunakan Semantic Versioning.

## [Unreleased]

### Planned

- OCR dan pemindaian bukti transaksi.
- Lampiran dokumen transaksi.
- Rekomendasi adaptif berbasis data penggunaan.
- PWA dan dukungan offline.

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
