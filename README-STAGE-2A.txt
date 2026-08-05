LARAS v1.0.1 — TAHAP 2A
GLOBAL FEEDBACK: TOAST + CONFIRM DIALOG

TUJUAN
- Mengganti banner session sementara dengan toast global.
- Mendukung banyak toast yang tersusun tanpa saling menimpa.
- Menambahkan tombol tutup, auto-dismiss, pause saat hover/fokus,
  dan swipe horizontal untuk menutup.
- Menyediakan API window.LarasToast untuk aksi asinkron berikutnya.
- Mengganti seluruh confirm() bawaan browser pada Blade dengan
  dialog konfirmasi Laras yang reusable.

FILE BARU
- resources/js/ui/toast.js
- resources/js/ui/confirm-dialog.js
- resources/views/components/ui/toast-container.blade.php
- resources/views/components/ui/confirm-dialog.blade.php
- tests/Feature/GlobalFeedbackInterfaceTest.php
- README-STAGE-2A.txt

FILE DIUBAH
- resources/js/app.js
- resources/css/app.css
- resources/views/layouts/app.blade.php
- resources/views/layouts/auth.blade.php
- resources/views/accounts/index.blade.php
- resources/views/subscriptions/index.blade.php
- resources/views/subscriptions/billings/show.blade.php
- resources/views/subscriptions/show.blade.php
- resources/views/budgets/index.blade.php
- resources/views/budgets/show.blade.php
- resources/views/activities/index.blade.php
- resources/views/transactions/create.blade.php
- resources/views/transactions/show.blade.php
- resources/views/settings/index.blade.php
- resources/views/settings/partials/security.blade.php
- resources/views/settings/partials/data-privacy.blade.php
- resources/views/auth/login.blade.php
- resources/views/auth/forgot-password.blade.php
- resources/views/onboarding/show.blade.php
- resources/views/onboarding/accounts.blade.php

CATATAN
- Validation error per field tetap tampil di bawah input karena sifatnya
  kontekstual dan tidak diganti toast.
- Dialog penghapusan data dari Tahap 1E tetap dipertahankan karena dialog
  tersebut memiliki preview dampak khusus.
- Toast desktop muncul di kanan atas. Pada mobile, toast muncul di bawah.
- Session flash yang didukung: status/success, warning, error, info, dan
  array session bernama toasts.

API JAVASCRIPT
window.LarasToast.success('Data berhasil disimpan.');
window.LarasToast.warning('Periksa kembali data.');
window.LarasToast.error('Permintaan gagal.');
window.LarasToast.info('Proses sedang berjalan.');

Atau melalui event:
document.dispatchEvent(new CustomEvent('laras:toast', {
    detail: {
        type: 'success',
        title: 'Berhasil',
        message: 'Data berhasil disimpan.',
    },
}));
