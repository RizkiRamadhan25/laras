LARAS v1.0.1 — TAHAP 2C
GLOBAL SEARCH PADA TOPBAR

Fitur:
- Tombol pencarian desktop dan mobile.
- Shortcut / serta Ctrl+K atau Cmd+K.
- Pencarian navigasi, aktivitas, transaksi, rekening, anggaran, dan langganan.
- Debounce 275 ms dan AbortController.
- Navigasi keyboard Arrow Up/Down, Enter, Escape.
- Hanya data milik user aktif yang dapat ditemukan.
- Maksimal lima hasil per kelompok.
- Tidak ada migration baru.

File baru:
- app/Http/Controllers/GlobalSearchController.php
- app/Http/Requests/GlobalSearchRequest.php
- app/Services/GlobalSearchService.php
- resources/js/features/global-search.js
- resources/views/components/ui/global-search.blade.php
- tests/Feature/GlobalSearchTest.php

File diperbarui:
- resources/views/partials/app-topbar.blade.php
- resources/js/app.js
- routes/web.php
