<?php

namespace Database\Seeders;

use App\Services\InitialUserProvisioningService;
use Illuminate\Database\Seeder;
use RuntimeException;

class LarasUserSeeder extends Seeder
{
    public function run(
        InitialUserProvisioningService $provisioningService
    ): void {
        $name = config(
            'laras.user.name',
            'Pengguna Laras'
        );

        $email = config(
            'laras.user.email'
        );

        $password = config(
            'laras.user.password'
        );

        if (
            ! is_string($name)
            || ! is_string($email)
        ) {
            throw new RuntimeException(
                'LARAS_USER_NAME dan LARAS_USER_EMAIL wajib diisi untuk menjalankan LarasUserSeeder.'
            );
        }

        $result = $provisioningService
            ->provision(
                $name,
                $email,
                is_string($password)
                    ? $password
                    : null
            );

        if ($this->command === null) {
            return;
        }

        $this->command->info(
            $result['created']
                ? 'Akun awal Laras berhasil dibuat.'
                : 'Akun awal sudah tersedia dan tidak diubah.'
        );
    }
}
