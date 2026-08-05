<?php

namespace App\Providers;

use App\Actions\Fortify\AttemptToAuthenticate as LarasAttemptToAuthenticate;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);
        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        Fortify::resetPasswordView(function (Request $request) {
            return view('auth.reset-password', [
                'request' => $request,
            ]);
        });

        Fortify::authenticateThrough(
            function (Request $request): array {
                return array_values(array_filter([
                    config('fortify.limiters.login')
                        ? null
                        : EnsureLoginIsNotThrottled::class,

                    config('fortify.lowercase_usernames')
                        ? CanonicalizeUsername::class
                        : null,

                    Features::enabled(
                        Features::twoFactorAuthentication()
                    )
                        ? RedirectIfTwoFactorAuthenticatable::class
                        : null,

                    LarasAttemptToAuthenticate::class,
                    PrepareAuthenticatedSession::class,
                ]));
            }
        );

        Fortify::authenticateUsing(function (Request $request): ?User {
            $request->validate(
                [
                    'email' => [
                        'required',
                        'string',
                        'email',
                    ],

                    'password' => [
                        'required',
                        'string',
                    ],
                ],
                [
                    'email.required' => 'Email wajib diisi.',
                    'email.email' => 'Format email tidak valid.',
                    'password.required' => 'Kata sandi wajib diisi.',
                ]
            );

            $email = Str::lower(
                trim(
                    (string) $request->input(
                        'email'
                    )
                )
            );

            $user = User::query()
                ->where('email', $email)
                ->first();

            if ($user === null) {
                $request->attributes->set(
                    'laras_auth_failure',
                    'email'
                );

                return null;
            }

            if (! $user->is_active) {
                $request->attributes->set(
                    'laras_auth_failure',
                    'inactive'
                );

                return null;
            }

            if (
                ! Hash::check(
                    (string) $request->input(
                        'password'
                    ),
                    $user->password
                )
            ) {
                $request->attributes->set(
                    'laras_auth_failure',
                    'password'
                );

                return null;
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
