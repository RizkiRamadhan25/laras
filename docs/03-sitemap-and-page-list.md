# Sitemap and Page List — Laras

## 1. Informasi Dokumen

- **Nama proyek:** Laras
- **Tagline:** Selaraskan hari, tentukan langkah.
- **Jenis dokumen:** Sitemap and Page List
- **Versi:** 1.0
- **Tanggal:** 31 Juli 2026
- **Dokumen terkait:**
  - `01-project-scope.md`
  - `02-user-flow.md`

---

## 2. Tujuan Dokumen

Dokumen ini menetapkan:

- Struktur informasi Laras.
- Hubungan antarhalaman.
- Navigasi utama desktop dan mobile.
- Daftar halaman MVP.
- Komponen utama setiap halaman.
- Route awal aplikasi.
- Halaman publik dan privat.
- Modal, drawer, bottom sheet, serta halaman detail.
- Prioritas implementasi.

Dokumen ini menjadi dasar untuk pembuatan:

- Wireframe.
- Design system.
- Database.
- Route Laravel.
- Controller.
- Blade views.
- Navigation component.
- Responsive layout.

---

# 3. Arsitektur Informasi Utama

```text
Laras
├── Public
│   ├── Opening
│   ├── Login
│   ├── Forgot Password
│   └── Reset Password
│
├── Onboarding
│   ├── Welcome
│   ├── Profile Setup
│   ├── Preferences
│   ├── Account Selection
│   ├── Initial Balance
│   └── Setup Review
│
├── Dashboard
│   ├── Daily Focus
│   ├── Priority Activities
│   ├── Financial Summary
│   ├── Account Balances
│   ├── Recommendations
│   └── Recent Activity
│
├── Activities
│   ├── Activity List
│   ├── Create Activity
│   ├── Activity Detail
│   ├── Edit Activity
│   ├── Activity History
│   └── Activity Categories
│
├── Finance
│   ├── Finance Overview
│   ├── Accounts
│   │   ├── Account List
│   │   ├── Create Account
│   │   ├── Account Detail
│   │   ├── Edit Account
│   │   └── Balance Adjustment
│   │
│   ├── Transactions
│   │   ├── Transaction History
│   │   ├── Transaction Detail
│   │   ├── Create Expense
│   │   ├── Create Income
│   │   ├── Create Transfer
│   │   ├── Edit Transaction
│   │   └── Transaction Attachment
│   │
│   ├── Budgets
│   │   ├── Budget List
│   │   ├── Create Budget
│   │   ├── Budget Detail
│   │   └── Edit Budget
│   │
│   └── Scan Transaction
│       ├── Upload
│       ├── Processing
│       ├── Review Result
│       ├── Draft List
│       └── Scan Detail
│
├── Recommendations
│   ├── Active Recommendations
│   ├── Recommendation Detail
│   └── Recommendation History
│
├── Insights
│   ├── Insights Overview
│   ├── Productivity Insights
│   ├── Financial Insights
│   ├── Account Insights
│   └── Weekly Review
│
├── Notes
│   ├── Note List
│   ├── Create Note
│   ├── Note Detail
│   └── Edit Note
│
├── Search
│   └── Global Search Results
│
├── Notifications
│   └── Notification Center
│
├── Settings
│   ├── Profile
│   ├── Appearance
│   ├── Activity Preferences
│   ├── Financial Preferences
│   ├── Security
│   ├── Categories
│   ├── Backup and Data
│   └── About Laras
│
└── System
    ├── Error 403
    ├── Error 404
    ├── Error 419
    ├── Error 429
    ├── Error 500
    ├── Maintenance
    └── Offline
```

---

# 4. Pembagian Halaman Berdasarkan Akses

## 4.1 Halaman Publik

Halaman yang dapat diakses tanpa login:

- Opening.
- Login.
- Forgot Password.
- Reset Password.
- Error tertentu.

## 4.2 Halaman Onboarding

Halaman hanya untuk pengguna yang sudah login tetapi belum menyelesaikan setup awal:

- Welcome.
- Profile Setup.
- Preferences.
- Account Selection.
- Initial Balance.
- Setup Review.

## 4.3 Halaman Privat

Seluruh halaman berikut membutuhkan autentikasi:

- Dashboard.
- Activities.
- Finance.
- Accounts.
- Transactions.
- Budgets.
- Scan Transaction.
- Recommendations.
- Insights.
- Notes.
- Search.
- Notifications.
- Settings.

---

# 5. Navigasi Desktop

## 5.1 Sidebar Utama

```text
Dashboard
Activities
Finance
Recommendations
Insights
Notes
```

## 5.2 Submenu Finance

```text
Overview
Accounts
Transactions
Budgets
Scan Transaction
```

## 5.3 Area Bawah Sidebar

```text
Settings
Help / About
Profile
Logout
```

## 5.4 Header Desktop

Header memuat:

- Page title.
- Breadcrumb opsional.
- Global search.
- Quick Add.
- Notification button.
- Theme switcher.
- Profile menu.

