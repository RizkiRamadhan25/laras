LARAS v1.0.1 — TAHAP 3B
Migrasi form profil, preferensi, aktivitas, dan filter

Perubahan utama:
1. Form profil memakai floating input yang konsisten.
2. Preferensi memakai floating select dan pesan bantuan kontekstual.
3. Form tambah/edit aktivitas dimigrasikan ke komponen modern.
4. Jenis aktivitas serta pilihan sepanjang hari/fleksibel memakai choice card.
5. Filter aktivitas memakai field modern dengan density compact.
6. Floating input mendukung suffix, misalnya satuan menit.
7. State old value, validation error, disabled, dan autofocus tetap dipertahankan.
8. Tidak ada perubahan migration, route, controller, atau service.

File yang diperbarui:
- resources/css/ui/forms.css
- resources/views/components/ui/floating-input.blade.php
- resources/views/components/ui/floating-select.blade.php
- resources/views/components/ui/floating-textarea.blade.php
- resources/views/settings/index.blade.php
- resources/views/activities/_form.blade.php
- resources/views/activities/index.blade.php

File baru:
- tests/Feature/ModernFormMigrationTest.php
- README-STAGE-3B.txt

Pemeriksaan terfokus:
php artisan optimize:clear
php artisan test --filter=ModernFormMigrationTest
php artisan test --filter=UserSettingsTest
php artisan test --filter=SettingsInterfaceTest
php artisan test --filter=ActivityManagementTest
php artisan test --filter=ModernFormFoundationTest
php artisan test --filter=LocalizedValidationAndPasswordPolicyTest
php vendor/bin/pint --test
npm run build
