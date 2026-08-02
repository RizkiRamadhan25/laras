<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class InitialUserProvisioningService
{
    /**
     * @return array{user: User, created: bool}
     */
    public function provision(
        string $name,
        string $email,
        ?string $password = null
    ): array {
        $name = trim($name);
        $email = mb_strtolower(trim($email));

        $identityValidator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
            ],
            [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:191',
                ],
            ]
        );

        if ($identityValidator->fails()) {
            throw new RuntimeException(
                'Konfigurasi akun awal Laras tidak valid: '
                .$identityValidator->errors()->first()
            );
        }

        $existingUser = User::withTrashed()
            ->where('email', $email)
            ->first();

        if ($existingUser !== null) {
            if ($existingUser->trashed()) {
                throw new RuntimeException(
                    'Email akun awal sudah digunakan oleh akun yang dinonaktifkan.'
                );
            }

            return [
                'user' => $existingUser,
                'created' => false,
            ];
        }

        $passwordValidator = Validator::make(
            [
                'password' => $password,
            ],
            [
                'password' => [
                    'required',
                    'string',
                    Password::min(12)
                        ->mixedCase()
                        ->numbers()
                        ->symbols(),
                ],
            ]
        );

        if ($passwordValidator->fails()) {
            throw new RuntimeException(
                'Kata sandi akun awal Laras tidak valid: '
                .$passwordValidator->errors()->first()
            );
        }

        $user = DB::transaction(
            function () use (
                $name,
                $email,
                $password
            ): User {
                $user = new User();

                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);

                $user->save();

                return $user;
            },
            3
        );

        return [
            'user' => $user,
            'created' => true,
        ];
    }
}