## 5.5 Perilaku Sidebar

- Expanded pada desktop besar.
- Collapsed icon-only pada laptop kecil.
- Dapat disembunyikan pada tablet.
- Active indicator bergerak lembut.
- Submenu hanya terbuka ketika diperlukan.
- State sidebar disimpan dalam preferensi lokal.

---

# 6. Navigasi Mobile

## 6.1 Bottom Navigation

```text
Home | Activities | Add | Finance | Insights
```

## 6.2 Tombol Tengah

Tombol `Add` membuka Quick Add bottom sheet.

Pilihan:

- Activity.
- Expense.
- Income.
- Transfer.
- Note.
- Scan Transaction.

## 6.3 Menu Tambahan

Menu berikut tersedia dari avatar atau tombol More:

- Recommendations.
- Notes.
- Notifications.
- Settings.
- Search.
- Logout.

## 6.4 Mobile Header

Mobile header memuat:

- Page title.
- Back button pada halaman detail.
- Search atau filter sesuai konteks.
- Notification icon.
- Overflow menu.

---

# 7. Struktur Route Awal

Route menggunakan nama berbahasa Inggris untuk konsistensi teknis.

## 7.1 Public Routes

```text
GET    /                         opening
GET    /login                    auth.login
POST   /login                    auth.login.store
POST   /logout                   auth.logout
GET    /forgot-password          password.request
POST   /forgot-password          password.email
GET    /reset-password/{token}   password.reset
POST   /reset-password           password.update
```

## 7.2 Onboarding Routes

```text
GET    /onboarding                       onboarding.index
GET    /onboarding/profile               onboarding.profile
POST   /onboarding/profile               onboarding.profile.store
GET    /onboarding/preferences           onboarding.preferences
POST   /onboarding/preferences           onboarding.preferences.store
GET    /onboarding/accounts              onboarding.accounts
POST   /onboarding/accounts              onboarding.accounts.store
GET    /onboarding/initial-balances      onboarding.balances
POST   /onboarding/initial-balances      onboarding.balances.store
GET    /onboarding/review                onboarding.review
POST   /onboarding/complete              onboarding.complete
```

## 7.3 Dashboard Route

```text
GET    /dashboard                dashboard.index
```

## 7.4 Activity Routes

```text
GET    /activities                       activities.index
GET    /activities/create                activities.create
POST   /activities                       activities.store
GET    /activities/{activity}            activities.show
GET    /activities/{activity}/edit       activities.edit
PUT    /activities/{activity}            activities.update
DELETE /activities/{activity}            activities.destroy

POST   /activities/{activity}/start      activities.start
POST   /activities/{activity}/complete   activities.complete
POST   /activities/{activity}/postpone   activities.postpone
POST   /activities/{activity}/cancel     activities.cancel
GET    /activities/{activity}/history    activities.history
```

## 7.5 Finance Overview Route

```text
GET    /finance                  finance.index
```

## 7.6 Account Routes

```text
GET    /finance/accounts                         accounts.index
GET    /finance/accounts/create                  accounts.create
POST   /finance/accounts                         accounts.store
GET    /finance/accounts/{account}               accounts.show
GET    /finance/accounts/{account}/edit          accounts.edit
PUT    /finance/accounts/{account}               accounts.update
POST   /finance/accounts/{account}/deactivate    accounts.deactivate
POST   /finance/accounts/{account}/activate      accounts.activate

GET    /finance/accounts/{account}/adjustment    adjustments.create
POST   /finance/accounts/{account}/adjustment    adjustments.store
```

## 7.7 Transaction Routes

```text
GET    /finance/transactions                     transactions.index
GET    /finance/transactions/{transaction}       transactions.show
GET    /finance/transactions/{transaction}/edit  transactions.edit
PUT    /finance/transactions/{transaction}       transactions.update
DELETE /finance/transactions/{transaction}       transactions.destroy

GET    /finance/expenses/create                  expenses.create
POST   /finance/expenses                         expenses.store

GET    /finance/income/create                    income.create
POST   /finance/income                           income.store

GET    /finance/transfers/create                 transfers.create
POST   /finance/transfers                        transfers.store

POST   /finance/transactions/{transaction}/refund transactions.refund
POST   /finance/transactions/{transaction}/cancel transactions.cancel
```

## 7.8 Attachment Routes

```text
POST   /finance/transactions/{transaction}/attachments
DELETE /finance/transactions/{transaction}/attachments/{attachment}
GET    /finance/attachments/{attachment}
```

## 7.9 Budget Routes

```text
GET    /finance/budgets                  budgets.index
GET    /finance/budgets/create           budgets.create
POST   /finance/budgets                  budgets.store
GET    /finance/budgets/{budget}         budgets.show
GET    /finance/budgets/{budget}/edit    budgets.edit
PUT    /finance/budgets/{budget}         budgets.update
DELETE /finance/budgets/{budget}         budgets.destroy
```

## 7.10 Scan Routes

