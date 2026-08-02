# User Acceptance Test — Laras MVP

## 1. Tujuan

UAT memastikan Laras dapat digunakan sebagai aplikasi personal sehari-hari, bukan hanya lulus automated test. UAT dilakukan setelah:

```powershell
composer quality:full
composer release:smoke
```

keduanya lulus.

## 2. Aturan pelaksanaan

- Gunakan database UAT atau backup database lokal terlebih dahulu.
- Jalankan aplikasi dengan `composer dev` untuk pengujian lokal.
- Uji desktop, tablet, dan mobile viewport.
- Catat hasil dengan status `PASS`, `FAIL`, atau `BLOCKED`.
- Catat request ID dari halaman error bila menemukan status 500.
- Jangan menghapus data pribadi production untuk keperluan UAT.

## 3. Data uji yang disarankan

Siapkan:

- Satu pengguna yang telah menyelesaikan onboarding.
- Minimal tiga rekening aktif, misalnya bank, e-wallet, dan cash.
- Minimal satu kategori pemasukan dan tiga kategori pengeluaran.
- Minimal lima transaksi dalam bulan berjalan.
- Minimal tiga kegiatan dengan status dan deadline berbeda.
- Minimal dua langganan, termasuk satu billing gagal atau tertunda.
- Minimal dua anggaran aktif.

## 4. Kriteria penerimaan

Rilis MVP diterima bila:

- Semua test case `CRITICAL` dan `HIGH` berstatus `PASS`.
- Tidak ada defect blocker atau kehilangan data.
- Saldo dan ledger tetap konsisten setelah transaksi dan pembatalan.
- Akses data pengguna lain tidak memungkinkan.
- Quality gate penuh dan production smoke test lulus.
- Backup dan prosedur deployment telah ditinjau.

---

## A. Autentikasi dan onboarding

### UAT-A01 — Login valid

- **Prioritas:** CRITICAL
- **Langkah:** Buka `/login`, isi email dan password valid, lalu masuk.
- **Harapan:** Login berhasil dan diarahkan ke onboarding atau dashboard sesuai status pengguna.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-A02 — Login tidak valid

- **Prioritas:** HIGH
- **Langkah:** Masukkan password salah.
- **Harapan:** Login ditolak, pesan tidak mengungkap apakah email terdaftar, dan password tidak dipertahankan.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-A03 — Remember me dan logout

- **Prioritas:** HIGH
- **Langkah:** Login dengan remember me, tutup dan buka browser, lalu logout.
- **Harapan:** Sesi dipertahankan sesuai konfigurasi; logout menghapus sesi dan kembali ke login.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-A04 — Onboarding awal

- **Prioritas:** CRITICAL
- **Langkah:** Gunakan pengguna baru, isi preferensi, zona waktu, mata uang, rekening, dan saldo awal.
- **Harapan:** Onboarding selesai sekali, rekening dibuat, dan saldo awal tidak dihitung sebagai pemasukan biasa.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## B. Dashboard

### UAT-B01 — Ringkasan dashboard

- **Prioritas:** HIGH
- **Langkah:** Buka dashboard setelah data transaksi dan kegiatan tersedia.
- **Harapan:** Ringkasan saldo, pemasukan, pengeluaran, kegiatan, transaksi terbaru, anggaran, dan rekomendasi tampil konsisten.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-B02 — Kondisi kosong

- **Prioritas:** MEDIUM
- **Langkah:** Buka dashboard pada akun UAT tanpa data operasional.
- **Harapan:** Empty state dapat dipahami dan tidak menghasilkan error.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## C. Rekening

### UAT-C01 — Tambah dan edit rekening

- **Prioritas:** CRITICAL
- **Langkah:** Tambah rekening, isi saldo awal, lalu edit nama dan atribut yang diizinkan.
- **Harapan:** Rekening tersimpan dan saldo sesuai tanpa duplikasi ledger.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-C02 — Pindah, nonaktifkan, dan pulihkan rekening

- **Prioritas:** HIGH
- **Langkah:** Uji aksi move, delete/nonaktifkan, dan restore pada rekening yang memenuhi syarat.
- **Harapan:** Aksi mengikuti aturan bisnis dan tidak menghapus histori transaksi.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## D. Transaksi dan saldo

