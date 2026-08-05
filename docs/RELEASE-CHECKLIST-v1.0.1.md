# Release Checklist — Laras v1.0.1

**Versi:** 1.0.1  
**Tanggal:** 5 Agustus 2026  
**Status:** SIAP DIRILIS

## A. Repository

- [x] Working tree bersih sebelum release.
- [x] Seluruh perubahan v1.0.1 telah di-commit.
- [x] Tidak ada `.env`, database lokal, log, atau data export yang ikut di-commit.
- [x] `git diff --check` tidak menemukan whitespace error.
- [x] README dan CHANGELOG telah diperbarui.

## B. Backend

- [x] Seluruh migration telah dijalankan.
- [x] Tidak ada migration tertunda.
- [x] Full PHPUnit regression lulus.
- [x] MySQL integration gate lulus.
- [x] Ownership dan authorization test lulus.
- [x] Transfer internal dan eksternal lulus.
- [x] Pembatalan transaksi lulus.
- [x] Atomic deletion lulus.
- [x] Saldo dan ledger tetap konsisten.
- [x] Double cancellation tidak mengubah saldo ulang.

## C. Frontend

- [x] `npm run build` lulus.
- [x] Vite manifest tersedia.
- [x] `public/hot` tidak tersedia pada release mode.
- [x] Tidak ada unresolved import.
- [x] Ikon transfer tampil dengan benar.
- [x] Tidak ada gambar Laras yang menghasilkan response `403`.
- [x] Aksi kegiatan asinkron lulus.
- [x] Pengurutan rekening asinkron lulus.
- [x] Reduced motion lulus.
- [x] Keyboard focus restoration lulus.

## D. Code quality

- [x] Laravel Pint lulus.
- [x] Composer validation lulus.
- [x] Dependency audit lulus.
- [x] `composer quality:full` lulus.
- [x] JavaScript syntax checks lulus.
- [x] Tidak ada test yang di-skip untuk menutupi failure.

## E. Production readiness

- [x] `APP_ENV=production`.
- [x] `APP_DEBUG=false`.
- [x] `APP_URL` menggunakan HTTPS.
- [x] APP_KEY tersedia.
- [x] Secure session cookie aktif.
- [x] HttpOnly aktif.
- [x] SameSite sesuai.
- [x] Session encryption aktif.
- [x] Persistent session driver digunakan.
- [x] Persistent cache store digunakan.
- [x] Queue worker terpisah digunakan.
- [x] LOG_LEVEL sesuai production.
- [x] Writable directory valid.
- [x] Public storage link valid.
- [x] Configuration cache aktif.
- [x] Route cache aktif.
- [x] Health endpoint `/up` menghasilkan 200.
- [x] Login dan security headers production lulus.
- [x] Custom 404 page lulus.
- [x] `composer release:smoke` lulus.
- [x] `composer release:final` lulus.

## F. UAT

- [x] Desktop 1920 × 1080.
- [x] Desktop 1366 × 768.
- [x] Tablet portrait.
- [x] Tablet landscape.
- [x] Mobile 360 px.
- [x] Mobile 390 px.
- [x] Autentikasi dan onboarding.
- [x] Kegiatan.
- [x] Rekening.
- [x] Pemasukan dan pengeluaran.
- [x] Transfer internal.
- [x] Transfer eksternal.
- [x] Pembatalan transaksi.
- [x] Penghapusan permanen.
- [x] Responsive layout.
- [x] Accessibility basics.
- [x] Browser console bebas dari error Laras yang diketahui.

## G. Backup dan rollback

- [x] Backup database dilakukan sebelum deployment.
- [x] Source release sebelumnya tetap tersedia.
- [x] Prosedur rollback migration dipahami.
- [x] File upload dan storage ikut dipertimbangkan dalam backup.
- [x] Cache dapat dibersihkan ulang setelah rollback.

## H. Dokumentasi

- [x] `README.md` diperbarui.
- [x] `CHANGELOG.md` diperbarui.
- [x] `docs/RELEASE-NOTES-v1.0.1.md` tersedia.
- [x] `docs/UAT-v1.0.1.md` tersedia.
- [x] `docs/RELEASE-CHECKLIST-v1.0.1.md` tersedia.

## Warning yang diterima

- [x] `MAIL_MAILER=log` diterima untuk penggunaan personal.
- [ ] Mail transport nyata dikonfigurasi sebelum penggunaan multi-user.

## Perintah verifikasi final

```powershell
php artisan optimize:clear
php artisan test
php vendor/bin/phpunit -c phpunit.mysql.xml
php vendor/bin/pint --test
npm run build
composer quality:full
composer release:smoke
composer release:final
git status
```

## Persetujuan rilis

- [x] Tidak ada failure yang tersisa.
- [x] Warning yang tersisa telah diketahui dan diterima.
- [x] Release v1.0.1 disetujui.

**Keputusan:** Laras v1.0.1 siap dirilis.
