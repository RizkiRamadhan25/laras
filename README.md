# Laras

**Selaraskan hari, tentukan langkah.**

Laras adalah web app personal untuk membantu satu pengguna mengelola kegiatan, prioritas, rekening, transaksi, anggaran, langganan, notifikasi, serta rekomendasi yang dapat dijelaskan. Aplikasi dirancang mobile-first, responsif, dan tidak menyediakan registrasi publik.

## Status proyek

**Laras v1.0.1 — Release Stable**

Versi 1.0.1 menyempurnakan interaksi kegiatan dan rekening tanpa reload penuh, memperjelas transfer internal dan eksternal, memperkuat konsistensi ledger, serta menambahkan pembatalan dan penghapusan transfer secara atomik.

Seluruh regression test, MySQL integration gate, quality gate, production smoke test, final release review, dan User Acceptance Test (UAT) v1.0.1 telah diselesaikan.

## Fitur utama

- Login pribadi, remember me, reset password, dan logout.
- Provisioning akun awal tanpa registrasi publik.
- Onboarding preferensi, zona waktu, mata uang, dan rekening awal.
- Dashboard keuangan, kegiatan, rekomendasi, dan peringatan.
- Manajemen rekening serta saldo awal.
- Pengurutan rekening secara asinkron dengan rollback, global lock, busy state, pemulihan fokus keyboard, dan dukungan reduced motion.
- Transaksi pemasukan dan pengeluaran.
- Transfer internal antar-rekening Laras.
- Transfer eksternal ke pihak di luar Laras tanpa menambah saldo rekening Laras lain.
- Biaya admin sebagai ledger entry terpisah.
- Riwayat dan detail transfer yang membedakan sumber, tujuan, penerima, institusi, serta identitas rekening.
- Pembatalan transaksi dengan pencatatan ledger yang konsisten.
- Penghapusan permanen transaksi yang telah dibatalkan, dengan verifikasi password dan konfirmasi kuat.
- Penghapusan transfer secara atomik sebagai satu kelompok ledger.
- Analisis pengeluaran per kategori dan periode.
- Manajemen kegiatan dan prioritas.
- Aksi kegiatan secara asinkron dengan refresh ringkasan dan daftar, rollback, serta dukungan aksesibilitas.
- Arsip dan pemulihan kegiatan tanpa mengubah status penyelesaian.
- Manajemen langganan, billing, reminder, retry, pause, resume, dan cancel.
- Anggaran per kategori, periode, penggunaan, peringatan, dan histori.
- Rekomendasi personal berbasis aturan, feedback, dan riwayat interaksi.
- Notification center.
- Profil, preferensi, foto profil, keamanan akun, sesi perangkat, ekspor data, dan penghapusan akun.
- Custom error page, request ID, security headers, slow-query monitoring, dan query-budget tests.
- Quality gate, dependency audit, release readiness check, production smoke test, dan final release review.

## Di luar ruang lingkup v1.0.1

Fitur berikut disiapkan untuk fase setelah v1.0.1:

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

MySQL integration test:

```powershell
php vendor/bin/phpunit -c phpunit.mysql.xml
```

Gunakan database khusus testing dan jangan arahkan konfigurasi tersebut ke database development atau production.

## Dokumentasi rilis

- [Panduan deployment](docs/DEPLOYMENT.md)
- [Release notes v1.0.1](docs/RELEASE-NOTES-v1.0.1.md)
- [UAT v1.0.1](docs/UAT-v1.0.1.md)
- [Release checklist v1.0.1](docs/RELEASE-CHECKLIST-v1.0.1.md)
- [Release notes v1.0.0](docs/RELEASE-NOTES-v1.0.0.md)
- [User Acceptance Test MVP](docs/UAT-MVP.md)
- [Release checklist MVP](docs/RELEASE-CHECKLIST.md)
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
- Penghapusan transaksi permanen memerlukan password, teks konfirmasi, dan persetujuan eksplisit.
- Upload foto dinormalisasi dan disimpan sebagai WebP.
- Response menggunakan Content Security Policy dan security headers lainnya.
- Setiap request memiliki request ID untuk penelusuran error.
- Query metric headers tidak diekspos pada production.
- Transfer dan ledger diproses dalam database transaction untuk menjaga konsistensi saldo.

Jangan commit `.env`, database lokal, file log, `public/hot`, atau data export pengguna.

## Catatan email

Konfigurasi `MAIL_MAILER=log` masih dapat digunakan untuk instalasi personal atau development. Sebelum Laras digunakan oleh pengguna lain, gunakan mail transport nyata agar reset password dan notifikasi email dapat dikirim.

## Lisensi dan penggunaan

Laras dikembangkan sebagai aplikasi personal. Tentukan lisensi repository sebelum mendistribusikan source code kepada pihak lain.
