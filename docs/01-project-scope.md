# Project Scope — Personal Life Management App

## 1. Informasi Dokumen

- **Nama proyek sementara:** Personal Life Management App
- **Jenis aplikasi:** Web app pribadi
- **Target pengguna:** Satu pengguna
- **Versi dokumen:** 1.0
- **Tanggal dokumen:** 31 Juli 2026
- **Status:** Ruang lingkup awal sebelum pengembangan

---

## 2. Gambaran Proyek

Personal Life Management App adalah web app pribadi yang digunakan untuk membantu pengguna mengelola kegiatan sehari-hari, keuangan, prioritas, kebiasaan, dan pengambilan keputusan secara lebih sistematis.

Aplikasi tidak hanya berfungsi sebagai tempat pencatatan. Sistem juga akan menganalisis data kegiatan dan keuangan, menghitung prioritas, menampilkan insight, serta memberikan rekomendasi yang berubah berdasarkan pola penggunaan dan respons pengguna.

Pada tahap awal, rekomendasi dibuat menggunakan aturan yang dapat dijelaskan. Setelah data penggunaan mencukupi, sistem dapat dikembangkan menjadi lebih adaptif dan menggunakan machine learning.

---

## 3. Latar Belakang

Pengguna memiliki berbagai kegiatan, tugas, proyek, pemasukan, pengeluaran, rekening bank, akun keuangan digital, dan uang tunai yang perlu dikelola dalam satu sistem.

Pencatatan yang terpisah dapat menyebabkan beberapa masalah, seperti:

- Kegiatan penting terlupakan atau terlambat dikerjakan.
- Kesulitan menentukan kegiatan yang harus didahulukan.
- Pengeluaran tidak tercatat secara lengkap.
- Saldo tersebar di beberapa rekening dan sulit dipantau.
- Biaya administrasi dan pajak tidak diperhatikan.
- Pengguna tidak mengetahui pola produktivitas dan pengeluaran.
- Bukti transaksi tersimpan terpisah dan sulit dicari.
- Pengguna harus memasukkan detail transaksi secara manual.

Aplikasi ini dirancang untuk menyelesaikan masalah tersebut dalam satu sistem yang cepat, responsif, interaktif, sederhana, dan mudah digunakan.

---

## 4. Target Pengguna

Aplikasi hanya digunakan oleh satu pengguna, yaitu pemilik aplikasi.

Walaupun hanya digunakan secara pribadi, sistem tetap menggunakan autentikasi untuk melindungi data kegiatan, keuangan, saldo, dan bukti transaksi.

Aplikasi tidak menyediakan fitur registrasi publik pada versi awal.

---

## 5. Tujuan Utama

Tujuan utama aplikasi adalah:

1. Mempermudah pencatatan kegiatan sehari-hari.
2. Mempermudah pencatatan pemasukan dan pengeluaran.
3. Mengelola saldo pada beberapa rekening, akun digital, dan cash.
4. Menentukan prioritas kegiatan secara otomatis.
5. Menilai tingkat kebutuhan suatu pengeluaran.
6. Memberikan rekomendasi berdasarkan kondisi pengguna.
7. Menampilkan pola produktivitas dan kondisi keuangan.
8. Menyimpan bukti transaksi secara terorganisasi.
9. Mempermudah input transaksi melalui pemindaian bukti pembayaran.
10. Membantu pengguna mengambil keputusan secara lebih sistematis.
11. Mengumpulkan data penggunaan untuk sistem adaptif pada tahap selanjutnya.

---

## 6. Prinsip Pengembangan

Aplikasi dikembangkan berdasarkan prinsip berikut:

- Cepat digunakan.
- Mobile-first.
- Responsif pada berbagai ukuran layar.
- User-friendly.
- Desain sederhana dan elegan.
- Tidak menggunakan tampilan generik atau berlebihan.
- Informasi penting mudah ditemukan.
- Setiap rekomendasi memiliki alasan.
- Pengguna tetap menjadi pengambil keputusan terakhir.
- Data keuangan harus akurat dan dapat ditelusuri.
- Fitur kompleks dikembangkan secara bertahap.
- Machine learning tidak digunakan sebelum data mencukupi.
- Keamanan data pribadi menjadi prioritas.

---

# 7. Ruang Lingkup MVP

## 7.1 Autentikasi

Aplikasi menyediakan autentikasi pribadi.

Fitur autentikasi:

- Login menggunakan email dan password.
- Remember me.
- Logout.
- Proteksi halaman menggunakan middleware.
- Hanya tersedia satu akun pengguna.
- Tidak tersedia registrasi publik.
- Reset password dapat ditambahkan apabila diperlukan.
- PIN cepat dapat dikembangkan setelah MVP stabil.

---

## 7.2 Dashboard Utama

Dashboard menampilkan informasi penting secara ringkas.

Informasi dashboard:

- Sapaan berdasarkan waktu.
- Tanggal hari ini.
- Ringkasan kegiatan hari ini.
- Kegiatan dengan prioritas tertinggi.
- Kegiatan yang mendekati deadline.
- Persentase kegiatan selesai.
- Total saldo seluruh akun.
- Total pemasukan bulan berjalan.
- Total pengeluaran bulan berjalan.
- Saldo bersih bulan berjalan.
- Rekomendasi utama.
- Peringatan keuangan.
- Transaksi terbaru.
- Riwayat aktivitas terbaru.
- Tombol Quick Add.

Dashboard tidak boleh dipenuhi terlalu banyak kartu. Informasi yang ditampilkan harus berhubungan dengan tindakan atau keputusan pengguna.

---

## 7.3 Quick Add

