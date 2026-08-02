<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InitialUserProvisioningService;
use Database\Seeders\FinanceCategorySeeder;
use Illuminate\Console\Command;
use RuntimeException;

class ProvisionLarasUserCommand extends Command
{
    protected $signature = 'laras:provision-user
        {--name= : Nama akun awal Laras}
        {--email= : Email akun awal Laras}
        {--no-categories : Jangan membuat kategori keuangan bawaan}';

    protected $description =
        'Membuat akun awal Laras secara aman tanpa mereset akun yang sudah ada.';

    public function handle(
        InitialUserProvisioningService $provisioningService
    ): int {
        $name = $this->resolveValue(
            'name',
            config(
                'laras.user.name',
                'Pengguna Laras'
            ),
            'Nama pengguna awal'
        );

        if ($name === null) {
            return self::FAILURE;
        }

        $email = $this->resolveValue(
            'email',
            config('laras.user.email'),
            'Email pengguna awal'
        );

        if ($email === null) {
            return self::FAILURE;
        }

        $normalizedEmail = mb_strtolower(
            trim($email)
        );

        $existingUser = User::withTrashed()
            ->where(
                'email',
                $normalizedEmail
            )
            ->first();

        $password = config(
            'laras.user.password'
        );

        if (
            $existingUser === null
            && ! is_string($password)
        ) {
            $password = null;
        }

        if (
            $existingUser === null
            && blank($password)
        ) {
            if (! $this->input->isInteractive()) {
                $this->components->error(
                    'LARAS_USER_PASSWORD wajib diisi untuk instalasi non-interaktif.'
                );

                return self::FAILURE;
            }

            $password = $this->secret(
                'Kata sandi awal (minimal 12 karakter, huruf besar-kecil, angka, dan simbol)'
            );

            $confirmation = $this->secret(
                'Ulangi kata sandi awal'
            );

            if ($password !== $confirmation) {
                $this->components->error(
                    'Konfirmasi kata sandi tidak sama.'
                );

                return self::FAILURE;
            }
        }

        try {
            $result = $provisioningService
                ->provision(
                    $name,
                    $normalizedEmail,
                    is_string($password)
                        ? $password
                        : null
                );
        } catch (RuntimeException $exception) {
            $this->components->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        if ($result['created']) {
            $this->components->info(
                'Akun awal Laras berhasil dibuat.'
            );
        } else {
            $this->components->info(
                'Akun awal sudah tersedia. Nama dan kata sandi tidak diubah.'
            );
        }

        if (! $this->option('no-categories')) {
            $exitCode = $this->call(
                'db:seed',
                [
                    '--class' =>
                        FinanceCategorySeeder::class,
                    '--force' => true,
                ]
            );

            if ($exitCode !== self::SUCCESS) {
                $this->components->error(
                    'Kategori keuangan bawaan gagal dibuat.'
                );

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function resolveValue(
        string $option,
        mixed $configuredValue,
        string $question
    ): ?string {
        $optionValue = $this->option(
            $option
        );

        if (
            is_string($optionValue)
            && trim($optionValue) !== ''
        ) {
            return trim($optionValue);
        }

        if (
            is_string($configuredValue)
            && trim($configuredValue) !== ''
        ) {
            return trim($configuredValue);
        }

        if (! $this->input->isInteractive()) {
            $this->components->error(
                sprintf(
                    'Nilai %s belum tersedia untuk instalasi non-interaktif.',
                    strtoupper($option)
                )
            );

            return null;
        }

        $answer = $this->ask($question);

        if (
            ! is_string($answer)
            || trim($answer) === ''
        ) {
            $this->components->error(
                $question.' wajib diisi.'
            );

            return null;
        }

        return trim($answer);
    }
}
