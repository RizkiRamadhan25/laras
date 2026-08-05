LARAS v1.0.1 — TAHAP 2B
Sinkronisasi avatar, cache foto profil, dan loading screen awal

PERUBAHAN UTAMA
1. Seluruh avatar menggunakan komponen Blade yang sama:
   resources/views/components/ui/user-avatar.blade.php
2. Avatar topbar, sidebar, dan halaman pengaturan membaca URL foto yang sama.
3. URL foto profil memiliki parameter versi berbasis path agar cache browser tidak menahan foto lama.
4. Loading screen Laras tampil sekali per tab/sesi browser menggunakan sessionStorage.
5. Loading screen memiliki batas minimum 650 ms dan batas maksimum 2200 ms.
6. Animasi menghormati prefers-reduced-motion.
7. Tidak ada migration atau perubahan struktur database.

FILE BARU
- resources/views/components/ui/user-avatar.blade.php
- resources/views/components/ui/loading-screen.blade.php
- resources/js/ui/loading-screen.js
- tests/Feature/ProfileAvatarAndLoadingScreenTest.php

FILE DIUBAH
- app/Models/User.php
- resources/views/layouts/app.blade.php
- resources/views/layouts/auth.blade.php
- resources/views/partials/app-topbar.blade.php
- resources/views/partials/app-sidebar.blade.php
- resources/views/settings/index.blade.php
- resources/js/app.js
- resources/css/app.css

PENGUJIAN TERFOKUS
php artisan optimize:clear
php artisan test --filter=ProfileAvatarAndLoadingScreenTest
php artisan test --filter=ProfilePhotoTest
npm run build

PENGUJIAN MANUAL
1. Hapus key sessionStorage untuk menampilkan ulang loading screen:
   sessionStorage.removeItem('laras:intro-shown:v1'); location.reload();
2. Upload foto profil pada Pengaturan.
3. Pastikan foto yang sama muncul di topbar, sidebar, dan kartu profil.
4. Refresh biasa dan hard refresh; foto baru harus tetap tampil.
5. Pindah halaman; loading screen tidak boleh muncul lagi pada tab yang sama.
6. Buka tab baru; loading screen tampil satu kali pada tab baru tersebut.