Quick Add merupakan fitur utama untuk menambahkan data dengan cepat.

Jenis data yang dapat ditambahkan:

- Kegiatan.
- Pengeluaran.
- Pemasukan.
- Transfer antar-akun.
- Catatan singkat.
- Penyesuaian saldo.

Quick Add dapat diakses melalui:

- Tombol tetap pada desktop.
- Tombol tengah pada bottom navigation mobile.
- Keyboard shortcut pada desktop.
- Tombol dari dashboard.

Form Quick Add hanya menampilkan data penting. Detail tambahan disembunyikan dalam bagian lanjutan.

---

# 8. Modul Kegiatan

## 8.1 Manajemen Kegiatan

Pengguna dapat:

- Menambahkan kegiatan.
- Melihat daftar kegiatan.
- Melihat detail kegiatan.
- Mengedit kegiatan.
- Menghapus kegiatan.
- Menentukan kategori kegiatan.
- Menentukan tanggal mulai.
- Menentukan deadline.
- Menentukan tingkat kepentingan.
- Menentukan tingkat dampak.
- Menentukan estimasi durasi.
- Menentukan waktu pengerjaan.
- Menambahkan deskripsi.
- Menambahkan catatan.
- Menandai kegiatan sedang dikerjakan.
- Menandai kegiatan selesai.
- Menunda kegiatan.
- Menjadwalkan ulang kegiatan.
- Membatalkan kegiatan.
- Mengurutkan kegiatan berdasarkan prioritas.
- Mencari kegiatan.
- Memfilter kegiatan berdasarkan status, kategori, tanggal, dan prioritas.

---

## 8.2 Status Kegiatan

Status kegiatan:

- `Planned`
- `In Progress`
- `Completed`
- `Postponed`
- `Cancelled`

Setiap perubahan status disimpan dalam riwayat aktivitas.

---

## 8.3 Data Kegiatan

Setiap kegiatan memiliki data:

- Judul.
- Deskripsi.
- Kategori.
- Status.
- Tingkat kepentingan.
- Tingkat dampak.
- Estimasi durasi.
- Durasi sebenarnya.
- Tanggal mulai.
- Deadline.
- Waktu mulai.
- Waktu selesai.
- Jumlah penundaan.
- Skor prioritas.
- Tingkat prioritas.
- Alasan prioritas.
- Waktu dibuat.
- Waktu diperbarui.
- Waktu selesai.

---

## 8.4 Sistem Prioritas Kegiatan

Sistem menghitung prioritas kegiatan menggunakan aturan yang dapat dijelaskan.

Faktor penilaian:

- Tingkat kepentingan.
- Tingkat dampak.
- Kedekatan deadline.
- Jumlah penundaan.
- Status kegiatan.
- Estimasi durasi.
- Waktu yang tersedia.
- Pola penyelesaian kegiatan sebelumnya.

Contoh rumus awal:

```text
Skor Prioritas =
Skor Kepentingan
+ Skor Dampak
+ Skor Kedekatan Deadline
+ Skor Penundaan
+ Skor Kemudahan Diselesaikan
```

Contoh aturan:

```text
Deadline kurang dari 24 jam  : +35
Deadline kurang dari 3 hari  : +20
Sangat penting               : +30
Dampak tinggi                : +20
Sudah ditunda 1 kali         : +8
Sudah ditunda 2 kali         : +15
Durasi kurang dari 30 menit  : +10
Sedang dikerjakan            : +10
Tidak memiliki deadline      : +0
```

Tingkat prioritas:

```text
80–100 : Critical
60–79  : High
40–59  : Medium
0–39   : Low
```

Sistem wajib menampilkan alasan prioritas.

Contoh:

> Prioritas tinggi karena deadline kurang dari 24 jam, kegiatan berdampak tinggi, dan telah ditunda dua kali.

---

# 9. Modul Keuangan

## 9.1 Manajemen Rekening dan Sumber Dana

Aplikasi mengelola beberapa tempat penyimpanan uang secara terpisah.

Jenis sumber dana:

- Rekening bank.
- Bank digital atau akun keuangan digital.
- E-wallet.
- Uang tunai atau cash.

Akun awal:

1. BCA.
2. Mandiri.
3. BNI Pribadi.
4. BNI Mahasiswa/Kampus.
5. SeaBank.
6. Cash.

Setiap akun memiliki informasi:

- Nama akun.
- Nama institusi.
- Jenis akun.
- Label atau fungsi akun.
- Saldo awal.
- Tanggal saldo awal.
- Saldo saat ini.
- Mata uang.
- Warna.
- Ikon.
- Nomor rekening atau identitas yang disamarkan.
- Status aktif atau tidak aktif.
- Batas saldo minimum.
- Catatan tambahan.
- Apakah saldo dihitung dalam total keseluruhan.

Contoh:

```text
Nama akun      : BNI Mahasiswa
Institusi      : BNI
Jenis          : Rekening bank
Fungsi         : Rekening keperluan kampus
Saldo awal     : Rp500.000
Tanggal saldo  : 31 Juli 2026
```

Pengguna dapat:

- Menambahkan akun.
- Mengedit akun.
- Menonaktifkan akun.
- Memasukkan saldo awal.
- Melihat saldo setiap akun.
- Melihat total saldo seluruh akun.
- Melihat riwayat transaksi setiap akun.
- Mengatur batas saldo minimum.
- Menentukan akun yang dihitung dalam total saldo.

Akun tidak dapat dihapus apabila sudah memiliki transaksi. Akun hanya dapat dinonaktifkan agar riwayat tetap tersimpan.

---

## 9.2 Saldo Awal

