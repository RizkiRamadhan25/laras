<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class LarasUserSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = [
            'name' => config('laras.user.name'),
            'email' => config('laras.user.email'),
            'password' => config('laras.user.password'),
        ];

        $validator = Validator::make($credentials, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191'],
            'password' => [
                'required',
                'string',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException(
                'Konfigurasi akun Laras tidak valid: ' .
                $validator->errors()->first()
            );
        }

        User::query()->updateOrCreate(
            ['email' => $credentials['email']],
            [
                'name' => $credentials['name'],
                'password' => $credentials['password'],
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
    }
}
