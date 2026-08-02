<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReleaseReadinessCommand extends Command
{
    protected $signature = 'laras:release-check
        {--production : Terapkan pemeriksaan wajib untuk production}
        {--require-cache : Wajibkan configuration dan route cache aktif}
        {--skip-database : Lewati koneksi database dan migration}
        {--skip-filesystem : Lewati manifest, storage link, dan permission}';

    protected $description = 'Memeriksa kesiapan konfigurasi Laras sebelum rilis';

    /**
     * @var array<int, array{status: string, check: string, detail: string}>
     */
    private array $checks = [];

    public function handle(): int
    {
        $production = (bool) $this->option('production');

        $this->checkCoreConfiguration($production);
        $this->checkRuntimeConfiguration($production);

        if (! $this->option('skip-filesystem')) {
            $this->checkFilesystem($production);
        }

        if (! $this->option('skip-database')) {
            $this->checkDatabase();
        }

        if ($this->option('require-cache')) {
            $this->checkOptimizationCache();
        }

        $this->newLine();
        $this->table(
            ['Status', 'Pemeriksaan', 'Detail'],
            array_map(
                fn (array $check): array => [
                    $check['status'],
                    $check['check'],
                    $check['detail'],
                ],
                $this->checks
            )
        );

        $failures = count(array_filter(
            $this->checks,
            fn (array $check): bool => $check['status'] === 'FAIL'
        ));

        $warnings = count(array_filter(
            $this->checks,
            fn (array $check): bool => $check['status'] === 'WARN'
        ));

        $this->newLine();

        if ($failures > 0) {
            $this->error("Release check gagal: {$failures} failure, {$warnings} warning.");

            return self::FAILURE;
        }

        $this->info("Release check lulus: 0 failure, {$warnings} warning.");

        return self::SUCCESS;
    }

    private function checkCoreConfiguration(bool $production): void
    {
        $this->result(
            filled(config('app.key')),
            'APP_KEY',
            'Application key tersedia.',
            'APP_KEY kosong.'
        );

        if (! $production) {
            return;
        }

        $this->result(
            config('app.env') === 'production',
            'APP_ENV',
            'Environment adalah production.',
            'APP_ENV harus production.'
        );

        $this->result(
            config('app.debug') === false,
            'APP_DEBUG',
            'Debug mode nonaktif.',
            'APP_DEBUG harus false.'
        );

        $appUrl = (string) config('app.url');

        $this->result(
            str_starts_with($appUrl, 'https://'),
            'APP_URL',
            'URL menggunakan HTTPS.',
            'APP_URL harus menggunakan HTTPS.'
        );
    }

    private function checkRuntimeConfiguration(bool $production): void
    {
        if (! $production) {
            return;
        }

        $this->result(
            config('session.secure') === true,
            'Session secure cookie',
            'Cookie session hanya dikirim melalui HTTPS.',
            'SESSION_SECURE_COOKIE harus true.'
        );

        $this->result(
            config('session.http_only') === true,
            'Session HttpOnly',
            'Cookie session menggunakan HttpOnly.',
            'SESSION_HTTP_ONLY harus true.'
        );

        $sameSite = strtolower((string) config('session.same_site'));

        $this->result(
            in_array($sameSite, ['lax', 'strict'], true),
            'Session SameSite',
            "SameSite={$sameSite}.",
            'SESSION_SAME_SITE harus lax atau strict.'
        );

        $this->result(
            config('session.driver') !== 'array',
            'Session driver',
            'Session driver persisten digunakan.',
            'SESSION_DRIVER tidak boleh array.'
        );

        $this->result(
            config('cache.default') !== 'array',
            'Cache store',
            'Cache store persisten digunakan.',
            'CACHE_STORE tidak boleh array.'
        );

        $this->result(
            config('queue.default') !== 'sync',
            'Queue connection',
            'Queue menggunakan worker terpisah.',
            'QUEUE_CONNECTION tidak boleh sync.'
        );

        $this->result(
            config('observability.eloquent.strict') === false,
            'Eloquent strict mode',
            'Strict mode production nonaktif.',
            'LARAS_ELOQUENT_STRICT harus false.'
        );

        $this->result(
            config('observability.queries.response_headers') === false,
            'Query metric headers',
            'Header metrik database tidak diekspos.',
            'LARAS_QUERY_RESPONSE_HEADERS harus false.'
        );

        if (config('session.encrypt') !== true) {
            $this->warning(
                'Session data encryption',
                'SESSION_ENCRYPT masih false; disarankan true pada production.'
            );
        } else {
            $this->passing(
                'Session data encryption',
                'Data session terenkripsi.'
            );
        }

        $logLevel = strtolower((string) config('logging.channels.single.level'));

        if ($logLevel === 'debug') {
            $this->warning(
                'Log level',
                'LOG_LEVEL masih debug; disarankan warning atau error.'
            );
        } else {
            $this->passing(
                'Log level',
                "LOG_LEVEL={$logLevel}."
            );
        }

        if (config('mail.default') === 'log') {
            $this->warning(
                'Mail transport',
                'MAIL_MAILER=log; email nyata belum akan dikirim.'
            );
        } else {
            $this->passing(
                'Mail transport',
                'Mail transport production telah dipilih.'
            );
        }
    }

    private function checkFilesystem(bool $production): void
    {
        $manifestPath = public_path('build/manifest.json');

        $this->result(
            is_file($manifestPath),
            'Vite production manifest',
            'public/build/manifest.json tersedia.',
            'Jalankan npm run build.'
        );

        foreach ([
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $path) {
            $this->result(
                is_dir($path) && is_writable($path),
                'Writable directory',
                "Writable: {$path}",
                "Tidak writable: {$path}"
            );
        }

        $publicStorage = realpath(public_path('storage'));
        $storageTarget = realpath(storage_path('app/public'));
        $storageLinked = $publicStorage !== false
            && $storageTarget !== false
            && $this->normalizePath($publicStorage) === $this->normalizePath($storageTarget);

        $this->result(
            $storageLinked,
            'Public storage link',
            'public/storage mengarah ke storage/app/public.',
            'Jalankan php artisan storage:link.'
        );

        if ($production) {
            $this->result(
                ! is_file(public_path('hot')),
                'Vite hot file',
                'public/hot tidak tersedia.',
                'Hapus public/hot sebelum deployment.'
            );
        }
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->passing('Database connection', 'Koneksi database berhasil.');
        } catch (Throwable $exception) {
            $this->failing(
                'Database connection',
                'Koneksi database gagal ('.$exception::class.').'
            );

            return;
        }

        try {
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                $this->failing(
                    'Migration repository',
                    'Tabel migrations belum tersedia.'
                );

                return;
            }

            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));

            $this->result(
                $pending === [],
                'Pending migrations',
                'Tidak ada migration tertunda.',
                count($pending).' migration masih tertunda.'
            );
        } catch (Throwable $exception) {
            $this->failing(
                'Pending migrations',
                'Pemeriksaan migration gagal ('.$exception::class.').'
            );
        }
    }

    private function checkOptimizationCache(): void
    {
        $this->result(
            app()->configurationIsCached(),
            'Configuration cache',
            'Configuration cache aktif.',
            'Jalankan php artisan optimize.'
        );

        $this->result(
            app()->routesAreCached(),
            'Route cache',
            'Route cache aktif.',
            'Jalankan php artisan optimize.'
        );
    }

    private function result(
        bool $condition,
        string $check,
        string $passDetail,
        string $failDetail
    ): void {
        if ($condition) {
            $this->passing($check, $passDetail);

            return;
        }

        $this->failing($check, $failDetail);
    }

    private function passing(string $check, string $detail): void
    {
        $this->checks[] = [
            'status' => 'PASS',
            'check' => $check,
            'detail' => $detail,
        ];
    }

    private function warning(string $check, string $detail): void
    {
        $this->checks[] = [
            'status' => 'WARN',
            'check' => $check,
            'detail' => $detail,
        ];
    }

    private function failing(string $check, string $detail): void
    {
        $this->checks[] = [
            'status' => 'FAIL',
            'check' => $check,
            'detail' => $detail,
        ];
    }

    private function normalizePath(string $path): string
    {
        return strtolower(str_replace('\\', '/', rtrim($path, '\\/')));
    }
}