Setiap akun wajib memiliki saldo awal dan tanggal berlakunya saldo awal.

Contoh:

```text
Saldo awal BCA          : Rp1.000.000
Tanggal berlaku         : 31 Juli 2026
```

Saldo awal tidak dianggap sebagai pemasukan bulan berjalan. Saldo awal merupakan titik awal pencatatan keuangan.

Perubahan saldo awal setelah transaksi tersedia harus dicatat sebagai revisi atau penyesuaian, bukan mengganti nilai tanpa riwayat.

---

## 9.3 Perhitungan Saldo

Saldo setiap akun dihitung berdasarkan:

```text
Saldo saat ini =
Saldo awal
+ seluruh pemasukan
+ transfer masuk
+ refund
+ cashback masuk
- seluruh pengeluaran
- transfer keluar
- biaya administrasi
- pajak
± penyesuaian saldo
```

Saldo berasal dari riwayat transaksi dan tidak boleh hanya berupa angka yang diubah langsung.

Jika saldo aplikasi berbeda dengan saldo sebenarnya, pengguna dapat membuat transaksi penyesuaian saldo dengan alasan wajib.

Contoh alasan:

- Koreksi saldo awal.
- Transaksi lama belum dicatat.
- Biaya administrasi otomatis.
- Bunga rekening.
- Selisih uang tunai.
- Kesalahan pencatatan.
- Saldo aktual berbeda dengan sistem.

---

## 9.4 Jenis Transaksi

Jenis transaksi:

- `Income`
- `Expense`
- `Internal Transfer`
- `Balance Adjustment`
- `Refund`
- `Cashback`
- `Account Fee`

Status transaksi:

- `Draft`
- `Completed`
- `Pending`
- `Failed`
- `Cancelled`
- `Refunded`

Sumber input transaksi:

- `Manual`
- `Receipt Scan`
- `Payment Proof Scan`
- `Transfer Proof Scan`
- `Imported Data`

---

## 9.5 Detail Transaksi

Setiap transaksi dapat menyimpan:

- Jenis transaksi.
- Akun sumber dana.
- Akun tujuan.
- Nominal utama.
- Biaya administrasi.
- Pajak.
- Diskon.
- Cashback.
- Total uang keluar.
- Total uang masuk.
- Tanggal transaksi.
- Waktu transaksi.
- Tujuan penggunaan uang.
- Nama penerima.
- Nama merchant.
- Nama pengirim.
- Kategori.
- Metode pembayaran.
- Tingkat kebutuhan.
- Nomor referensi.
- Lokasi transaksi.
- Deskripsi.
- Catatan.
- Lampiran.
- Sumber input.
- Status transaksi.
- Saldo sebelum transaksi.
- Saldo setelah transaksi.
- Waktu dibuat.
- Waktu diperbarui.

---

## 9.6 Pengeluaran

Ketika menambahkan pengeluaran, pengguna dapat mengisi:

- Akun yang digunakan.
- Nominal utama.
- Tujuan pengeluaran.
- Penerima atau merchant.
- Kategori.
- Tingkat kebutuhan.
- Biaya administrasi.
- Pajak.
- Diskon.
- Cashback.
- Total pembayaran.
- Tanggal.
- Waktu.
- Metode pembayaran.
- Nomor referensi.
- Lokasi.
- Catatan.
- Bukti pembayaran.

Contoh:

```text
Akun              : BCA
Nominal utama     : Rp35.000
Tujuan            : Membeli makan malam
Penerima          : FamilyMart
Kategori          : Makanan dan minuman
Biaya admin       : Rp0
Pajak             : Rp3.500
Diskon            : Rp0
Cashback          : Rp0
Total keluar      : Rp38.500
Tingkat kebutuhan : Essential
```

Setelah transaksi berstatus `Completed`, saldo akun otomatis berkurang sebesar total uang keluar.

---

## 9.7 Pemasukan

Ketika menambahkan pemasukan, pengguna dapat mengisi:

- Akun penerima.
- Nominal pemasukan.
- Sumber pemasukan.
- Nama pengirim.
- Kategori pemasukan.
- Tanggal.
- Waktu.
- Nomor referensi.
- Catatan.
- Bukti transaksi.

Contoh sumber pemasukan:

- Uang bulanan.
- Beasiswa.
- Pendapatan freelance.
- Pengembalian dana.
- Bunga rekening.
- Penjualan.
- Pemasukan lainnya.

Setelah transaksi berstatus `Completed`, saldo akun penerima otomatis bertambah.

---

## 9.8 Transfer Antar-Akun

Pengguna dapat memindahkan uang dari satu akun miliknya ke akun lainnya.

Contoh:

```text
Akun sumber      : BCA
Akun tujuan      : SeaBank
Nominal transfer : Rp500.000
Biaya admin      : Rp2.500
```

Hasil:

```text
Saldo BCA berkurang     : Rp502.500
Saldo SeaBank bertambah : Rp500.000
```

Transfer antar-akun tidak dihitung sebagai pemasukan atau pengeluaran utama karena uang hanya berpindah tempat.

Biaya administrasi transfer tetap dihitung sebagai pengeluaran.

Validasi transfer:

- Akun sumber dan tujuan tidak boleh sama.
- Nominal harus lebih dari nol.
- Saldo sumber harus mencukupi.
- Saldo tujuan tidak bertambah jika transfer gagal.
- Transfer pending tidak langsung mengubah saldo final.
- Biaya admin dicatat terpisah.
- Transaksi transfer memiliki hubungan antara sisi keluar dan sisi masuk.

---

## 9.9 Perhitungan Nominal Transaksi

Untuk pengeluaran, nominal utama dibedakan dari biaya tambahan.

