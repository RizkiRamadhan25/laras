<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\AttemptToAuthenticate as FortifyAttemptToAuthenticate;
use Laravel\Fortify\Fortify;

class AttemptToAuthenticate extends FortifyAttemptToAuthenticate
{
    /**
     * Throw a localized authentication validation exception.
     *
     * @throws ValidationException
     */
    protected function throwFailedAuthenticationException(
        $request
    ): void {
        $this->limiter->increment($request);

        $detailed = (bool) config(
            'laras.authentication.detailed_errors',
            false
        );

        if (! $detailed) {
            throw ValidationException::withMessages([
                Fortify::username() => [
                    trans('auth.failed'),
                ],
            ]);
        }

        [$field, $message] = $this->failureMessage(
            $request
        );

        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function failureMessage(
        Request $request
    ): array {
        return match (
            $request->attributes->get(
                'laras_auth_failure'
            )
        ) {
            'email' => [
                'email',
                'Email tidak terdaftar.',
            ],
            'inactive' => [
                'email',
                'Akun ini sedang tidak aktif.',
            ],
            'password' => [
                'password',
                'Kata sandi yang dimasukkan tidak sesuai.',
            ],
            default => [
                Fortify::username(),
                trans('auth.failed'),
            ],
        };
    }
}
