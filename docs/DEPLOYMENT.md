# Deployment Laras MVP

Dokumen ini menjelaskan deployment Laras pada server Linux dengan web server, PHP-FPM, MySQL, queue worker, dan cron scheduler. Sesuaikan nama user, domain, path, versi PHP, dan service manager dengan server yang digunakan.

## 1. Arsitektur minimum

- Nginx atau Apache dengan document root menuju folder `public`.
- PHP 8.3 dan PHP-FPM.
- MySQL 8 atau database yang kompatibel.
- Composer 2.10 atau lebih baru.
- Process monitor untuk queue worker, misalnya Supervisor atau systemd.
- Cron untuk Laravel scheduler.
- HTTPS aktif.

Node.js hanya dibutuhkan pada mesin yang menjalankan `npm ci` dan `npm run build`. Asset hasil build dapat dibuat di CI atau mesin deployment lalu dikirim ke server runtime.

## 2. Ekstensi PHP minimum

Pastikan kebutuhan Composer lulus:

```bash
composer check-platform-reqs
```

Laras secara eksplisit memerlukan:

```text
ext-bcmath
ext-exif
ext-gd
ext-zip
```

Laravel dan dependency lain juga memerlukan ekstensi PHP umum seperti OpenSSL, PDO, Mbstring atau polyfill yang kompatibel, Tokenizer, XML, Ctype, JSON, dan Fileinfo.

## 3. Persiapan source

Contoh path deployment:

```text
/var/www/laras
```

Clone atau upload source tanpa file berikut:

```text
.env
node_modules/
vendor/
public/hot
storage/logs/*.log
database/database.sqlite
```

Pastikan web server hanya mengekspos:

```text
/var/www/laras/public
```

Jangan menjadikan root repository sebagai document root.

## 4. Instalasi dependency

```bash
cd /var/www/laras
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
```

Pada deployment yang membangun asset di CI, `npm ci` dan `npm run build` dapat dijalankan di CI, lalu folder `public/build` ikut dikirim ke server.

## 5. Environment production

```bash
cp .env.production.example .env
php artisan key:generate
```

Isi minimal:

```env
APP_NAME=Laras
APP_ENV=production
APP_DEBUG=false
APP_URL=https://laras.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laras
DB_USERNAME=laras
DB_PASSWORD=PASSWORD_KUAT

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME=Laras

LARAS_ELOQUENT_STRICT=false
LARAS_QUERY_MONITORING=true
LARAS_QUERY_RESPONSE_HEADERS=false
```

Jangan menyimpan `.env` production di Git.

## 6. Permission

Web server dan queue worker harus dapat menulis ke:

```text
storage/
bootstrap/cache/
```

Contoh:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

Gunakan permission paling sempit yang tetap memungkinkan aplikasi berjalan.

## 7. Database dan storage link

```bash
php artisan migrate --force
php artisan storage:link
```

Untuk instalasi pertama, buat akun pribadi:

```bash
php artisan laras:provision-user
```

Jangan menjalankan provisioning ulang pada database production yang sudah memiliki pengguna kecuali command mengonfirmasi operasi aman.

## 8. Optimization dan release check

```bash
php artisan optimize
php artisan laras:release-check --production --require-cache
```

Target:

```text
Release check lulus: 0 failure
```

Peringatan mail transport harus diselesaikan sebelum fitur reset password atau notifikasi email digunakan secara nyata.

## 9. Queue worker

Contoh command worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

Jalankan dengan process monitor agar worker otomatis hidup kembali. Setelah deployment source baru, restart worker secara graceful:

```bash
php artisan queue:restart
```

Pastikan log worker dipantau dan tabel failed jobs diperiksa bila ada kegagalan.

## 10. Scheduler

Tambahkan satu cron entry yang berjalan setiap menit:

```cron
* * * * * cd /var/www/laras && php artisan schedule:run >> /dev/null 2>&1
```

Periksa jadwal aplikasi:

```bash
php artisan schedule:list
```

Scheduler diperlukan untuk otomatisasi langganan, reminder, dan sinkronisasi penggunaan anggaran.

## 11. Konfigurasi web server

### Nginx ringkas

```nginx
server {
    listen 443 ssl http2;
    server_name laras.example.com;

    root /var/www/laras/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

Konfigurasi TLS, rate limiting, ukuran upload, log rotation, dan header proxy harus disesuaikan dengan infrastruktur.

## 12. Urutan deployment pembaruan

```bash
cd /var/www/laras
php artisan down --retry=60

git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan laras:release-check --production --require-cache
php artisan queue:restart

php artisan up
```

Pada sistem zero-downtime, gunakan strategi release directory dan symlink aktif daripada memperbarui source langsung.

## 13. Backup dan restore

Backup minimum:

- Database MySQL.
- `.env` production melalui secret manager atau penyimpanan terenkripsi.
- `storage/app/public`, terutama foto profil dan file pengguna.

Contoh database backup:

```bash
mysqldump --single-transaction --routines --triggers \
  -u laras -p laras > laras-$(date +%F-%H%M).sql
```

Backup dianggap valid hanya setelah restore pernah diuji pada environment terpisah.

## 14. Verifikasi setelah deployment

```bash
php artisan about
php artisan migrate:status
php artisan schedule:list
php artisan laras:release-check --production --require-cache
```

Periksa manual:

- `/up` menghasilkan HTTP 200.
- `/login` tampil tanpa detail debug.
- URL yang tidak tersedia menampilkan custom 404 dan kode referensi.
- Security headers tersedia.
- Login, dashboard, transaksi, upload foto, dan logout berfungsi.
- Queue worker dan scheduler aktif.

## 15. Rollback

Sebelum deployment, simpan:

- Commit atau release tag sebelumnya.
- Backup database sebelum migration berisiko.
- Salinan asset dan storage yang diperlukan.

Rollback source tidak otomatis membatalkan migration. Periksa migration baru sebelum memutuskan rollback database. Jangan menjalankan `migrate:rollback` secara buta pada production.