Contoh:

```text
Harga barang       : Rp50.000
Biaya administrasi : Rp2.500
Pajak              : Rp5.500
Diskon             : Rp3.000
Cashback langsung  : Rp0
Total uang keluar  : Rp55.000
```

Rumus:

```text
Total uang keluar =
Nominal utama
+ biaya administrasi
+ pajak
- diskon
- cashback langsung
```

Biaya administrasi, pajak, diskon, dan cashback disimpan secara terpisah agar dapat dianalisis.

---

## 9.10 Metode Pembayaran

Metode pembayaran awal:

- Transfer bank.
- Debit.
- QRIS.
- Virtual account.
- E-wallet.
- Cash.
- Autodebit.
- Pembayaran lainnya.

Metode pembayaran tidak selalu sama dengan akun sumber. Misalnya transaksi QRIS dapat menggunakan saldo BCA atau SeaBank.

---

## 9.11 Tingkat Kebutuhan Pengeluaran

Tingkat kebutuhan:

- `Essential`
- `Important`
- `Optional`
- `Unnecessary`

Definisi:

- **Essential:** kebutuhan utama yang sulit dihindari.
- **Important:** penting, tetapi masih dapat direncanakan.
- **Optional:** tidak wajib dan dapat ditunda.
- **Unnecessary:** tidak diperlukan atau berpotensi disesali.

Sistem dapat memberikan saran awal berdasarkan kategori, nominal, anggaran, dan riwayat transaksi. Pengguna tetap dapat mengubah tingkat kebutuhan.

---

## 9.12 Kategori Keuangan

Pengguna dapat membuat kategori untuk pemasukan dan pengeluaran.

Contoh kategori pengeluaran:

- Makanan dan minuman.
- Transportasi.
- Pendidikan.
- Kuliah.
- Teknologi.
- Internet.
- Hiburan.
- Kesehatan.
- Kebutuhan rumah.
- Belanja.
- Langganan.
- Biaya administrasi.
- Pajak.
- Lainnya.

Contoh kategori pemasukan:

- Uang bulanan.
- Beasiswa.
- Freelance.
- Penjualan.
- Refund.
- Bunga.
- Hadiah.
- Lainnya.

Setiap kategori memiliki:

- Nama.
- Jenis.
- Ikon.
- Warna.
- Status aktif.
- Batas anggaran opsional.
- Catatan.

---

## 9.13 Anggaran

Pengguna dapat membuat anggaran berdasarkan kategori dan periode.

Data anggaran:

- Kategori.
- Nominal anggaran.
- Periode.
- Tanggal mulai.
- Tanggal berakhir.
- Nominal terpakai.
- Nominal tersisa.
- Persentase penggunaan.
- Status.

Status anggaran:

- Aman.
- Mendekati batas.
- Melebihi batas.

Sistem memberikan peringatan ketika penggunaan anggaran melewati batas tertentu.

---

## 9.14 Riwayat Transaksi

Riwayat dapat difilter berdasarkan:

- Akun.
- Jenis transaksi.
- Pemasukan atau pengeluaran.
- Kategori.
- Merchant.
- Penerima.
- Pengirim.
- Rentang tanggal.
- Rentang nominal.
- Status.
- Sumber input.
- Metode pembayaran.
- Transaksi yang memiliki lampiran.
- Transaksi dengan biaya admin.
- Transaksi dengan pajak.

Detail riwayat menampilkan:

- Informasi transaksi.
- Saldo sebelum transaksi.
- Perubahan saldo.
- Saldo setelah transaksi.
- Bukti pembayaran.
- Riwayat perubahan.
- Sumber input.
- Status konfirmasi.

---

## 9.15 Penyesuaian Saldo

Penyesuaian saldo digunakan ketika saldo sistem berbeda dengan saldo aktual.

Data penyesuaian:

- Akun.
- Jenis penyesuaian.
- Nominal perubahan.
- Saldo sistem.
- Saldo aktual.
- Alasan.
- Tanggal.
- Catatan.

Penyesuaian saldo tidak boleh menghapus riwayat transaksi sebelumnya.

---

# 10. Pemindaian Struk dan Bukti Transaksi

## 10.1 Tujuan

Fitur pemindaian membantu pengguna mengisi transaksi secara lebih cepat dari:

- Struk pembelian.
- Bukti pembayaran.
- Bukti transfer.
- Screenshot mobile banking.
- Screenshot e-wallet.
- Foto nota.
- Invoice.
- Dokumen transaksi lainnya.

---

## 10.2 Alur Pemindaian

```text
Unggah atau ambil foto
→ Sistem memvalidasi file
→ Sistem membaca dokumen
→ Sistem mengekstrak informasi
→ Sistem membuat draft transaksi
→ Form terisi otomatis
→ Pengguna memeriksa hasil
→ Pengguna memperbaiki jika diperlukan
→ Pengguna mengonfirmasi
→ Transaksi disimpan
→ Saldo diperbarui
```

Hasil pemindaian tidak boleh langsung menjadi transaksi final tanpa konfirmasi pengguna.

---

## 10.3 Data yang Diekstrak

Sistem mencoba mengekstrak:

- Nama merchant.
- Nama penerima.
- Nama pengirim.
- Tanggal transaksi.
- Waktu transaksi.
- Nominal utama.
- Biaya administrasi.
- Pajak.
- Diskon.
- Cashback.
- Total pembayaran.
- Nomor referensi.
- Nama bank.
- Nama layanan pembayaran.
- Akun sumber.
- Akun tujuan.
- Metode pembayaran.
- Deskripsi transaksi.
- Item pembelian jika tersedia.