### UAT-D01 — Catat pemasukan

- **Prioritas:** CRITICAL
- **Langkah:** Tambah pemasukan ke satu rekening.
- **Harapan:** Transaksi posted, saldo bertambah tepat, dan dashboard ikut berubah.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-D02 — Catat pengeluaran

- **Prioritas:** CRITICAL
- **Langkah:** Tambah pengeluaran dengan kategori dan biaya tambahan bila tersedia.
- **Harapan:** Saldo berkurang sesuai total ledger dan analisis kategori diperbarui.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-D03 — Transfer antar-rekening

- **Prioritas:** CRITICAL
- **Langkah:** Transfer dana dari rekening A ke B.
- **Harapan:** Saldo A berkurang, saldo B bertambah, total kekayaan tidak berubah selain biaya transfer, dan transfer tidak dihitung sebagai pemasukan/pengeluaran eksternal.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-D04 — Batalkan transaksi

- **Prioritas:** CRITICAL
- **Langkah:** Batalkan transaksi posted yang dapat dibatalkan.
- **Harapan:** Saldo dikembalikan melalui mekanisme yang konsisten, status berubah, dan transaksi batal tidak ikut analisis aktif.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-D05 — Filter dan detail transaksi

- **Prioritas:** HIGH
- **Langkah:** Gunakan filter tanggal, jenis, rekening, kategori, dan buka detail.
- **Harapan:** Daftar sesuai filter dan detail menampilkan entry yang benar.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## E. Kegiatan dan prioritas

### UAT-E01 — Buat dan edit kegiatan

- **Prioritas:** HIGH
- **Langkah:** Buat kegiatan dengan deadline, prioritas, estimasi, lalu edit.
- **Harapan:** Data tersimpan dan urutan prioritas dapat dijelaskan.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-E02 — Siklus status kegiatan

- **Prioritas:** HIGH
- **Langkah:** Uji start, complete, cancel, reopen, delete, dan restore sesuai kondisi.
- **Harapan:** Hanya transisi valid yang diterima dan daftar diperbarui.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-E03 — Halaman prioritas

- **Prioritas:** HIGH
- **Langkah:** Buat beberapa kegiatan dengan urgensi berbeda lalu buka prioritas.
- **Harapan:** Urutan masuk akal, alasan prioritas tersedia, dan deadline dekat terlihat jelas.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## F. Langganan dan billing

### UAT-F01 — CRUD langganan

- **Prioritas:** HIGH
- **Langkah:** Tambah, lihat, edit, pause, resume, dan cancel langganan.
- **Harapan:** Status dan jadwal billing berubah konsisten.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-F02 — Proses billing dan retry

- **Prioritas:** CRITICAL
- **Langkah:** Uji billing sukses, billing gagal karena saldo, buka detail, lalu retry setelah saldo cukup.
- **Harapan:** Tidak terjadi double charge; status billing dan transaksi sesuai hasil pemrosesan.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-F03 — Reminder scheduler

- **Prioritas:** HIGH
- **Langkah:** Jalankan scheduler pada data langganan yang mendekati jatuh tempo.
- **Harapan:** Reminder dibuat sekali dan notification center menampilkan informasi relevan.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## G. Anggaran

### UAT-G01 — Buat dan edit anggaran

- **Prioritas:** HIGH
- **Langkah:** Buat anggaran kategori, periode, nominal, lalu edit.
- **Harapan:** Hanya satu anggaran aktif per kategori pengguna dan periode terbentuk benar sesuai zona waktu.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-G02 — Sinkronisasi penggunaan

- **Prioritas:** CRITICAL
- **Langkah:** Tambah pengeluaran posted pada kategori anggaran, lalu batalkan salah satunya.
- **Harapan:** Pemakaian hanya menghitung entry posted yang relevan dan kembali benar setelah pembatalan.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-G03 — Peringatan anggaran

- **Prioritas:** HIGH
- **Langkah:** Capai threshold warning dan exceeded.
- **Harapan:** Alert tidak terduplikasi secara tidak wajar, dashboard dan notifikasi menampilkan level tepat.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## H. Analisis, rekomendasi, dan notifikasi

### UAT-H01 — Analisis pengeluaran