```text
GET    /finance/scan                     scans.index
POST   /finance/scan                     scans.store
GET    /finance/scan/{scan}              scans.show
GET    /finance/scan/{scan}/review       scans.review
PUT    /finance/scan/{scan}              scans.update
POST   /finance/scan/{scan}/confirm      scans.confirm
POST   /finance/scan/{scan}/reject       scans.reject
DELETE /finance/scan/{scan}              scans.destroy
```

## 7.11 Recommendation Routes

```text
GET    /recommendations                          recommendations.index
GET    /recommendations/history                  recommendations.history
GET    /recommendations/{recommendation}         recommendations.show
POST   /recommendations/{recommendation}/follow  recommendations.follow
POST   /recommendations/{recommendation}/schedule recommendations.schedule
POST   /recommendations/{recommendation}/postpone recommendations.postpone
POST   /recommendations/{recommendation}/ignore  recommendations.ignore
POST   /recommendations/{recommendation}/irrelevant recommendations.irrelevant
```

## 7.12 Insight Routes

```text
GET    /insights                 insights.index
GET    /insights/productivity    insights.productivity
GET    /insights/financial       insights.financial
GET    /insights/accounts        insights.accounts
GET    /insights/weekly-review   insights.weekly
```

## 7.13 Note Routes

```text
GET    /notes                    notes.index
GET    /notes/create             notes.create
POST   /notes                    notes.store
GET    /notes/{note}             notes.show
GET    /notes/{note}/edit        notes.edit
PUT    /notes/{note}             notes.update
DELETE /notes/{note}             notes.destroy
POST   /notes/{note}/pin         notes.pin
POST   /notes/{note}/unpin       notes.unpin
```

## 7.14 Search and Notifications

```text
GET    /search                   search.index
GET    /notifications            notifications.index
POST   /notifications/{notification}/read
POST   /notifications/read-all
DELETE /notifications/{notification}
```

## 7.15 Settings Routes

```text
GET    /settings                         settings.index
GET    /settings/profile                 settings.profile
PUT    /settings/profile                 settings.profile.update

GET    /settings/appearance              settings.appearance
PUT    /settings/appearance              settings.appearance.update

GET    /settings/activities              settings.activities
PUT    /settings/activities              settings.activities.update

GET    /settings/finance                 settings.finance
PUT    /settings/finance                 settings.finance.update

GET    /settings/security                settings.security
PUT    /settings/security/password       settings.password.update

GET    /settings/categories              categories.index
POST   /settings/categories              categories.store
PUT    /settings/categories/{category}   categories.update
POST   /settings/categories/{category}/deactivate
POST   /settings/categories/{category}/activate

GET    /settings/data                    settings.data
POST   /settings/data/export             settings.data.export
POST   /settings/data/backup             settings.data.backup

GET    /settings/about                   settings.about
```

---

# 8. Daftar Halaman MVP Inti

## 8.1 Opening Page

### Tujuan

Menampilkan identitas Laras sebelum masuk ke login atau dashboard.

### Komponen

- Logo Laras.
- Tagline.
- Opening animation.
- Skip button.
- Reduced-motion fallback.

### Prioritas

Sedang. Dibuat setelah alur inti berjalan.

---

## 8.2 Login Page

### Tujuan

Mengautentikasi pengguna.

### Komponen

- Logo.
- Email input.
- Password input.
- Show/hide password.
- Remember me.
- Login button.
- Forgot password.
- Error message.
- Loading state.

### Mobile

Form satu kolom dan tombol penuh.

### Desktop

Layout dua bagian atau panel terpusat sederhana.

### Prioritas

Sangat tinggi.

---

## 8.3 Onboarding Welcome

### Tujuan

Memperkenalkan fungsi Laras dan memulai setup awal.

### Komponen

- Welcome message.
- Ringkasan manfaat.
- Progress indicator.
- Start button.
- Skip terbatas jika aman.

### Prioritas

Tinggi.

---

## 8.4 Profile Setup

### Tujuan

Mengisi profil dasar.

### Komponen

- Nama.
- Email read-only atau editable.
- Foto profil opsional.
- Zona waktu.
- Mata uang.
- Format tanggal.

### Prioritas

Tinggi.

---

## 8.5 Account Selection

### Tujuan

Memilih akun keuangan yang digunakan.

### Komponen

- BCA.
- Mandiri.
- BNI Pribadi.
- BNI Mahasiswa.
- SeaBank.
- Cash.
- Add custom account.
- Account type.
- Color and icon selection.

### Prioritas

Sangat tinggi.

---

## 8.6 Initial Balance Setup

### Tujuan

Mengisi saldo awal setiap akun.

### Komponen

- Account card.
- Initial balance input.
- Effective date.
- Minimum balance optional.
- Masked account number optional.
- Validation message.

### Prioritas

Sangat tinggi.

---

## 8.7 Setup Review

### Tujuan

Memeriksa seluruh data onboarding sebelum disimpan.

### Komponen

- Profile summary.
- Account summary.
- Initial balance summary.
- Edit links.
- Confirmation checkbox.
- Finish setup button.

### Prioritas

Tinggi.

---