---

## 10.4 Confidence Score

Setiap hasil pembacaan memiliki tingkat keyakinan.

Contoh:

```text
Merchant       : FamilyMart       96%
Tanggal        : 31 Juli 2026     92%
Total          : Rp38.500         98%
Pajak          : Rp3.500          81%
Akun sumber    : BCA              64%
```

Aturan:

- Confidence tinggi dapat diterima otomatis pada form.
- Confidence sedang ditandai untuk pemeriksaan.
- Confidence rendah wajib dikonfirmasi atau diisi manual.
- Sistem tidak boleh mengubah saldo sebelum pengguna menyimpan hasil.

---

## 10.5 Status Hasil Scan

Status hasil scan:

- `Uploaded`
- `Processing`
- `Needs Review`
- `Confirmed`
- `Rejected`
- `Failed`

---

## 10.6 Batasan MVP Scan

Pada MVP lanjutan, fitur scan hanya membantu mengisi form.

Fitur scan belum mencakup:

- Penyimpanan otomatis tanpa konfirmasi.
- Pembacaan sempurna dari semua format.
- Impor mutasi otomatis dari rekening.
- Rekonsiliasi bank otomatis.
- Sinkronisasi langsung dengan mobile banking.
- Pembacaan banyak dokumen sekaligus.
- Deteksi fraud kompleks.

---

# 11. Pengelolaan Lampiran

Setiap transaksi dapat memiliki satu atau beberapa lampiran.

Jenis lampiran:

- Foto struk.
- Screenshot pembayaran.
- Bukti transfer.
- Invoice.
- Nota.
- Dokumen lainnya.

Ketentuan:

- File hanya dapat diakses oleh pengguna.
- File tidak disimpan secara terbuka tanpa proteksi.
- Jenis file divalidasi.
- Ukuran file dibatasi.
- Nama file dibuat aman.
- Lampiran dapat diganti.
- Lampiran dapat dihapus tanpa menghapus transaksi.
- Identitas rekening sensitif disamarkan saat ditampilkan.
- Metadata file disimpan.
- File dapat dikompresi untuk menghemat penyimpanan.

---

# 12. Catatan Singkat

Pengguna dapat membuat catatan singkat melalui Quick Add.

Data catatan:

- Judul opsional.
- Isi catatan.
- Kategori.
- Tanggal.
- Status pin.
- Warna atau label.
- Waktu dibuat.
- Waktu diperbarui.

Catatan tidak memiliki sistem kompleks pada MVP.

---

# 13. Sistem Rekomendasi

## 13.1 Rekomendasi Berbasis Aturan

Pada MVP, sistem rekomendasi menggunakan aturan yang dapat dijelaskan.

Jenis rekomendasi:

- Kegiatan yang harus dikerjakan lebih dahulu.
- Peringatan deadline.
- Saran membagi kegiatan besar.
- Saran menjadwalkan ulang kegiatan.
- Peringatan saldo rendah.
- Peringatan anggaran.
- Pengeluaran kategori tertentu meningkat.
- Biaya administrasi terlalu sering.
- Penggunaan akun tidak sesuai fungsi.
- Transaksi scan belum dikonfirmasi.
- Bukti transaksi belum tersedia.
- Perbedaan saldo perlu diperiksa.

---

## 13.2 Rekomendasi Kegiatan

Contoh aturan:

```text
Jika deadline kurang dari 24 jam dan status masih Planned:
Sarankan memulai kegiatan.

Jika kegiatan ditunda lebih dari dua kali:
Sarankan membagi kegiatan menjadi beberapa bagian.

Jika estimasi kegiatan sesuai waktu kosong:
Sarankan kegiatan tersebut untuk dikerjakan.

Jika kegiatan berdampak tinggi dan belum dimulai:
Naikkan prioritas rekomendasi.
```

---

## 13.3 Rekomendasi Keuangan

Contoh aturan:

```text
Jika pengeluaran kategori melebihi anggaran:
Tampilkan peringatan.

Jika biaya admin sering terjadi:
Tampilkan total biaya admin dan saran evaluasi.

Jika saldo akun mendekati batas minimum:
Tampilkan peringatan saldo rendah.

Jika rekening kampus digunakan untuk transaksi non-pendidikan:
Tampilkan saran pemeriksaan.

Jika transaksi scan belum dikonfirmasi:
Tampilkan pengingat untuk meninjau draft.
```

---

## 13.4 Data Rekomendasi

Setiap rekomendasi memiliki:

- Jenis rekomendasi.
- Judul.
- Isi.
- Alasan.
- Tingkat prioritas.
- Confidence score.
- Data sumber.
- Tindakan yang disarankan.
- Waktu dibuat.
- Waktu kedaluwarsa.
- Status.

Status rekomendasi:

- `Active`
- `Followed`
- `Scheduled`
- `Postponed`
- `Ignored`
- `Not Relevant`
- `Expired`

---

## 13.5 Feedback Rekomendasi

Pengguna dapat memberikan respons:

- Diikuti.
- Dijadwalkan.
- Ditunda.
- Diabaikan.
- Tidak relevan.

Data feedback:

- Rekomendasi.
- Respons.
- Alasan opsional.
- Waktu respons.
- Tindakan lanjutan.
- Hasil akhir.

Feedback menjadi dasar pengembangan sistem adaptif.

---

# 14. Insight dan Laporan

## 14.1 Insight Kegiatan

Sistem dapat menampilkan:

- Jumlah kegiatan selesai.
- Jumlah kegiatan ditunda.
- Tingkat penyelesaian.
- Kategori kegiatan terbanyak.
- Hari paling produktif.
- Jam paling produktif.
- Perbandingan estimasi dan durasi sebenarnya.
- Kegiatan yang sering tertunda.
- Pola penyelesaian mingguan.

