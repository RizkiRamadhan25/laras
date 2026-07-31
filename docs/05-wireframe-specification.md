# Wireframe Specification — Laras

## 1. Informasi Dokumen

- **Nama proyek:** Laras
- **Tagline:** Selaraskan hari, tentukan langkah.
- **Jenis dokumen:** Wireframe Specification
- **Versi:** 1.0
- **Tanggal:** 31 Juli 2026
- **Dokumen terkait:**
  - `01-project-scope.md`
  - `02-user-flow.md`
  - `03-sitemap-and-page-list.md`
  - `04-feature-priority-and-development-phases.md`

---

## 2. Tujuan Dokumen

Dokumen ini menjadi acuan awal untuk:

- Menentukan struktur visual setiap halaman.
- Menentukan hierarki informasi.
- Menentukan urutan komponen.
- Menentukan perilaku responsive.
- Menentukan posisi navigasi dan aksi utama.
- Menentukan state loading, empty, error, dan success.
- Menjadi dasar pembuatan low-fidelity wireframe di Figma.
- Menjadi acuan implementasi Blade dan Tailwind CSS.

Dokumen ini belum menentukan desain visual final seperti ilustrasi, detail warna, bayangan, atau animasi penuh.

---

# 3. Prinsip Wireframe Laras

Wireframe Laras mengikuti prinsip berikut:

1. Informasi terpenting muncul lebih dahulu.
2. Aksi utama maksimal satu atau dua per area.
3. Quick Add harus selalu mudah dijangkau.
4. Form panjang menggunakan progressive disclosure.
5. Pengguna dapat memahami saldo dan transaksi tanpa membuka banyak halaman.
6. Sistem rekomendasi tidak mendominasi halaman.
7. Mobile menjadi prioritas utama.
8. Desktop menggunakan ruang lebih luas untuk perbandingan data.
9. State kosong harus memberi arahan tindakan.
10. Data keuangan harus mudah diverifikasi.
11. Status tidak hanya dibedakan dengan warna.
12. Setiap halaman memiliki fokus yang jelas.

---

# 4. Grid dan Spacing Dasar

## 4.1 Desktop

```text
Canvas width         : 1280–1440 px
Sidebar width        : 240–272 px
Collapsed sidebar    : 72–80 px
Content max width    : 1200–1280 px
Page padding         : 24–32 px
Column gap           : 20–24 px
Section gap          : 24–32 px
Card padding         : 20–24 px
```

## 4.2 Tablet

```text
Canvas width         : 768–1023 px
Sidebar              : drawer / collapsed
Page padding         : 20–24 px
Grid                 : 2 columns when possible
```

## 4.3 Mobile

```text
Canvas width         : 360–430 px
Page padding         : 16–20 px
Bottom navigation    : 64–72 px
Sticky action area   : 64–80 px
Card gap             : 12–16 px
Section gap          : 20–24 px
```

---

# 5. App Shell

## 5.1 Desktop App Shell

```text
┌───────────────────────────────────────────────────────────────┐
│ Sidebar │ Header                                              │
│         ├─────────────────────────────────────────────────────┤
│         │ Main Content                                        │
│         │                                                     │
│         │                                                     │
│         │                                                     │
└───────────────────────────────────────────────────────────────┘
```

### Sidebar

Urutan menu:

```text
Logo Laras
Dashboard
Activities
Finance
Recommendations
Insights
Notes
────────────
Settings
Profile
```

### Header

Komponen:

- Page title.
- Breadcrumb opsional.
- Global search.
- Quick Add.
- Notification.
- Theme switcher.
- Profile menu.

---

## 5.2 Mobile App Shell

```text
┌──────────────────────┐
│ Mobile Header        │
├──────────────────────┤
│                      │
│ Main Content         │
│                      │
├──────────────────────┤
│ Bottom Navigation    │
└──────────────────────┘
```

Bottom navigation:

```text
Home | Activities | Add | Finance | Insights
```

Tombol Add berada di tengah dan sedikit menonjol.

---

# 6. Opening Animation Wireframe

## 6.1 Frame 1

```text
Full-screen deep blue background

           [small glowing point]
```

## 6.2 Frame 2

