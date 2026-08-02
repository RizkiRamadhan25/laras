# Laras v1.0.0 — MVP Release

**Tanggal target:** 3 Agustus 2026  
**Tagline:** Selaraskan hari, tentukan langkah.

## Ringkasan

Laras v1.0.0 adalah rilis MVP pertama untuk penggunaan personal. Rilis ini menyatukan manajemen kegiatan, rekening, transaksi, anggaran, langganan, analisis, notifikasi, dan rekomendasi dalam satu aplikasi responsif.

## Fitur utama

- Autentikasi pribadi dan onboarding.
- Dashboard personal dan keuangan.
- Rekening dan saldo awal.
- Pemasukan, pengeluaran, transfer, dan pembatalan transaksi.
- Analisis pengeluaran per kategori.
- Kegiatan dan priority engine.
- Langganan dan billing automation.
- Anggaran, usage tracking, dan alert.
- Rekomendasi personal dengan alasan dan feedback.
- Notification center.
- Profil, preferensi, foto, keamanan sesi, data export, dan account deletion.

## Hardening rilis

- Custom error pages dan request tracing.
- Content Security Policy dan security headers.
- Query monitoring dan query-budget tests.
- Concurrency guard pada anggaran.
- Dependency security audit.
- Release readiness command.
- Full quality gate dan production smoke test.

## Batasan yang diketahui

- Ditujukan untuk satu pengguna.
- Registrasi publik tidak tersedia.
- OCR dan lampiran transaksi belum tersedia.
- Rekomendasi masih berbasis aturan, bukan machine learning.
- PWA/offline belum tersedia.
- Mail transport production harus dikonfigurasi oleh pemilik deployment.

## Acceptance gate

Rilis hanya boleh diberi tag `v1.0.0` setelah:

```powershell
composer quality:full
composer release:smoke
composer release:final
```

lulus dan seluruh UAT prioritas CRITICAL/HIGH berstatus PASS.