---

## 14.2 Insight Keuangan

Sistem dapat menampilkan:

- Total saldo keseluruhan.
- Saldo setiap akun.
- Pemasukan per periode.
- Pengeluaran per periode.
- Pengeluaran per kategori.
- Pengeluaran per akun.
- Pengeluaran per merchant.
- Total biaya administrasi.
- Total pajak.
- Total diskon.
- Total cashback.
- Transfer antar-akun.
- Perubahan saldo.
- Kategori paling boros.
- Perbandingan periode.
- Anggaran terpakai.
- Transaksi tanpa bukti.
- Transaksi hasil scan.

---

## 14.3 Dashboard Rekening

Dashboard rekening menampilkan:

- Total saldo.
- Saldo BCA.
- Saldo Mandiri.
- Saldo BNI Pribadi.
- Saldo BNI Mahasiswa.
- Saldo SeaBank.
- Saldo Cash.
- Akun dengan saldo terbesar.
- Akun dengan saldo rendah.
- Perubahan saldo bulan ini.
- Transaksi terbaru.
- Transfer terbaru.
- Peringatan saldo.

---

# 15. Kategori

Kategori dapat digunakan untuk:

- Kegiatan.
- Pemasukan.
- Pengeluaran.
- Catatan.

Setiap kategori memiliki:

- Nama.
- Jenis.
- Ikon.
- Warna.
- Deskripsi.
- Status aktif.
- Waktu dibuat.
- Waktu diperbarui.

Kategori tidak boleh dihapus apabila masih digunakan. Kategori dapat dinonaktifkan.

---

# 16. Activity Log dan Audit Trail

Sistem menyimpan aktivitas penting.

Data yang dicatat:

- Login.
- Logout.
- Pembuatan kegiatan.
- Perubahan kegiatan.
- Penyelesaian kegiatan.
- Penundaan kegiatan.
- Pembuatan transaksi.
- Perubahan transaksi.
- Penghapusan transaksi.
- Transfer antar-akun.
- Penyesuaian saldo.
- Perubahan rekening.
- Hasil scan.
- Konfirmasi scan.
- Feedback rekomendasi.

Setiap log memiliki:

- Jenis tindakan.
- Jenis data.
- ID data.
- Nilai sebelum.
- Nilai setelah.
- Metadata.
- Waktu.
- Alamat IP jika diperlukan.
- User agent jika diperlukan.

---

# 17. Tampilan dan Pengalaman Pengguna

## 17.1 Konsep Desain

Konsep desain:

> Calm Intelligence

Karakter desain:

- Modern.
- Sederhana.
- Elegan.
- Tenang.
- Interaktif.
- Tidak terlihat seperti template generik.
- Tidak menggunakan efek berlebihan.
- Fokus pada informasi dan tindakan.

---

## 17.2 Warna

Palet awal:

```text
Primary Blue       #2563EB
Deep Navy          #0F172A
Soft Blue          #DBEAFE
Warm Amber         #F59E0B
Fresh Green        #22C55E
Danger Coral       #F43F5E
Background Light   #F8FAFC
Surface White      #FFFFFF
Muted Text         #64748B
```

Penggunaan:

- Biru untuk identitas dan tindakan utama.
- Amber untuk perhatian dan prioritas.
- Hijau untuk pemasukan, selesai, dan kondisi aman.
- Coral untuk risiko, pengeluaran kritis, dan error.
- Navy untuk teks utama dan dark mode.

---

## 17.3 Layout

Desktop:

- Sidebar.
- Header.
- Area konten.
- Quick Add tetap.
- Panel rekomendasi.

Mobile:

- Bottom navigation.
- Tombol Quick Add di tengah.
- Bottom sheet untuk form.
- Layout satu kolom.
- Area sentuh yang cukup besar.

---

## 17.4 Navigasi Utama

Menu utama:

```text
Dashboard
Activities
Finance
Accounts
Transactions
Recommendations
Insights
Notes
Settings
```

Submenu Finance:

```text
Overview
Income
Expenses
Transfers
Budgets
Scan Transaction
Transaction History
```

---

## 17.5 Animasi

Animasi yang direncanakan:

- Opening animation.
- Page transition.
- Sidebar active indicator.
- Bottom sheet transition.
- Modal transition.
- Checkbox completion.
- Count-up angka.
- Progress bar.
- Chart entrance.
- Hover state.
- Press state.
- Skeleton loading.

Opening animation hanya tampil penuh saat pertama kali membuka aplikasi atau setelah login.

Durasi animasi harus singkat dan tidak menghambat penggunaan.

Aplikasi mendukung:

```css
@media (prefers-reduced-motion: reduce)
```

---

## 17.6 Dark Mode

Warna dark mode awal:

```text
Background   #08111F
Surface      #111C2E
Elevated     #17243A
Primary      #60A5FA
Text         #F8FAFC
Muted        #94A3B8
```

Dark mode dapat mengikuti sistem atau diatur manual.

---

## 17.7 Aksesibilitas

Aplikasi harus:

- Memiliki kontras warna yang cukup.
- Dapat digunakan dengan keyboard.
- Memiliki focus state.
- Tidak membedakan status hanya berdasarkan warna.
- Memiliki label pada input.
- Memiliki pesan error yang jelas.
- Mendukung reduced motion.
- Menggunakan ukuran tombol yang nyaman.
- Menyediakan alt text pada gambar.
- Menampilkan loading state.
- Menampilkan empty state.
- Menampilkan error state.

---

