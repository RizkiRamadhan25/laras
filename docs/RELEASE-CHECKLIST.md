# Release Checklist — Laras MVP v1.0.0

Checklist ini harus selesai sebelum branch `feature/mvp-hardening` digabungkan ke `main`.

## A. Source dan Git

- [ ] Branch aktif adalah `feature/mvp-hardening`.
- [ ] Seluruh perubahan Fase 5D sudah di-commit.
- [ ] `git status` bersih.
- [ ] `git diff --check` tidak menghasilkan error.
- [ ] `.env`, `public/hot`, database lokal, log, dan data export tidak terlacak Git.
- [ ] Remote repository dan branch `main` dapat diakses.

## B. Automated quality

- [ ] `php artisan test` lulus.
- [ ] `php vendor\bin\pint --test` lulus.
- [ ] `npm run build` lulus.
- [ ] `composer validate --strict` lulus.
- [ ] `composer check-platform-reqs` lulus.
- [ ] `composer audit --locked` tidak memiliki advisory aktif.
- [ ] `npm audit --audit-level=high` lulus.
- [ ] `npm audit --omit=dev --audit-level=high` lulus.
- [ ] `composer quality:full` lulus.
- [ ] `composer release:smoke` lulus.
- [ ] `composer release:final` lulus.

## C. Database dan data integrity

- [ ] Seluruh migration berstatus `Ran`.
- [ ] Tidak ada migration tertunda.
- [ ] Saldo sesuai ledger pada data UAT.
- [ ] Pembatalan transaksi mengembalikan saldo secara konsisten.
- [ ] Transfer tidak mengubah total kekayaan kecuali biaya.
- [ ] Budget usage hanya menghitung entry posted yang relevan.
- [ ] Subscription billing tidak membuat double charge.
- [ ] Backup database dibuat sebelum deployment.
- [ ] Prosedur restore pernah diuji pada environment terpisah.

## D. Security dan privacy

- [ ] Registrasi publik tidak tersedia.
- [ ] `APP_DEBUG=false` pada production.
- [ ] HTTPS dan secure session cookie aktif.
- [ ] Security headers tersedia.
- [ ] Custom error page tidak membocorkan stack trace.
- [ ] Request ID tampil pada error page.
- [ ] Query metric headers tidak tampil di production.
- [ ] Export data memerlukan password.
- [ ] Penghapusan akun diuji hanya pada database UAT.
- [ ] Foto invalid ditolak dan file lama dibersihkan.

## E. Operasional production

- [ ] Document root menuju folder `public`.
- [ ] `storage` dan `bootstrap/cache` writable.
- [ ] `public/storage` link tersedia.
- [ ] Queue worker dikelola process monitor.
- [ ] Cron scheduler berjalan setiap menit.
- [ ] Mail transport production dikonfigurasi atau keterbatasannya diterima secara eksplisit.
- [ ] Log rotation dan monitoring tersedia.
- [ ] Backup database dan storage terjadwal.
- [ ] `php artisan laras:release-check --production --require-cache` lulus.

## F. UAT

- [ ] Semua test case CRITICAL lulus.
- [ ] Semua test case HIGH lulus.
- [ ] Tidak ada blocker terbuka.
- [ ] Tidak ada defect high terbuka.
- [ ] Responsiveness desktop, tablet, dan mobile diterima.
- [ ] Pengguna menyetujui hasil UAT.

## G. Dokumentasi

- [ ] README sesuai fitur aktual.
- [ ] Deployment guide ditinjau.
- [ ] UAT result diisi.
- [ ] Changelog diperbarui.
- [ ] Release notes diperbarui.
- [ ] Known limitations dipahami.

## H. Merge dan tag

- [ ] Branch `main` sudah diperbarui dari remote.
- [ ] Merge dilakukan dengan `--no-ff`.
- [ ] Automated test diulang pada `main` setelah merge.
- [ ] Tag `v1.0.0` dibuat setelah test pada `main` lulus.
- [ ] `main` dan tag didorong ke remote.
- [ ] Feature branch dihapus setelah verifikasi remote.
