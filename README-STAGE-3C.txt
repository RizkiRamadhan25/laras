# Laras v1.0.1 — Tahap 3C

Tahap ini memigrasikan form keuangan utama ke fondasi form modern Laras.

## Cakupan

- Form tambah/edit rekening.
- Form tambah transaksi.
- Form tambah/edit anggaran.
- Form tambah/edit langganan.
- Prefix nominal (IDR/Rp) dan suffix persentase.
- Floating label untuk tanggal, waktu, dan datetime.
- Helper text dan error per field.
- Choice card transaksi dan pengingat langganan.
- Automated test `ModernFinanceFormMigrationTest`.

## Tidak termasuk

- Transfer eksternal ke bank/pihak lain.
- Filter transaksi/langganan tanpa reload.
- Pengurutan rekening tanpa reload.
- Aksi status asinkron.
- Penghapusan permanen data keuangan.

Fitur tersebut dikerjakan pada tahap khusus agar logika ledger dan concurrency tidak tercampur dengan migrasi UI form.
