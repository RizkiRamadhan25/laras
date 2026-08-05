LARAS v1.0.1 — STAGE 1A
Critical account deletion dependency fix

Files:
- app/Services/AccountDeletionService.php
- tests/Feature/AccountDeletionDependencyTest.php

Apply by extracting this ZIP into D:\Projects\laras and choosing Replace for AccountDeletionService.php.

Then run:

php -l app\Services\AccountDeletionService.php
php -l tests\Feature\AccountDeletionDependencyTest.php
php artisan optimize:clear
php artisan test --filter=AccountDeletionDependencyTest
php artisan test --filter=DataPrivacyTest
php artisan test
php vendor\bin\pint --test
npm run build

For the MySQL-specific verification, use a dedicated test database. Never run RefreshDatabase against db_laras.
Suggested database name: db_laras_test.