# 18. Keamanan dan Akurasi Data

Sistem harus:

- Menggunakan tipe `decimal` untuk uang.
- Tidak menggunakan `float` untuk saldo.
- Memvalidasi seluruh input.
- Menggunakan autentikasi dan middleware.
- Menyimpan password dalam bentuk hash.
- Melindungi file bukti transaksi.
- Menyamarkan nomor rekening.
- Mencatat perubahan transaksi.
- Meminta konfirmasi sebelum penghapusan.
- Menghitung ulang saldo setelah edit atau penghapusan.
- Mencegah saldo berubah tanpa riwayat.
- Menghindari mass assignment yang tidak aman.
- Menggunakan CSRF protection.
- Membatasi jenis dan ukuran file.
- Memeriksa nama file unggahan.
- Membuat backup database.
- Menyimpan waktu pembuatan dan perubahan.
- Menghindari hard delete pada data keuangan penting.
- Menjaga konsistensi transaksi menggunakan database transaction.

---

# 19. Teknologi

Teknologi awal:

```text
Backend             : Laravel
Frontend            : Blade
Styling             : Tailwind CSS
Interaksi           : Alpine.js dan JavaScript
Database            : MySQL
Grafik              : Chart.js
Animasi             : GSAP
Ikon                : Lucide Icons
Notifikasi          : SweetAlert2 untuk konfirmasi penting
Development         : Laragon
Version Control     : Git dan GitHub
```

Teknologi scan yang dapat dipertimbangkan pada tahap lanjutan:

```text
OCR lokal           : Tesseract OCR
Image processing    : Python / OpenCV
OCR API             : Google Cloud Vision atau layanan sejenis
Service tambahan    : Python + FastAPI
```

Pemilihan teknologi scan dilakukan setelah modul transaksi manual stabil.

---

# 20. Struktur Modul Aplikasi

```text
Dashboard
├── Daily Focus
├── Priority Activities
├── Financial Summary
├── Account Balances
├── Recommendations
└── Recent Activity

Activities
├── Activity List
├── Activity Detail
├── Priority
├── Categories
└── History

Finance
├── Overview
├── Accounts
├── Income
├── Expenses
├── Transfers
├── Budgets
├── Scan Transaction
└── Transaction History

Recommendations
├── Active Recommendations
├── Recommendation History
└── Feedback

Insights
├── Productivity
├── Financial
├── Account Analysis
└── Weekly Review

Notes
Settings
```

---

# 21. Data Awal Rekening

Data rekening awal yang disiapkan:

| Nama Akun | Institusi | Jenis | Fungsi |
|---|---|---|---|
| BCA | Bank Central Asia | Bank | Pribadi |
| Mandiri | Bank Mandiri | Bank | Pribadi |
| BNI Pribadi | Bank Negara Indonesia | Bank | Pribadi |
| BNI Mahasiswa | Bank Negara Indonesia | Bank | Kampus/Pendidikan |
| SeaBank | SeaBank Indonesia | Bank Digital | Pribadi |
| Cash | Tunai | Cash | Pengeluaran tunai |

Saldo awal belum ditentukan dalam dokumen dan akan dimasukkan langsung oleh pengguna ketika sistem siap.

---

# 22. Fitur yang Termasuk MVP Inti

MVP inti mencakup:

- Login pribadi.
- Dashboard.
- Quick Add.
- Manajemen kegiatan.
- Kategori kegiatan.
- Sistem skor prioritas.
- Manajemen rekening.
- Input saldo awal.
- Saldo setiap akun.
- Total saldo.
- Pemasukan.
- Pengeluaran.
- Transfer antar-akun.
- Biaya administrasi.
- Pajak.
- Diskon.
- Cashback.
- Detail merchant atau penerima.
- Riwayat transaksi.
- Penyesuaian saldo.
- Anggaran sederhana.
- Lampiran bukti transaksi.
- Rekomendasi berbasis aturan.
- Feedback rekomendasi.
- Activity log.
- Responsive design.
- Light mode.
- Dark mode.
- Opening animation.
- Page transition.
- Loading state.
- Empty state.
- Error state.

---

# 23. Fitur MVP Lanjutan

MVP lanjutan mencakup:

- Pemindaian foto struk.
- Pemindaian bukti pembayaran.
- Pemindaian bukti transfer.
- OCR.
- Pengisian form otomatis.
- Confidence score.
- Draft transaksi hasil scan.
- Pemeriksaan hasil scan.
- Deteksi kategori awal.
- Deteksi akun sumber awal.
- Peringatan kemungkinan transaksi duplikat.

Fitur ini dikerjakan setelah transaksi manual dan perhitungan saldo stabil.

---

# 24. Fitur Setelah MVP Stabil

Fitur setelah MVP:

- Habit tracker.
- Target tabungan.
- Kalender kompleks.
- Notifikasi push.
- Sinkronisasi Google Calendar.
- Voice input.
- OCR struk lanjutan.
- Impor mutasi rekening.
- Rekonsiliasi otomatis.
- Pengenalan merchant.
- Prediksi pengeluaran.
- Prediksi risiko anggaran.
- Saran pemindahan saldo.
- Machine learning.
- Aplikasi Android atau PWA penuh.
- Biometric login.
- Pembacaan banyak bukti transaksi.
- Ekspor laporan PDF.
- Ekspor laporan Excel.
- Backup otomatis ke cloud.

---

# 25. Fitur yang Tidak Termasuk Versi Awal

Fitur berikut tidak dibuat pada tahap pertama:

