FASE 5D — FINAL MVP RELEASE REVIEW

Isi paket:
- README.md
- CHANGELOG.md
- docs/DEPLOYMENT.md
- docs/UAT-MVP.md
- docs/RELEASE-CHECKLIST.md
- docs/RELEASE-NOTES-v1.0.0.md
- scripts/final-release-review.ps1
- composer.json dengan script release:final

Paket tidak mengubah controller, model, service, migration, middleware,
view aplikasi, konfigurasi observability, atau script Fase 5C yang sudah ada.

Urutan:
1. Ekstrak ke root proyek.
2. composer validate --strict
3. composer dump-autoload
4. php vendor/bin/pint --dirty
5. commit dokumentasi Fase 5D.
6. Pastikan working tree bersih dan public/hot tidak ada.
7. composer release:final
8. Jalankan UAT manual dari docs/UAT-MVP.md.
9. Merge ke main hanya setelah acceptance criteria lulus.
