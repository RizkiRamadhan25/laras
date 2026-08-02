# Laras

**Selaraskan hari, tentukan langkah.**

Laras adalah web app personal untuk membantu satu pengguna mengelola kegiatan, prioritas, rekening, transaksi, anggaran, langganan, notifikasi, serta rekomendasi yang dapat dijelaskan. Aplikasi dirancang mobile-first, responsif, dan tidak menyediakan registrasi publik.

## Status proyek

**MVP Release Candidate — Fase 5D**

Fitur inti, hardening keamanan, observability, quality gate, dan production smoke test telah tersedia. Rilis MVP dinyatakan selesai setelah seluruh User Acceptance Test (UAT) dan release checklist berstatus lulus.

## Fitur MVP

- Login pribadi, remember me, reset password, dan logout.
- Provisioning akun awal tanpa registrasi publik.
- Onboarding preferensi, zona waktu, mata uang, dan rekening awal.
- Dashboard keuangan, kegiatan, rekomendasi, dan peringatan.
- Manajemen rekening serta saldo awal.
- Transaksi pemasukan, pengeluaran, dan transfer antar-rekening.
- Pembatalan transaksi dengan pencatatan ledger yang konsisten.
- Analisis pengeluaran per kategori dan periode.
- Manajemen kegiatan dan prioritas.
- Manajemen langganan, billing, reminder, retry, pause, resume, dan cancel.
- Anggaran per kategori, periode, penggunaan, peringatan, dan histori.
- Rekomendasi personal berbasis aturan, feedback, dan riwayat interaksi.
- Notification center.
- Profil, preferensi, foto profil, keamanan akun, sesi perangkat, ekspor data, dan penghapusan akun.
- Custom error page, request ID, security headers, slow-query monitoring, dan query-budget tests.
- Quality gate, dependency audit, release readiness check, dan production smoke test.

## Di luar ruang lingkup MVP

Fitur berikut disiapkan untuk fase setelah MVP:

- OCR dan pemindaian bukti transaksi.
- Lampiran dokumen transaksi.
- Machine learning dan rekomendasi adaptif berbasis model.
- PWA penuh dan sinkronisasi offline.
- Multi-user, role, dan registrasi publik.
- Backup cloud otomatis.

## Teknologi

- PHP 8.3
- Laravel 13
- Laravel Fortify
- MySQL
- Blade dan Alpine.js
- Tailwind CSS 4
- Chart.js
- Lucide Icons
- Vite 8
- PHPUnit 12
- Laravel Pint

## Persyaratan lokal

- PHP 8.3 dengan ekstensi `bcmath`, `exif`, `gd`, dan `zip`.
- Composer 2.10 atau lebih baru.
- Node.js dan npm yang kompatibel dengan Vite 8.
- MySQL 8 atau database yang kompatibel.
- Windows PowerShell untuk script quality gate dan smoke test yang tersedia di repository ini.

## Instalasi lokal

### Instalasi terpandu

```powershell
composer setup
```

Script tersebut akan:

1. Memasang dependency Composer.
2. Membuat `.env` dari `.env.example` bila belum tersedia.
3. Membuat application key.
4. Menjalankan migration.
5. Membuat public storage link.
6. Menjalankan provisioning pengguna Laras.
7. Memasang dependency npm.
8. Membuat frontend production build.

### Instalasi manual

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan laras:provision-user
npm run build
```

Isi koneksi MySQL di `.env` sebelum menjalankan migration.

## Menjalankan aplikasi

```powershell
composer dev
```

Perintah tersebut menjalankan:

- Laravel development server.
- Queue listener.
- Scheduler worker.
- Vite development server.

Aplikasi biasanya tersedia di:

```text
http://127.0.0.1:8000
```

## Pengujian dan quality gate

```powershell
php artisan test
php vendor\bin\pint --test
npm run build
composer validate --strict
```

Quality gate lengkap:

```powershell
composer quality:full
```

Production smoke test:

```powershell
composer release:smoke
```

Final release review:

```powershell
composer release:final
```

## Dokumentasi rilis

- [Panduan deployment](docs/DEPLOYMENT.md)
- [User Acceptance Test MVP](docs/UAT-MVP.md)
- [Release checklist](docs/RELEASE-CHECKLIST.md)
- [Release notes v1.0.0](docs/RELEASE-NOTES-v1.0.0.md)
- [Changelog](CHANGELOG.md)

## Struktur fitur utama

```text
app/
├── Console/Commands
├── Enums
├── Http
│   ├── Controllers
│   ├── Middleware
│   └── Requests
├── Models
├── Notifications
├── Providers
├── Services
└── Support

resources/
├── css
├── js
└── views

docs/
scripts/
tests/
```

## Keamanan dan privasi

- Registrasi publik dinonaktifkan.
- Password di-hash oleh Laravel.
- Form dilindungi CSRF.
- Route sensitif dilindungi autentikasi dan onboarding middleware.
- Session production menggunakan cookie aman dan data terenkripsi.
- Data export dan penghapusan akun memerlukan password saat ini.
- Upload foto dinormalisasi dan disimpan sebagai WebP.
- Response menggunakan Content Security Policy dan security headers lainnya.
- Setiap request memiliki request ID untuk penelusuran error.
- Query metric headers tidak diekspos pada production.

Jangan commit `.env`, database lokal, file log, `public/hot`, atau data export pengguna.

## Lisensi dan penggunaan

Laras dikembangkan sebagai aplikasi personal. Tentukan lisensi repository sebelum mendistribusikan source code kepada pihak lain.