```text
Icons move inward:

activity icon  →  center
finance icon   →  center
insight icon   →  center
```

## 6.3 Frame 3

```text
              LARAS
Selaraskan hari, tentukan langkah.
```

## 6.4 Frame 4

Logo mengecil menuju posisi header atau halaman login.

### Interaksi

- Skip di pojok kanan atas.
- Durasi maksimum sekitar 2 detik.
- Reduced motion menggunakan fade sederhana.
- Tidak tampil penuh setiap navigasi.

---

# 7. Login Page

## 7.1 Desktop

```text
┌────────────────────────┬───────────────────────────────┐
│ Brand / Illustration   │ Login Form                    │
│                        │                               │
│ Laras                  │ Selamat datang kembali       │
│ Tagline                │ Email                        │
│ Short description      │ Password                     │
│                        │ Remember me                   │
│                        │ [Masuk]                       │
│                        │ Forgot password               │
└────────────────────────┴───────────────────────────────┘
```

## 7.2 Mobile

```text
Logo
Welcome text

Email
Password
Remember me

[Masuk]

Forgot password
```

### State

- Default.
- Invalid login.
- Loading.
- Session expired.
- Offline.

---

# 8. Onboarding

## 8.1 Onboarding Layout

```text
Progress indicator
Step title
Short explanation
Main form
Sticky action footer
```

## 8.2 Welcome

Komponen:

- Logo.
- Judul sambutan.
- Ringkasan fungsi.
- Tiga manfaat utama.
- Tombol Mulai.

## 8.3 Profile Setup

```text
Nama
Email
Zona waktu
Mata uang
Format tanggal

[Back] [Next]
```

## 8.4 Account Selection

```text
Pilih akun yang digunakan

[✓] BCA
[✓] Mandiri
[✓] BNI Pribadi
[✓] BNI Mahasiswa
[✓] SeaBank
[✓] Cash

[+ Tambah akun lain]

[Back] [Next]
```

## 8.5 Initial Balance

Setiap akun menggunakan card:

```text
BCA
Saldo awal        [Rp ...]
Tanggal berlaku   [Date]
Batas minimum     [Optional]
Nomor tersamar    [Optional]
```

## 8.6 Review

```text
Profil
Daftar akun
Saldo awal
Total saldo awal

[Edit] [Selesaikan Setup]
```

---

# 9. Dashboard

## 9.1 Desktop Layout

```text
┌───────────────────────────────────────────────────────────────┐
│ Greeting + Date                           Quick Add            │
├──────────────────────────────┬────────────────────────────────┤
│ Daily Focus                  │ Main Recommendation            │
├──────────────────────────────┼────────────────────────────────┤
│ Priority Activities          │ Financial Summary              │
│                              │                                │
├──────────────────────────────┴────────────────────────────────┤
│ Account Balance Strip                                         │
├──────────────────────────────┬────────────────────────────────┤
│ Recent Transactions          │ Recent Activity                │
└──────────────────────────────┴────────────────────────────────┘
```

## 9.2 Mobile Layout

```text
Greeting + Date

Daily Focus

Main Recommendation

Priority Activities

Financial Summary

Account Balances
(horizontal scroll)

Recent Transactions

Recent Activity
```

## 9.3 Daily Focus Card

Isi:

- Jumlah kegiatan hari ini.
- Deadline penting.
- Estimasi fokus.
- Progress harian.
- Tombol mulai kegiatan utama.

## 9.4 Recommendation Card

Isi:

- Label rekomendasi.
- Judul.
- Alasan singkat.
- Related item.
- Primary action.
- Secondary action.
- Feedback action.

## 9.5 Financial Summary

Isi:

- Total balance.
- Income month.
- Expense month.
- Net flow.
- Small trend chart.

## 9.6 Account Balance Strip

Desktop: grid horizontal.

Mobile: horizontal scroll.

Setiap card:

- Nama akun.
- Institusi.
- Saldo.
- Status minimum balance.
- Hide/reveal behavior.

---

# 10. Quick Add

## 10.1 Desktop

Quick Add dapat tampil sebagai modal atau command menu.

```text
Quick Add

[Activity] [Expense] [Income]
[Transfer] [Note] [Scan]
```