- Multi-user.
- Registrasi publik.
- Fitur sosial.
- Berbagi keuangan dengan pengguna lain.
- Integrasi langsung dengan rekening bank.
- Autodebit melalui aplikasi.
- Pengiriman uang melalui aplikasi.
- Pembayaran melalui aplikasi.
- Sinkronisasi real-time dengan mobile banking.
- Sistem investasi.
- Sistem pinjaman.
- Sistem akuntansi bisnis.
- AI generatif sebagai pusat keputusan.
- Penyimpanan otomatis hasil scan tanpa konfirmasi.

---

# 26. Kriteria Keberhasilan MVP

MVP dianggap berhasil apabila pengguna dapat:

1. Login ke aplikasi.
2. Menambahkan kegiatan dengan cepat.
3. Melihat kegiatan berdasarkan prioritas.
4. Menyelesaikan dan menunda kegiatan.
5. Menambahkan seluruh akun keuangan.
6. Memasukkan saldo awal setiap akun.
7. Menambahkan pemasukan.
8. Menambahkan pengeluaran.
9. Memilih akun sumber pengeluaran.
10. Melakukan transfer antar-akun.
11. Mencatat biaya administrasi dan pajak.
12. Melihat saldo setiap akun.
13. Melihat total saldo.
14. Melihat riwayat transaksi.
15. Melihat detail transaksi.
16. Mengunggah bukti transaksi.
17. Membuat penyesuaian saldo.
18. Mendapatkan rekomendasi berbasis aturan.
19. Memberikan feedback pada rekomendasi.
20. Menggunakan aplikasi pada mobile dan desktop.
21. Menggunakan light mode dan dark mode.
22. Menyelesaikan alur utama tanpa error.
23. Mendapatkan hasil saldo yang konsisten dengan riwayat transaksi.
24. Melihat alasan penentuan prioritas dan rekomendasi.

---

# 27. Risiko Pengembangan

Risiko yang perlu diperhatikan:

## 27.1 Ruang Lingkup Terlalu Besar

Solusi:

- Memisahkan MVP inti dan MVP lanjutan.
- Menyelesaikan transaksi manual sebelum OCR.
- Menunda machine learning.
- Mengembangkan per modul.

## 27.2 Saldo Tidak Konsisten

Solusi:

- Menggunakan database transaction.
- Menghindari perubahan saldo langsung.
- Menyimpan saldo sebelum dan sesudah transaksi.
- Menambahkan audit trail.
- Membuat pengujian perhitungan saldo.

## 27.3 Hasil OCR Tidak Akurat

Solusi:

- Menggunakan draft.
- Menampilkan confidence score.
- Meminta konfirmasi pengguna.
- Menyimpan hasil asli dan hasil koreksi.
- Tidak langsung mengubah saldo.

## 27.4 Data Sensitif Terbuka

Solusi:

- Menyimpan file secara privat.
- Menyamarkan nomor rekening.
- Menggunakan autentikasi.
- Membatasi akses file.
- Melakukan validasi upload.

## 27.5 Desain Terlalu Kompleks

Solusi:

- Menggunakan design system.
- Membatasi jumlah komponen.
- Mengutamakan fungsi.
- Menambahkan animasi setelah fitur stabil.

---

# 28. Urutan Pengembangan

Urutan pengembangan:

```text
1. Finalisasi project scope
2. Menentukan nama proyek
3. Membuat user flow
4. Membuat sitemap
5. Membuat wireframe
6. Membuat design system
7. Menentukan struktur database
8. Membuat ERD
9. Setup Laravel
10. Setup Git dan GitHub
11. Membuat autentikasi
12. Membuat layout dasar
13. Membuat manajemen rekening
14. Membuat saldo awal
15. Membuat transaksi pemasukan
16. Membuat transaksi pengeluaran
17. Membuat transfer antar-akun
18. Membuat perhitungan saldo
19. Membuat riwayat transaksi
20. Membuat modul kegiatan
21. Membuat priority engine
22. Membuat recommendation engine
23. Membuat dashboard
24. Membuat lampiran transaksi
25. Menambahkan animasi
26. Menguji responsive design
27. Membuat testing
28. Deployment versi awal
29. Mengembangkan OCR dan scan
30. Mengembangkan sistem adaptif
31. Mengembangkan machine learning
```

---

# 29. Keputusan Arsitektur Awal

Keputusan awal:

- Aplikasi hanya untuk satu pengguna.
- Database menggunakan MySQL.
- Data uang menggunakan decimal.
- Saldo dihitung dari transaksi.
- Transfer internal tidak dihitung sebagai pemasukan atau pengeluaran utama.
- Biaya admin transfer dihitung sebagai pengeluaran.
- Saldo awal memiliki tanggal berlaku.
- Akun yang memiliki transaksi tidak dapat dihapus permanen.
- Transaksi keuangan penting menggunakan soft delete atau mekanisme arsip.
- Hasil scan disimpan sebagai draft.
- Saldo tidak berubah sebelum transaksi dikonfirmasi.
- Sistem rekomendasi awal menggunakan aturan.
- Machine learning dikembangkan setelah data mencukupi.
- Desain dibuat mobile-first.
- Animasi tidak boleh menghambat penggunaan.

---

# 30. Catatan Pengembangan

Dokumen ini merupakan dasar pengembangan dan dapat diperbarui apabila ditemukan kebutuhan baru.

Setiap perubahan ruang lingkup harus dicatat agar pengembangan tidak melebar tanpa kontrol.

Sebelum mulai coding, tahap berikutnya adalah:

1. Menentukan nama final atau nama sementara proyek.
2. Membuat user flow.
3. Membuat daftar halaman.
4. Membuat rancangan database.
5. Menentukan prioritas fitur untuk fase pertama.
