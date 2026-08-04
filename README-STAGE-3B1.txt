Laras v1.0.1 — Tahap 3B.1
Perbaikan field tanggal/waktu dan activity live browser

Isi perubahan:
1. Label input date, time, datetime-local, month, dan week selalu berada di atas.
2. Mask tanggal/waktu bawaan browser tidak lagi bertumpuk dengan label.
3. Tab Aktivitas pada halaman /activities dimuat tanpa full-page refresh.
4. Pencarian aktivitas berjalan live dengan debounce 350 ms.
5. Filter jenis, prioritas, dan tanggal memperbarui panel secara langsung.
6. Reset dan pagination hanya mengganti area activity browser.
7. Request lama dibatalkan memakai AbortController.
8. URL tetap sinkron melalui History API dan tombol back/forward tetap berfungsi.

Catatan:
- Tahap ini tidak mengubah endpoint atau query backend.
- Server masih mengembalikan HTML halaman penuh, tetapi JavaScript hanya mengambil dan mengganti area data-activity-browser.
- Aksi status aktivitas seperti Mulai, Selesai, Batal, dan Arsip masih mengikuti alur form biasa. Aksi tersebut dapat dibuat asinkron pada tahap khusus berikutnya.