# 9. Dashboard

## 9.1 Tujuan

Menjadi pusat kendali utama Laras.

## 9.2 Komponen Desktop

- Greeting and date.
- Daily Focus card.
- Priority activity list.
- Financial summary.
- Account balance strip or grid.
- Main recommendation.
- Budget warning.
- Recent transactions.
- Recent activity.
- Quick Add.
- Search.
- Notification.

## 9.3 Komponen Mobile

Urutan mobile:

1. Greeting.
2. Daily Focus.
3. Main recommendation.
4. Priority activities.
5. Financial summary.
6. Account balances horizontal scroll.
7. Recent transactions.
8. Recent activity.

## 9.4 Aksi Cepat

- Add activity.
- Add expense.
- Add income.
- Add transfer.
- Scan transaction.
- View all.

## 9.5 Empty State

- Belum ada kegiatan.
- Belum ada transaksi.
- Belum ada rekomendasi.

## 9.6 Prioritas

Sangat tinggi.

---

# 10. Activities

## 10.1 Activity List

### Komponen

- Page header.
- Search.
- Filter chips.
- Sort dropdown.
- View toggle.
- Activity cards or rows.
- Priority badge.
- Deadline.
- Status.
- Quick complete.
- Add activity button.
- Pagination or infinite scroll.

### Filter

- Today.
- Upcoming.
- Overdue.
- Completed.
- Postponed.
- Category.
- Priority.
- Date range.

### Prioritas

Sangat tinggi.

---

## 10.2 Create Activity

### Komponen Utama

- Title.
- Deadline.
- Importance.
- Estimated duration.

### Detail Tambahan

- Description.
- Category.
- Impact level.
- Start date.
- Scheduled time.
- Notes.

### Footer Action

- Save.
- Save and start.
- Cancel.

### Prioritas

Sangat tinggi.

---

## 10.3 Activity Detail

### Komponen

- Title.
- Status.
- Priority score.
- Priority explanation.
- Description.
- Schedule and deadline.
- Category.
- Duration.
- Postponed count.
- Activity timeline.
- Recommendation related to activity.

### Actions

- Start.
- Complete.
- Postpone.
- Reschedule.
- Edit.
- Cancel.
- Delete.

### Prioritas

Tinggi.

---

## 10.4 Edit Activity

Menggunakan struktur yang sama dengan Create Activity, tetapi menampilkan data lama dan dampak perubahan prioritas.

### Prioritas

Tinggi.

---

## 10.5 Activity History

### Komponen

- Status change timeline.
- Deadline revisions.
- Postponement history.
- Duration history.
- Audit data.

### Prioritas

Sedang.

---

# 11. Finance Overview

## 11.1 Tujuan

Menampilkan kondisi keuangan secara keseluruhan.

## 11.2 Komponen

- Total balance.
- Income this month.
- Expense this month.
- Net cash flow.
- Account balances.
- Budget status.
- Spending categories.
- Recent transactions.
- Total admin fees.
- Total tax.
- Quick financial actions.
- Financial recommendation.

## 11.3 Actions

- Add expense.
- Add income.
- Transfer.
- Scan.
- View accounts.
- View all transactions.

## 11.4 Prioritas

Sangat tinggi.

---

# 12. Accounts

## 12.1 Account List

### Komponen

- Total balance.
- Active account cards.
- Hidden balance toggle.
- Institution icon.
- Current balance.
- Minimum balance indicator.
- Recent activity.
- Add account button.
- Inactive account section.

### Prioritas

Sangat tinggi.

---

## 12.2 Create Account

### Fields

- Account name.
- Institution.
- Account type.
- Function label.
- Initial balance.
- Effective date.
- Currency.
- Color.
- Icon.
- Masked account number.
- Minimum balance.
- Include in total balance.
- Notes.

### Prioritas

Tinggi.

---

## 12.3 Account Detail

### Komponen

- Account identity.
- Current balance.
- Initial balance.
- Balance change.
- Income.
- Expense.
- Transfer activity.
- Transaction history.
- Account-specific insight.
- Minimum balance warning.

### Actions

- Add expense from this account.
- Add income to this account.
- Transfer.
- Adjust balance.
- Edit.
- Deactivate.

### Prioritas

Sangat tinggi.

---

## 12.4 Edit Account

Saldo tidak dapat diedit langsung.

### Editable Fields

- Name.
- Label.
- Icon.
- Color.
- Minimum balance.
- Masked number.
- Include in total.
- Notes.

### Prioritas

Tinggi.

---

## 12.5 Balance Adjustment

### Komponen

- Current system balance.
- Actual balance.
- Difference preview.
- Adjustment type.
- Mandatory reason.
- Effective date.
- Confirmation.

### Prioritas

Sangat tinggi.

---

# 13. Transactions

## 13.1 Transaction History

### Komponen

- Search.
- Advanced filter.
- Date grouping.
- Transaction rows.
- Account source.
- Merchant or sender.
- Category.
- Amount.
- Fee indicator.
- Attachment indicator.
- Scan indicator.
- Status.
- Running balance optional.