## 10.2 Mobile

Bottom sheet:

```text
Quick Add

Activity
Expense
Income
Transfer
Note
Scan Transaction
```

## 10.3 Form Ringkas

Form hanya menampilkan field wajib.

Contoh pengeluaran:

```text
Account
Amount
Merchant / Purpose
Category
Date

[More details]
[Save]
```

---

# 11. Activity List

## 11.1 Desktop

```text
Page title         Search        Add Activity

Filter chips       Sort          View Toggle

┌───────────────────────────────────────────────────────────────┐
│ Activity row/card                                              │
├───────────────────────────────────────────────────────────────┤
│ Activity row/card                                              │
├───────────────────────────────────────────────────────────────┤
│ Activity row/card                                              │
└───────────────────────────────────────────────────────────────┘
```

## 11.2 Mobile

```text
Activities        Filter

Search

Filter chips horizontal scroll

Activity card
Activity card
Activity card

Floating Add button or center nav Add
```

## 11.3 Activity Card

Isi:

- Title.
- Category.
- Deadline.
- Priority badge.
- Status.
- Estimated duration.
- Quick complete button.
- Overflow menu.

## 11.4 Empty State

```text
Belum ada kegiatan.
Tambahkan kegiatan pertama agar Laras dapat membantu menentukan prioritas.

[Tambah Kegiatan]
```

---

# 12. Create and Edit Activity

## 12.1 Desktop

```text
┌──────────────────────────────┬───────────────────────────────┐
│ Main Form                    │ Priority Preview              │
│                              │                               │
│ Title                        │ Estimated score               │
│ Deadline                     │ Factors                       │
│ Importance                   │ Explanation preview           │
│ Duration                     │                               │
│ More details                 │                               │
└──────────────────────────────┴───────────────────────────────┘

[Cancel] [Save] [Save and Start]
```

## 12.2 Mobile

```text
Back + Page title

Title
Deadline
Importance
Duration

[More details]

Priority preview

Sticky footer:
[Save]
```

## 12.3 Progressive Details

- Description.
- Category.
- Impact.
- Start date.
- Scheduled time.
- Notes.

---

# 13. Activity Detail

## 13.1 Desktop

```text
┌────────────────────────────────────────┬──────────────────────┐
│ Activity Header                        │ Priority Panel       │
│ Title, status, category                │ Score                │
│                                        │ Level                │
│ Description                            │ Reasons              │
│ Schedule and deadline                  │                      │
│ Duration                               │                      │
│                                        │                      │
│ Timeline                               │ Related Suggestion   │
└────────────────────────────────────────┴──────────────────────┘

Actions:
Start | Complete | Postpone | Edit | More
```

## 13.2 Mobile

```text
Back + More

Title
Status + Priority

Main action button

Details

Priority explanation

Timeline

Related recommendation
```

---

# 14. Finance Overview

## 14.1 Desktop

```text
┌───────────────────────────────────────────────────────────────┐
│ Finance title                             Quick Actions        │
├───────────────────────┬───────────────────────────────────────┤
│ Total Balance         │ Cash Flow Summary                     │
├───────────────────────┴───────────────────────────────────────┤
│ Account Cards                                                  │
├──────────────────────────────┬────────────────────────────────┤
│ Expense by Category          │ Budget Status                  │
├──────────────────────────────┼────────────────────────────────┤
│ Recent Transactions          │ Financial Recommendation       │
└──────────────────────────────┴────────────────────────────────┘
```

## 14.2 Mobile

```text
Finance

Total Balance

Quick actions:
Expense | Income | Transfer | Scan

Account balances horizontal

Cash flow summary

Budget status

Recent transactions

Recommendation
```

---

# 15. Account List

## 15.1 Desktop

```text
Accounts                              Add Account

Total Balance

Account grid:
[BCA] [Mandiri] [BNI Pribadi]
[BNI Mahasiswa] [SeaBank] [Cash]

Inactive Accounts
```

## 15.2 Mobile

```text
Accounts       Add

Total Balance

Account card
Account card
Account card

Inactive section
```

## 15.3 Account Card

Isi:

