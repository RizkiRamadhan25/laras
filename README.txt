Laras — Fase 4 Langkah 8B-2A

Salin folder app dan tests ke root project D:\Projects\laras.
File yang namanya sama akan menggantikan versi lama.

Sesudah menyalin:
1. php artisan optimize:clear
2. php -l app\Services\BudgetUsageSyncService.php
3. php -l app\Services\TransactionPostingService.php
4. php -l app\Services\BudgetService.php
5. php -l app\Services\BudgetManagementService.php
6. php -l tests\Feature\BudgetUsageSyncTest.php
7. php artisan test --filter=BudgetUsageSyncTest
8. php artisan test --filter=BudgetFoundationTest
9. php artisan test --filter=BudgetManagementTest
10. php artisan test --filter=TransactionManagementTest