- **Prioritas:** HIGH
- **Langkah:** Ganti periode mingguan, bulanan, dan tahunan.
- **Harapan:** Nilai per kategori sesuai transaksi posted milik pengguna dan transaksi cancelled tidak dihitung.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-H02 — Rekomendasi dan feedback

- **Prioritas:** HIGH
- **Langkah:** Buka rekomendasi, ikuti tautan tindakan, lalu beri feedback.
- **Harapan:** Alasan rekomendasi terlihat, feedback tersimpan, dan histori interaksi diperbarui.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-H03 — Notification center

- **Prioritas:** HIGH
- **Langkah:** Buka notifikasi, tandai satu sebagai dibaca, lalu tandai semua.
- **Harapan:** Counter dan status read konsisten; notifikasi hanya membuka resource milik pengguna.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## I. Pengaturan, keamanan, dan privasi

### UAT-I01 — Profil dan preferensi

- **Prioritas:** HIGH
- **Langkah:** Ubah nama, locale, zona waktu, format tanggal/waktu, dan mata uang.
- **Harapan:** Data tersimpan dan tampilan terkait mengikuti preferensi.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-I02 — Foto profil

- **Prioritas:** HIGH
- **Langkah:** Unggah JPG/PNG/WebP valid, coba file invalid, lalu hapus foto.
- **Harapan:** Foto valid menjadi WebP persegi, file invalid ditolak, dan file lama dibersihkan.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-I03 — Ganti password dan logout perangkat lain

- **Prioritas:** CRITICAL
- **Langkah:** Ganti password dengan password saat ini, lalu logout session lain.
- **Harapan:** Password lama tidak berlaku, session lain berakhir, dan security event tercatat.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-I04 — Ekspor data

- **Prioritas:** CRITICAL
- **Langkah:** Masukkan password benar dan salah pada ekspor data.
- **Harapan:** Password salah ditolak; password benar menghasilkan ZIP yang dapat dibuka dan berisi dataset pengguna serta foto bila tersedia.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-I05 — Penghapusan akun

- **Prioritas:** CRITICAL
- **Langkah:** Hanya lakukan pada database UAT. Hapus akun dengan konfirmasi dan password.
- **Harapan:** Data pengguna, session, token, notifikasi, dan file terkait dibersihkan tanpa orphan penting.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## J. Responsiveness, error, dan operasional

### UAT-J01 — Desktop, tablet, mobile

- **Prioritas:** HIGH
- **Langkah:** Uji login, dashboard, tabel/list, form, modal, dan navigasi pada desktop, tablet, serta mobile.
- **Harapan:** Tidak ada overflow yang menghalangi aksi, teks tetap terbaca, dan navigasi dapat digunakan.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-J02 — Custom error page

- **Prioritas:** HIGH
- **Langkah:** Buka URL yang tidak tersedia dan kondisi unauthorized yang aman diuji.
- **Harapan:** Error page tidak membocorkan stack trace dan menampilkan kode referensi.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-J03 — Queue dan scheduler

- **Prioritas:** CRITICAL
- **Langkah:** Jalankan queue worker dan scheduler, lalu picu proses otomatis.
- **Harapan:** Job diproses, tidak ada failure berulang, dan scheduler tidak membuat data duplikat.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

### UAT-J04 — Production smoke dan headers

- **Prioritas:** CRITICAL
- **Langkah:** Jalankan `composer release:smoke`.
- **Harapan:** `/up`, `/login`, custom 404, CSP, request ID, dan penyembunyian query metric headers seluruhnya lulus.
- **Status:** `[ ] PASS  [ ] FAIL  [ ] BLOCKED`
- **Catatan:**

---

## 5. Ringkasan hasil

```text
Tanggal UAT       :
Penguji           :
Commit            :
Browser desktop   :
Browser mobile    :
Database UAT      :

Total PASS        :
Total FAIL        :
Total BLOCKED     :
Blocker terbuka   :
High defect       :
Keputusan         : ACCEPTED / REJECTED / CONDITIONAL
```

## 6. Defect template

```text
ID defect       :
UAT terkait     :
Severity        : BLOCKER / HIGH / MEDIUM / LOW
Judul           :
Langkah ulang   :
Hasil aktual    :
Hasil harapan   :
Request ID      :
Screenshot/log  :
Status          : OPEN / FIXED / RETESTED / CLOSED
```