- Icon.
- Account name.
- Institution.
- Current balance.
- Balance trend.
- Minimum balance warning.
- Active/inactive state.

---

# 16. Account Detail

## 16.1 Desktop

```text
┌────────────────────────────────────────────┬──────────────────┐
│ Account Header                             │ Account Actions  │
│ Name, institution, masked number           │ Expense          │
│ Current balance                            │ Income           │
│                                            │ Transfer         │
├────────────────────────────────────────────┼──────────────────┤
│ Balance Trend                              │ Account Insight  │
├────────────────────────────────────────────┴──────────────────┤
│ Transaction History                                           │
└───────────────────────────────────────────────────────────────┘
```

## 16.2 Mobile

```text
Back + More

Account identity
Current balance
Minimum threshold

Quick actions

Balance trend

Insight

Transactions
```

---

# 17. Create Expense

## 17.1 Desktop

```text
┌──────────────────────────────────────┬────────────────────────┐
│ Expense Form                         │ Transaction Summary    │
│                                      │                        │
│ Source Account                       │ Main amount            │
│ Main Amount                          │ Admin                  │
│ Purpose                              │ Tax                    │
│ Merchant                             │ Discount               │
│ Category                             │ Cashback               │
│ Necessity Level                      │ Total                  │
│ Date and Time                        │ Balance after          │
│ More details                         │                        │
└──────────────────────────────────────┴────────────────────────┘

[Cancel] [Save Draft] [Save Expense]
```

## 17.2 Mobile

```text
Back + Add Expense

Source Account
Amount
Purpose
Merchant
Category
Need Level
Date

[More details]

Summary
Total
Estimated balance after

Sticky action:
[Save Expense]
```

## 17.3 More Details

- Admin.
- Tax.
- Discount.
- Cashback.
- Payment method.
- Reference.
- Location.
- Notes.
- Attachment.

---

# 18. Create Income

Struktur serupa Create Expense.

Main fields:

- Destination account.
- Amount.
- Source.
- Sender.
- Category.
- Date and time.

Summary:

- Balance before.
- Incoming amount.
- Balance after.

---

# 19. Create Transfer

## 19.1 Desktop

```text
┌──────────────────────────────────────┬────────────────────────┐
│ Transfer Form                        │ Transfer Summary       │
│                                      │                        │
│ Source Account                       │ Source deduction       │
│ Destination Account                  │ Destination addition   │
│ Amount                               │ Admin                  │
│ Admin Fee                            │ Wealth change          │
│ Date and Time                        │ Final balances         │
│ Reference                            │                        │
└──────────────────────────────────────┴────────────────────────┘

[Cancel] [Save Transfer]
```

## 19.2 Mobile

```text
Source Account
Destination Account
Amount
Admin
Date

Summary

[Save Transfer]
```

---

# 20. Transaction History

## 20.1 Desktop

```text
Transactions                     Search       Filter

Tabs:
All | Expense | Income | Transfer | Adjustment | Draft

Date group
Transaction row
Transaction row

Date group
Transaction row
```

## 20.2 Mobile

```text
Transactions       Filter

Search

Tabs horizontal scroll

Today
Transaction card
Transaction card

Yesterday
Transaction card
```

## 20.3 Transaction Row/Card

Isi:

- Icon/type.
- Merchant, sender, or destination.
- Account.
- Category.
- Date/time.
- Amount.
- Admin indicator.
- Attachment indicator.
- Status.

---

# 21. Transaction Detail

## 21.1 Desktop

```text
┌──────────────────────────────────────┬────────────────────────┐
│ Transaction Main Detail              │ Amount Summary         │
│ Type, status                         │ Main amount            │
│ Merchant / sender / recipient        │ Admin                  │
│ Account source and destination       │ Tax                    │
│ Category and method                  │ Discount               │
│ Date, reference, note                │ Cashback               │
│                                      │ Total                  │
├──────────────────────────────────────┼────────────────────────┤
│ Attachments                          │ Balance Impact         │
├──────────────────────────────────────┴────────────────────────┤
│ Audit Timeline                                                │
└───────────────────────────────────────────────────────────────┘
```

## 21.2 Mobile