### Views

- All.
- Expense.
- Income.
- Transfer.
- Adjustment.
- Draft.
- Pending.

### Prioritas

Sangat tinggi.

---

## 13.2 Transaction Detail

### Komponen

- Transaction type.
- Status.
- Main amount.
- Admin.
- Tax.
- Discount.
- Cashback.
- Total.
- Source account.
- Destination account.
- Merchant.
- Recipient or sender.
- Category.
- Need level.
- Date and time.
- Reference.
- Note.
- Attachment gallery.
- Balance before and after.
- Audit timeline.

### Actions

- Edit.
- Add attachment.
- Refund.
- Cancel.
- Archive or delete.
- Duplicate.

### Prioritas

Sangat tinggi.

---

## 13.3 Create Expense

### Main Section

- Source account.
- Main amount.
- Purpose.
- Merchant or recipient.
- Category.
- Necessity level.
- Date and time.

### Additional Section

- Admin fee.
- Tax.
- Discount.
- Cashback.
- Payment method.
- Reference number.
- Location.
- Notes.
- Attachment.

### Summary Section

- Main amount.
- Additional fees.
- Deductions.
- Total expense.
- Balance before.
- Estimated balance after.

### Prioritas

Sangat tinggi.

---

## 13.4 Create Income

### Fields

- Destination account.
- Amount.
- Income source.
- Sender.
- Category.
- Date and time.
- Reference.
- Notes.
- Attachment.

### Summary

- Current balance.
- Incoming amount.
- Estimated balance after.

### Prioritas

Sangat tinggi.

---

## 13.5 Create Transfer

### Fields

- Source account.
- Destination account.
- Transfer amount.
- Admin fee.
- Date and time.
- Reference.
- Notes.
- Attachment.

### Summary

- Deduction from source.
- Addition to destination.
- Change in total wealth.
- Source balance after.
- Destination balance after.

### Prioritas

Sangat tinggi.

---

## 13.6 Edit Transaction

### Komponen

- Original transaction summary.
- Editable form.
- Impact on balances.
- Change comparison.
- Confirmation.

### Prioritas

Tinggi.

---

# 14. Budgets

## 14.1 Budget List

### Komponen

- Active budgets.
- Progress bar.
- Amount used.
- Remaining amount.
- Status.
- Period.
- Add budget.
- Expired budgets.

### Prioritas

Tinggi.

---

## 14.2 Create Budget

### Fields

- Category.
- Budget amount.
- Period.
- Start date.
- End date.
- Warning threshold.
- Notes.

### Prioritas

Tinggi.

---

## 14.3 Budget Detail

### Komponen

- Budget summary.
- Usage progress.
- Related transactions.
- Daily or weekly pace.
- Recommendation.
- Edit action.

### Prioritas

Sedang.

---

# 15. Scan Transaction

## 15.1 Scan Landing Page

### Komponen

- Camera button.
- Upload file button.
- Supported document types.
- Scan tips.
- Recent drafts.
- Privacy notice.

### Prioritas

MVP lanjutan.

---

## 15.2 Processing Page

### Komponen

- Uploaded image preview.
- Upload progress.
- OCR progress.
- Processing steps.
- Cancel action.
- Error state.

### Prioritas

MVP lanjutan.

---

## 15.3 Review Scan Result

### Komponen

- Original image.
- Cropped image preview.
- Extracted fields.
- Confidence score.
- Low-confidence highlight.
- Transaction type selector.
- Account selector.
- Amount summary.
- Save as draft.
- Confirm transaction.
- Reject result.

### Mobile

Image and form shown in stacked layout.

### Desktop

Split layout:

```text
Image Preview | Extracted Transaction Form
```

### Prioritas

MVP lanjutan.

---

## 15.4 Scan Draft List

### Komponen

- Draft preview.
- Upload date.
- Detected merchant.
- Detected total.
- Status.
- Continue review.
- Delete.

### Prioritas

MVP lanjutan.

---

# 16. Recommendations

## 16.1 Active Recommendations

### Komponen

- Main recommendation.
- Activity recommendations.
- Financial recommendations.
- Priority badge.
- Confidence.
- Reason.
- Created time.
- Expiration.
- Filter by type.

### Actions

- Follow.
- Schedule.
- Postpone.
- Ignore.
- Not relevant.

### Prioritas

Tinggi.

---

## 16.2 Recommendation Detail

### Komponen

- Recommendation message.
- Full reason.
- Supporting data.
- Confidence.
- Suggested action.
- Related activity or transaction.
- Feedback form.
- Recommendation timeline.

### Prioritas

Tinggi.

---

## 16.3 Recommendation History

### Komponen

- Filter by response.
- Followed.
- Scheduled.
- Ignored.
- Not relevant.
- Expired.
- Result of recommendation.

### Prioritas

Sedang.

---

# 17. Insights

## 17.1 Insights Overview

### Komponen

- Period selector.
- Productivity summary.
- Financial summary.
- Key patterns.
- Weekly highlights.
- Main recommendation.

