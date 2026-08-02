FASE 5C-2 — ERROR HANDLING DAN REQUEST TRACING

File baru:
- app/Http/Middleware/AssignRequestId.php
- resources/views/components/errors/layout.blade.php
- resources/views/errors/403.blade.php
- resources/views/errors/404.blade.php
- resources/views/errors/419.blade.php
- resources/views/errors/429.blade.php
- resources/views/errors/500.blade.php
- resources/views/errors/503.blade.php
- tests/Feature/RequestIdTest.php
- tests/Feature/ErrorPageTest.php

File diperbarui:
- bootstrap/app.php

Tujuan:
- Memberikan X-Request-ID pada response web.
- Menambahkan request_id ke context log.
- Menampilkan kode referensi pada halaman error.
- Menghindari pelaporan exception yang sama dua kali.
- Menyediakan halaman error Laras tanpa bergantung pada Vite.
