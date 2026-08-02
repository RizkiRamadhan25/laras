FASE 5C-4 - QUALITY GATE DAN PRODUCTION SMOKE

File utama:
- app/Console/Commands/ReleaseReadinessCommand.php
- tests/Feature/ReleaseReadinessCommandTest.php
- scripts/quality-gate.ps1
- scripts/production-smoke.ps1
- pint.json
- .env.production.example
- composer.json
- package.json

Perintah utama:
- composer format:dirty
- composer quality:dirty
- composer quality:full
- composer release:check
- composer release:smoke

Catatan:
- production-smoke.ps1 tidak mengubah file .env.
- environment production hanya berlaku di proses PowerShell tersebut.
- cache production dibersihkan kembali pada blok finally.