### Prioritas

Tinggi.

---

## 17.2 Productivity Insights

### Komponen

- Completion rate.
- Completed activities.
- Postponed activities.
- Productive day.
- Productive time.
- Category performance.
- Estimated vs actual duration.
- Overdue pattern.
- Charts.

### Prioritas

Sedang.

---

## 17.3 Financial Insights

### Komponen

- Income.
- Expense.
- Net flow.
- Expense by category.
- Expense by account.
- Expense by merchant.
- Admin and tax.
- Discount and cashback.
- Budget comparison.
- Period comparison.

### Prioritas

Tinggi.

---

## 17.4 Account Insights

### Komponen

- Balance distribution.
- Account usage.
- Transfer pattern.
- Minimum balance alerts.
- Account-specific expenses.
- Inactive accounts.
- Suggested account review.

### Prioritas

Sedang.

---

## 17.5 Weekly Review

### Komponen

- Week summary.
- Activities completed.
- Main accomplishment.
- Main delay.
- Total expense.
- Budget result.
- Recommendation followed.
- Suggested focus for next week.

### Prioritas

Sedang.

---

# 18. Notes

## 18.1 Note List

### Komponen

- Search.
- Pinned notes.
- Category filter.
- Note cards.
- Add note.
- Empty state.

### Prioritas

Sedang.

---

## 18.2 Create and Edit Note

### Fields

- Title optional.
- Content.
- Category.
- Pin status.
- Color or label.

### Prioritas

Sedang.

---

## 18.3 Note Detail

### Components

- Title.
- Content.
- Category.
- Created time.
- Updated time.
- Edit.
- Delete.
- Pin or unpin.

### Prioritas

Sedang.

---

# 19. Search

## 19.1 Global Search Results

### Search Targets

- Activities.
- Transactions.
- Accounts.
- Merchants.
- Categories.
- Notes.
- Recommendations.

### Komponen

- Search input.
- Recent searches.
- Grouped results.
- Filter by type.
- Highlighted keyword.
- Empty state.

### Prioritas

Sedang.

---

# 20. Notification Center

## 20.1 Komponen

- Unread count.
- Deadline alerts.
- Budget alerts.
- Low balance alerts.
- Scan reminders.
- Recommendation alerts.
- Mark all as read.
- Delete notification.

### Prioritas

Sedang.

---

# 21. Settings

## 21.1 Settings Overview

### Komponen

- Profile.
- Appearance.
- Activity preferences.
- Financial preferences.
- Security.
- Categories.
- Backup and data.
- About.

### Prioritas

Tinggi.

---

## 21.2 Profile Settings

### Fields

- Name.
- Email.
- Profile image.
- Timezone.
- Currency.
- Date format.
- Language future-ready.

### Prioritas

Tinggi.

---

## 21.3 Appearance Settings

### Options

- Light.
- Dark.
- System.
- Reduced motion.
- Hide balances.
- Replay opening animation.
- Sidebar behavior.

### Prioritas

Tinggi.

---

## 21.4 Activity Preferences

### Options

- Default category.
- Default duration.
- Default importance.
- Productive hours.
- Deadline reminder.
- Priority preferences.

### Prioritas

Sedang.

---

## 21.5 Financial Preferences

### Options

- Primary account.
- Default expense account.
- Default income account.
- Currency.
- Amount format.
- Default payment method.
- Budget period.
- Low balance threshold.
- Negative balance policy.

### Prioritas

Tinggi.

---

## 21.6 Security

### Options

- Change password.
- Active session.
- Login history.
- PIN future-ready.
- Backup reminder.

### Prioritas

Tinggi.

---

## 21.7 Category Management

### Tabs

- Activity.
- Expense.
- Income.
- Note.

### Actions

- Add.
- Edit.
- Activate.
- Deactivate.
- Set icon.
- Set color.
- Set default budget.

### Prioritas

Tinggi.

---

## 21.8 Backup and Data

### Actions

- Export data.
- Create backup.
- View backup history.
- Restore future-ready.
- Delete local drafts.
- Data retention information.

### Prioritas

Sedang.

---

## 21.9 About Laras

### Komponen

- Logo.
- Version.
- Tagline.
- Project description.
- Changelog future-ready.
- Licenses.
- Privacy statement.

### Prioritas

Rendah.

---

# 22. Quick Add Components

Quick Add bukan halaman penuh utama, tetapi komponen global.

## 22.1 Desktop

Tampilan:

- Command menu.
- Modal.
- Side panel.

## 22.2 Mobile

Tampilan:

- Bottom sheet.
- Large action buttons.
- Short forms.

## 22.3 Pilihan

```text
Add Activity
Add Expense
Add Income
Add Transfer
Add Note
Scan Transaction
Adjust Balance
```

## 22.4 Keyboard Shortcut

Contoh:

```text
Ctrl / Cmd + K  : Global Search
Ctrl / Cmd + J  : Quick Add
A               : Add Activity
E               : Add Expense
I               : Add Income
T               : Add Transfer
```