```text
Back + More

Transaction type + status

Total amount

Main details

Fee breakdown

Balance before / after

Attachments

Audit timeline
```

---

# 22. Balance Adjustment

## 22.1 Layout

```text
Current system balance

Actual balance input

Difference preview

Adjustment type

Mandatory reason

Effective date

Impact summary

[Cancel] [Confirm Adjustment]
```

Konfirmasi tambahan wajib sebelum menyimpan.

---

# 23. Budgets

## 23.1 Budget List

```text
Budgets                         Add Budget

Active budget card
Category
Progress
Used / Limit
Remaining
Status

Expired budget section
```

## 23.2 Budget Detail

```text
Budget summary
Progress
Daily/weekly pace
Related transactions
Recommendation
```

---

# 24. Scan Transaction

## 24.1 Scan Landing

```text
Scan Transaction

[Open Camera]
[Upload File]

Supported:
Receipt
Transfer proof
Payment proof
Invoice

Tips
Recent drafts
```

## 24.2 Processing

```text
Document preview

Uploading...
Reading document...
Extracting fields...
Preparing draft...

[Cancel]
```

## 24.3 Review Result Desktop

```text
┌──────────────────────────────┬───────────────────────────────┐
│ Original Document           │ Extracted Form                │
│                              │                               │
│ Zoom                         │ Type                          │
│ Rotate                       │ Account                       │
│ Crop                         │ Merchant                      │
│                              │ Date                          │
│                              │ Amount                        │
│                              │ Admin / Tax                   │
│                              │ Total                         │
│                              │ Confidence indicators         │
└──────────────────────────────┴───────────────────────────────┘

[Reject] [Save Draft] [Confirm Transaction]
```

## 24.4 Review Result Mobile

```text
Document preview

Extracted fields

Confidence warnings

Summary

[Save Draft]
[Confirm]
```

---

# 25. Recommendations

## 25.1 Recommendation List

```text
Recommendations

Tabs:
All | Activities | Finance

Main recommendation

Recommendation card
Recommendation card
Recommendation card
```

## 25.2 Recommendation Card

Isi:

- Type.
- Priority.
- Title.
- Message.
- Reason.
- Confidence.
- Related data.
- Created time.
- Primary action.
- Feedback menu.

## 25.3 Recommendation Detail

```text
Title
Priority
Message

Why this appears
Supporting data
Confidence

Suggested action

[Follow]
[Schedule]
[Postpone]
[Ignore]
[Not relevant]
```

---

# 26. Insights

## 26.1 Insights Overview

```text
Insights                     Period Selector

Key summary cards

Main pattern

Productivity preview

Financial preview

Weekly review
```

## 26.2 Productivity

```text
Completion Rate
Completed vs Postponed

Productive Day
Productive Time

Category Performance

Estimated vs Actual Duration

Main insight
```

## 26.3 Financial

```text
Income
Expense
Net Flow

Expense by Category
Expense by Account
Admin and Tax
Budget Performance
Period Comparison
```

---

# 27. Notes

## 27.1 Note List

```text
Notes                          Add Note

Search
Category filter

Pinned notes

Other notes
```

## 27.2 Note Editor

```text
Title
Content
Category
Pin
Color / label

[Save]
```

---

# 28. Settings

## 28.1 Desktop

```text
┌─────────────────────────────┬───────────────────────────────┐
│ Settings navigation         │ Settings content              │
│                             │                               │
│ Profile                     │ Selected form                 │
│ Appearance                  │                               │
│ Activities                  │                               │
│ Finance                     │                               │
│ Security                    │                               │
│ Categories                  │                               │
│ Data                        │                               │
│ About                       │                               │
└─────────────────────────────┴───────────────────────────────┘
```

## 28.2 Mobile

Settings list opens individual pages.

---

# 29. Global Search

## 29.1 Desktop

Command palette:

```text
Search Laras...

Recent searches

Activities
Transactions
Accounts
Notes
Recommendations
```

## 29.2 Mobile

Full-screen search page.

---

# 30. Notification Center

```text
Notifications

Unread
- Deadline alert
- Low balance
- Budget warning
- Scan review reminder

Earlier
- Recommendation
- Completed action
```

---

