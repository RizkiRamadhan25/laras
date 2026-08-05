LARAS v1.0.1 — TAHAP 3A
Fondasi Form Modern, Floating Label, Validasi, dan Password Visibility

RUANG LINGKUP
- Menambahkan komponen Blade reusable:
  - x-ui.floating-input
  - x-ui.floating-textarea
  - x-ui.floating-select
  - x-ui.password-input
- Menambahkan state fokus, terisi, error, hint, readonly, disabled, dan reduced motion.
- Menambahkan tombol tampilkan/sembunyikan kata sandi.
- Mempertahankan posisi kursor setelah tipe password diubah.
- Menangani nilai lama, browser autofill, dan validasi Laravel.
- Memigrasikan form autentikasi, pengaturan keamanan, ekspor data, dan hapus akun.
- Belum memigrasikan seluruh form transaksi, aktivitas, rekening, anggaran, dan langganan.

FILE BARU
resources/css/ui/forms.css
resources/js/ui/form-controls.js
resources/views/components/ui/floating-input.blade.php
resources/views/components/ui/floating-select.blade.php
resources/views/components/ui/floating-textarea.blade.php
resources/views/components/ui/password-input.blade.php
tests/Feature/ModernFormFoundationTest.php
README-STAGE-3A.txt

FILE YANG DIPERBARUI
resources/js/app.js
resources/views/auth/login.blade.php
resources/views/auth/forgot-password.blade.php
resources/views/auth/reset-password.blade.php
resources/views/settings/partials/security.blade.php
resources/views/settings/partials/data-privacy.blade.php

CATATAN
Paket app.js dibangun dari checkpoint Tahap 2C dan hanya menambahkan:
import '../css/ui/forms.css';
import './ui/form-controls';

Jika app.js lokal memiliki penyesuaian lain setelah Tahap 2C, jangan langsung menimpanya. Tambahkan dua import tersebut secara manual.

PEMERIKSAAN UTAMA
php artisan optimize:clear
php artisan test --filter=ModernFormFoundationTest
php artisan test --filter=AccountSecurityTest
php artisan test --filter=DataPrivacyTest
php artisan test --filter=ProfileAvatarAndLoadingScreenTest
php artisan test --filter=GlobalFeedbackInterfaceTest
node --check resources/js/ui/form-controls.js
node --check resources/js/app.js
npm run build
php vendor/bin/pint --test
php artisan test

UJI MANUAL
1. Buka halaman login.
2. Fokus pada input email dan pastikan label berpindah ke atas.
3. Isi email lalu pindah fokus; label harus tetap di atas.
4. Tekan ikon mata pada password; teks password harus terlihat.
5. Tekan kembali; password harus disembunyikan.
6. Buka Settings > Keamanan.
7. Uji keempat input password.
8. Buka Settings > Data dan privasi.
9. Uji password ekspor dan hapus akun tanpa mengeksekusi penghapusan akun.
10. Uji tampilan pada desktop 1366x768 dan mobile 390x844.
