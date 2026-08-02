LARAS — FASE 5C-3
Eloquent Strict Mode, Query Monitoring, Query Budgets, dan Indeks Terukur

File baru:
- app/Providers/ObservabilityServiceProvider.php
- app/Support/QueryMetrics.php
- app/Http/Middleware/AddQueryMetricsHeaders.php
- config/observability.php
- database/migrations/2026_08_02_211500_add_measured_query_indexes.php
- tests/Feature/ObservabilityTest.php
- tests/Feature/MainPageQueryBudgetTest.php

File yang diperbarui:
- bootstrap/providers.php

Catatan:
- Paket tidak menimpa bootstrap/app.php, sehingga middleware request ID,
  error pages, security headers, dan perbaikan CSP tetap dipertahankan.
- Strict mode aktif default hanya pada APP_ENV=local.
- Query metrics aktif default pada local dan testing.
- Header diagnostik tidak aktif secara default pada production.
- SQL log memakai placeholder query tanpa binding agar nilai pengguna tidak
  ikut masuk ke log observability.

Variabel opsional .env:
LARAS_ELOQUENT_STRICT=true
LARAS_QUERY_MONITORING=true
LARAS_QUERY_RESPONSE_HEADERS=true
LARAS_SLOW_QUERY_THRESHOLD_MS=250
LARAS_CUMULATIVE_QUERY_THRESHOLD_MS=500
LARAS_QUERY_SQL_PREVIEW_LENGTH=1000