# 31. Loading State

## 31.1 Dashboard

Gunakan skeleton untuk:

- Greeting.
- Daily Focus.
- Account balance.
- Activity list.
- Recommendation.
- Transactions.

## 31.2 Form Save

- Tombol disabled.
- Spinner di dalam tombol.
- Teks berubah menjadi `Menyimpan...`.
- Cegah double submit.

## 31.3 Scan

Gunakan progress step, bukan spinner tanpa informasi.

---

# 32. Empty State

## 32.1 Activity

```text
Belum ada kegiatan.
Tambahkan kegiatan pertama agar prioritasmu dapat dihitung.

[Tambah Kegiatan]
```

## 32.2 Transaction

```text
Belum ada transaksi.
Mulai catat pengeluaran atau pemasukan.

[Tambah Transaksi]
```

## 32.3 Account

```text
Belum ada akun aktif.
Tambahkan rekening, akun digital, atau cash.

[Tambah Akun]
```

## 32.4 Recommendation

```text
Belum ada rekomendasi.
Laras membutuhkan lebih banyak data penggunaan.
```

## 32.5 Insight

```text
Insight belum tersedia.
Gunakan Laras secara rutin agar pola mulai terbentuk.
```

---

# 33. Error State

## 33.1 Validation Error

- Field border.
- Error text.
- Focus ke field pertama.
- Data lain tetap tersedia.

## 33.2 General Error

```text
Terjadi kesalahan.
Data belum disimpan.

[Coba Lagi]
[Simpan Draft]
```

## 33.3 Offline

```text
Koneksi terputus.
Beberapa fitur tidak tersedia.

[Coba Sambungkan Kembali]
```

---

# 34. Success State

## 34.1 Toast

Digunakan untuk:

- Data berhasil disimpan.
- Kegiatan selesai.
- Transfer berhasil.
- Lampiran berhasil diunggah.
- Rekomendasi ditindaklanjuti.

## 34.2 Inline Success

Digunakan ketika tindakan memerlukan konfirmasi visual kuat.

Contoh:

```text
Transfer berhasil

BCA          -Rp502.500
SeaBank      +Rp500.000
Admin        Rp2.500

[Lihat Detail]
```

---

# 35. Confirmation Dialog

Digunakan untuk:

- Delete.
- Cancel transaction.
- Balance adjustment.
- Account deactivation.
- Logout.
- Scan confirmation.

Struktur:

```text
Judul tindakan

Penjelasan dampak

Data yang terpengaruh

[Cancel] [Confirm]
```

---

# 36. Component Behavior

## 36.1 Cards

- Tidak semua card memiliki ukuran sama.
- Gunakan hierarki.
- Card utama lebih besar.
- Hover hanya pada desktop.
- Press state pada mobile.

## 36.2 Tables

- Desktop dapat menggunakan table.
- Mobile berubah menjadi stacked card.
- Kolom penting tidak boleh tersembunyi.
- Advanced data dapat masuk detail page.

## 36.3 Charts

- Grafik sederhana.
- Tooltip jelas.
- Legend tidak berlebihan.
- Empty state tersedia.
- Tidak menggunakan terlalu banyak warna.

---

# 37. Responsive Rules

## 37.1 Sidebar

```text
>= 1280 px : Expanded
1024–1279 : Collapsed
768–1023  : Drawer
< 768     : Hidden, use bottom navigation
```

## 37.2 Grid

```text
Desktop : 2–4 columns
Tablet  : 2 columns
Mobile  : 1 column
```

## 37.3 Form

Desktop:

```text
Form + Summary Panel
```

Mobile:

```text
Single column + Sticky Action
```

## 37.4 Table

Desktop: table.

Mobile: card list.

---

# 38. Accessibility Wireframe Rules

- Semua field memiliki label.
- Icon button memiliki accessible name.
- Focus order mengikuti visual order.
- Primary action dapat dijangkau keyboard.
- Error tidak hanya menggunakan warna.
- Badge memiliki teks.
- Animasi dapat dikurangi.
- Touch target minimum nyaman.
- Dialog mengunci fokus.
- Screen reader mendapatkan status loading dan success.

---

# 39. Wireframe Priority