Shortcut tidak aktif ketika pengguna sedang mengetik di input.

---

# 23. Modal dan Bottom Sheet

Komponen yang menggunakan modal atau bottom sheet:

- Quick Add.
- Complete activity.
- Postpone activity.
- Cancel activity.
- Delete confirmation.
- Logout confirmation.
- Filter transaction.
- Filter activity.
- Add attachment.
- Preview attachment.
- Recommendation feedback.
- Account deactivation.
- Balance adjustment confirmation.
- Transaction impact confirmation.

Modal desktop dapat berubah menjadi bottom sheet pada mobile.

---

# 24. Breadcrumb

Breadcrumb digunakan pada desktop untuk halaman bertingkat.

Contoh:

```text
Finance > Accounts > BNI Mahasiswa
Finance > Transactions > Expense Detail
Activities > Activity Detail
Settings > Categories
```

Breadcrumb tidak wajib pada halaman mobile. Gunakan back button dan page title.

---

# 25. Layout Template

## 25.1 Public Layout

Digunakan untuk:

- Opening.
- Login.
- Forgot password.
- Reset password.

## 25.2 Onboarding Layout

Digunakan untuk:

- Seluruh setup awal.
- Progress step.
- Fokus pada satu tugas per layar.

## 25.3 App Layout

Digunakan untuk:

- Dashboard.
- Activities.
- Finance.
- Recommendations.
- Insights.
- Notes.
- Settings.

Struktur:

```text
App Shell
├── Sidebar / Mobile Header
├── Main Header
├── Main Content
├── Quick Add
├── Notification Layer
└── Modal Layer
```

## 25.4 Focus Layout

Digunakan untuk:

- Scan review.
- Form transaksi panjang.
- Weekly review.
- Data export.

---

# 26. Komponen Global

Komponen reusable:

- App logo.
- Sidebar.
- Mobile bottom navigation.
- Header.
- Breadcrumb.
- Search bar.
- Quick Add button.
- Modal.
- Bottom sheet.
- Drawer.
- Button.
- Icon button.
- Input.
- Currency input.
- Date picker.
- Time picker.
- Select.
- Combobox.
- Checkbox.
- Radio.
- Switch.
- Textarea.
- Form error.
- Card.
- Stat card.
- Account card.
- Activity card.
- Transaction row.
- Recommendation card.
- Insight card.
- Badge.
- Priority badge.
- Status badge.
- Progress bar.
- Chart wrapper.
- Empty state.
- Loading skeleton.
- Error state.
- Toast.
- Confirmation dialog.
- File uploader.
- Image preview.
- Confidence indicator.
- Currency formatter.
- Pagination.
- Filter chip.
- Tabs.
- Timeline.
- Audit log item.

---

# 27. Responsive Breakpoints

Breakpoints awal:

```text
Mobile Small     : 360px
Mobile Standard  : 390px
Tablet           : 768px
Laptop           : 1024px
Desktop          : 1280px
Wide Desktop     : 1440px+
```

## 27.1 Mobile

- Satu kolom.
- Bottom navigation.
- Bottom sheet.
- Horizontal account cards.
- Sticky action footer pada form.
- Filter berupa drawer atau sheet.

## 27.2 Tablet

- Sidebar drawer atau collapsed.
- Grid dua kolom.
- Detail dapat menggunakan split view terbatas.

## 27.3 Desktop

- Sidebar tetap.
- Grid fleksibel.
- Detail dan summary dapat berdampingan.
- Hover state aktif.
- Keyboard shortcut tersedia.

---

# 28. Prioritas Halaman Berdasarkan Fase

## Fase 1 — Fondasi

1. Login.
2. Onboarding.
3. Account selection.
4. Initial balance.
5. App layout.
6. Dashboard shell.
7. Settings dasar.

## Fase 2 — Keuangan Inti

1. Account list.
2. Account detail.
3. Create expense.
4. Create income.
5. Create transfer.
6. Transaction history.
7. Transaction detail.
8. Balance adjustment.
9. Finance overview.

## Fase 3 — Kegiatan

1. Activity list.
2. Create activity.
3. Activity detail.
4. Edit activity.
5. Start, complete, postpone, cancel.
6. Priority display.

## Fase 4 — Rekomendasi dan Insight

1. Active recommendations.
2. Recommendation detail.
3. Feedback.
4. Insights overview.
5. Financial insights.
6. Productivity insights.

## Fase 5 — Pendukung

1. Budgets.
2. Notes.
3. Search.
4. Notifications.
5. Category management.
6. Backup.

## Fase 6 — Scan

1. Scan landing.
2. Upload.
3. Processing.
4. Review result.
5. Draft scan list.
6. Confirmation.

## Fase 7 — Penyempurnaan

1. Opening animation.
2. Advanced transitions.
3. Weekly review.
4. Account insights.
5. Accessibility audit.
6. Performance optimization.

---

# 29. Halaman yang Tidak Dibuat sebagai Halaman Terpisah

Beberapa fitur lebih baik dibuat sebagai action atau modal:

