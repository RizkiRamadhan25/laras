LARAS v1.0.1 — TAHAP 3A.1
LOKALISASI VALIDASI DAN KETENTUAN KATA SANDI DINAMIS

TUJUAN
- Menghapus titik dekoratif pada pesan error form.
- Mengubah pesan validasi menjadi Bahasa Indonesia.
- Memberikan error login yang tepat pada field terkait.
- Menyamakan aturan kata sandi frontend dan backend.
- Menampilkan checklist ketentuan kata sandi secara realtime.

CATATAN KEAMANAN LOGIN
Pesan login yang membedakan email tidak terdaftar dan kata sandi salah
dapat mempermudah enumerasi akun jika aplikasi dibuka untuk publik.

Untuk development/private beta:
LARAS_DETAILED_AUTH_ERRORS=true

Untuk production publik:
LARAS_DETAILED_AUTH_ERRORS=false

KETENTUAN KATA SANDI
- Minimal 8 karakter
- Huruf besar
- Huruf kecil
- Angka
- Simbol

SETELAH EKSTRAK
1. Tambahkan ke .env lokal:
   APP_LOCALE=id
   APP_FALLBACK_LOCALE=id
   APP_FAKER_LOCALE=id_ID
   LARAS_DETAILED_AUTH_ERRORS=true

2. Jalankan:
   php artisan optimize:clear
   php artisan test --filter=LocalizedValidationAndPasswordPolicyTest
   php artisan test --filter=ModernFormFoundationTest
   php artisan test --filter=AuthenticationTest
   php artisan test --filter=AccountSecurityTest
   node --check resources/js/ui/form-controls.js
   php vendor/bin/pint --test
   npm run build

3. Uji manual halaman login, reset kata sandi, dan settings#security.

TIDAK ADA MIGRATION BARU.