## Prioritas A — Harus Digambar Terlebih Dahulu

1. Login.
2. Onboarding account selection.
3. Initial balance.
4. Dashboard.
5. Account list.
6. Account detail.
7. Create expense.
8. Create income.
9. Create transfer.
10. Transaction history.
11. Transaction detail.
12. Activity list.
13. Create activity.
14. Activity detail.
15. Quick Add.

## Prioritas B

1. Finance overview.
2. Recommendations.
3. Insights.
4. Budgets.
5. Settings.
6. Notes.

## Prioritas C

1. Scan transaction.
2. Opening animation.
3. Notification center.
4. Global search.
5. Weekly review.

---

# 40. Figma Frame Recommendation

Gunakan ukuran frame berikut:

```text
Mobile Small     : 360 × 800
Mobile Standard  : 390 × 844
Tablet           : 768 × 1024
Laptop           : 1280 × 800
Desktop          : 1440 × 1024
```

Buat page Figma:

```text
00 Foundations
01 Authentication
02 Onboarding
03 Dashboard
04 Activities
05 Finance
06 Recommendations
07 Insights
08 Notes
09 Settings
10 Components
11 States
12 Prototype
```

---

# 41. Prototype Flow yang Harus Dibuat

## Prototype 1 — First-time User

```text
Opening
→ Login
→ Welcome
→ Profile Setup
→ Account Selection
→ Initial Balance
→ Review
→ Dashboard
```

## Prototype 2 — Expense

```text
Dashboard
→ Quick Add
→ Expense
→ Fill Form
→ Review
→ Save
→ Success
→ Transaction Detail
```

## Prototype 3 — Transfer

```text
Finance
→ Transfer
→ Select Accounts
→ Enter Amount
→ Review
→ Confirm
→ Account Detail
```

## Prototype 4 — Activity

```text
Dashboard
→ Add Activity
→ Save
→ Activity Detail
→ Start
→ Complete
```

## Prototype 5 — Scan

```text
Finance
→ Scan
→ Upload
→ Processing
→ Review
→ Confirm
→ Transaction Detail
```

---

# 42. Definition of Done Wireframe

Wireframe dianggap selesai apabila:

- Seluruh halaman prioritas A sudah digambar.
- Mobile dan desktop tersedia.
- Alur first-time user dapat diprototipe.
- Alur expense dapat diprototipe.
- Alur transfer dapat diprototipe.
- Alur activity dapat diprototipe.
- Quick Add tersedia.
- Loading, empty, error, dan success state tersedia.
- Form memiliki ringkasan dampak.
- Navigasi desktop dan mobile konsisten.
- Tidak ada halaman tanpa tujuan jelas.
- Data keuangan mudah diverifikasi.
- Prioritas informasi sudah terlihat.
- Wireframe dapat diterjemahkan ke Blade dan Tailwind.

---

# 43. Keputusan Final Wireframe

Keputusan final:

- Mobile-first.
- Desktop menggunakan sidebar.
- Mobile menggunakan bottom navigation.
- Quick Add tersedia secara global.
- Dashboard menampilkan informasi tindakan, bukan seluruh data.
- Form keuangan memiliki summary panel.
- Saldo sebelum dan sesudah ditampilkan pada transaksi penting.
- Scan menggunakan split view di desktop.
- Recommendation card tidak menutupi dashboard.
- Tables berubah menjadi card pada mobile.
- Sticky action digunakan pada mobile form.
- Progressive disclosure digunakan untuk detail tambahan.
- Opening animation dibuat setelah fungsi inti stabil.
- Semua halaman utama memiliki state lengkap.

---

# 44. Tahap Berikutnya

Setelah spesifikasi wireframe disetujui, tahap berikutnya adalah membuat wireframe visual di Figma.

Setelah wireframe visual selesai, lanjutkan dengan:

```text
docs/06-database-design.md
docs/07-erd-specification.md
```

Dokumen database akan menetapkan:

- Daftar tabel.
- Kolom.
- Tipe data.
- Relasi.
- Foreign key.
- Index.
- Enum.
- Soft delete.
- Audit trail.
- Aturan saldo.
- Aturan transaksi.
