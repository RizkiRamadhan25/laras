LARAS — FASE 5B-1 RELEASE BLOCKER PATCH

Perubahan:
1. Provisioning akun awal interaktif/non-interaktif yang idempotent.
2. Seeder akun tidak lagi mengubah nama atau kata sandi akun yang sudah ada.
3. composer setup menjalankan storage:link dan provisioning akun awal.
4. composer dev menjalankan scheduler.
5. ext-bcmath menjadi requirement Composer.
6. Penghapusan akun membersihkan sessions, notifications, password reset tokens,
   data cascade, dan foto profil.
7. Status periode anggaran memakai timezone pengguna.
8. Automated test untuk seluruh perubahan di atas.

Setelah ekstrak ke root proyek:
- Jalankan composer update --lock --no-install
- Jalankan composer dump-autoload
- Jalankan php artisan optimize:clear
- Jalankan test sesuai instruksi percakapan.

Catatan:
- Jangan mengisi LARAS_USER_PASSWORD dengan password produksi di repository.
- Untuk setup interaktif, biarkan LARAS_USER_EMAIL dan LARAS_USER_PASSWORD kosong,
  lalu command akan meminta data melalui terminal.
- Untuk setup non-interaktif, isi LARAS_USER_NAME, LARAS_USER_EMAIL,
  dan LARAS_USER_PASSWORD melalui environment deployment.