- Complete activity.
- Postpone activity.
- Cancel activity.
- Logout.
- Delete confirmation.
- Recommendation feedback singkat.
- Add attachment.
- Preview image.
- Filter sederhana.
- Theme switcher.
- Balance visibility toggle.

Hal ini menjaga navigasi tetap ringkas.

---

# 30. Page State yang Wajib

Setiap halaman utama harus memiliki:

- Default state.
- Loading state.
- Empty state.
- Error state.
- Success state.
- Permission state jika diperlukan.
- Filtered-empty state.
- Offline state jika relevan.

Contoh Transaction History:

```text
Default       : Menampilkan transaksi.
Loading       : Skeleton list.
Empty         : Belum ada transaksi.
Filtered Empty: Tidak ada transaksi sesuai filter.
Error         : Gagal memuat transaksi.
Offline       : Data terakhir tersedia atau pesan offline.
```

---

# 31. SEO dan Metadata

Karena aplikasi bersifat pribadi, SEO bukan prioritas.

Metadata minimum:

```text
Title       : Laras
Description : Personal life management web app
Theme Color : Primary blue
Favicon     : Laras icon
Robots      : noindex untuk halaman privat
```

Halaman privat tidak boleh diindeks mesin pencari.

---

# 32. Naming Convention Teknis

## 32.1 Route Name

Gunakan pola:

```text
module.action
```

Contoh:

```text
activities.index
activities.store
accounts.show
transactions.update
recommendations.follow
```

## 32.2 Blade Folder

```text
resources/views/
├── auth/
├── onboarding/
├── dashboard/
├── activities/
├── finance/
│   ├── accounts/
│   ├── transactions/
│   ├── budgets/
│   └── scans/
├── recommendations/
├── insights/
├── notes/
├── settings/
├── components/
├── layouts/
└── errors/
```

## 32.3 Component Naming

Gunakan kebab-case pada Blade component:

```text
<x-account-card />
<x-activity-card />
<x-transaction-row />
<x-priority-badge />
<x-confirm-dialog />
```

---

# 33. Route Middleware

## Public

```text
guest
```

## Authenticated

```text
auth
```

## Onboarding Incomplete

```text
auth
onboarding.incomplete
```

## Application

```text
auth
onboarding.complete
```

## Sensitive Financial Actions

Tambahan opsional:

```text
password.confirm
```

Digunakan untuk:

- Export seluruh data.
- Restore backup.
- Menghapus data.
- Mengubah email.
- Menonaktifkan keamanan.

---

# 34. Redirect Rules

```text
Belum login
→ /login

Sudah login tetapi onboarding belum selesai
→ /onboarding

Sudah login dan onboarding selesai
→ /dashboard

Membuka /login saat sudah login
→ /dashboard atau /onboarding

Route tidak ditemukan
→ Error 404

Session habis
→ /login dengan pesan session expired
```

---

# 35. Acceptance Criteria Sitemap

Sitemap dianggap siap apabila:

- Seluruh fitur pada project scope memiliki halaman atau action.
- Seluruh user flow memiliki tujuan halaman yang jelas.
- Navigasi desktop dan mobile sudah ditentukan.
- Route utama sudah diberi nama.
- Halaman publik dan privat sudah dipisahkan.
- Halaman MVP dan lanjutan sudah diprioritaskan.
- Komponen global sudah teridentifikasi.
- State loading, empty, dan error sudah diperhitungkan.
- Tidak ada halaman duplikat yang tidak diperlukan.
- Transfer internal dan scan memiliki halaman khusus.
- Settings mencakup preferensi utama.
- Struktur dapat diterjemahkan ke Laravel route dan Blade view.

---

# 36. Keputusan Final Sitemap

Keputusan final:

- Dashboard menjadi halaman utama setelah login.
- Finance menjadi satu modul dengan submenu.
- Accounts dan Transactions memiliki halaman detail terpisah.
- Expense, Income, dan Transfer memiliki form terpisah.
- Quick Add tetap tersedia secara global.
- Scan Transaction berada di bawah Finance.
- Recommendations memiliki halaman utama dan detail.
- Insights dipisah menjadi produktivitas dan keuangan.
- Notes tetap sederhana.
- Mobile menggunakan lima item bottom navigation.
- Settings dan fitur sekunder masuk menu tambahan pada mobile.
- Modal digunakan untuk tindakan singkat.
- Halaman penuh digunakan untuk data kompleks.
- Opening animation tidak menjadi penghalang akses.
- Route teknis menggunakan bahasa Inggris.
- Label antarmuka dapat menggunakan bahasa Indonesia.
- Halaman privat tidak diindeks.

---

# 37. Tahap Berikutnya

Setelah sitemap dan daftar halaman disetujui, tahap berikutnya adalah membuat:

```text
docs/04-feature-priority-and-development-phases.md
```

Dokumen tersebut akan memecah pengembangan menjadi fase yang lebih kecil, menentukan dependensi setiap fitur, serta menetapkan definisi selesai untuk tiap fase sebelum wireframe dan coding dimulai.
