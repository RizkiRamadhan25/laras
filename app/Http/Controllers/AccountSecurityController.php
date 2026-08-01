<?php

namespace App\Http\Controllers;

use App\Enums\SecurityEventType;
use App\Http\Requests\LogoutOtherDevicesRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\SecurityEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountSecurityController extends Controller
{
    public function __construct(
        private readonly SecurityEventService $securityEvents
    ) {
    }

    public function updatePassword(
        UpdatePasswordRequest $request
    ): RedirectResponse {
        $user = $request->user();

        $currentPassword = $request
            ->validated(
                'current_password'
            );

        /*
         * Mengubah hash kata sandi lama terlebih
         * dahulu agar sesi lain menjadi tidak valid.
         */
        Auth::logoutOtherDevices(
            $currentPassword
        );

        $user->forceFill([
            'password' => Hash::make(
                $request->validated(
                    'password'
                )
            ),
        ])->save();

        $this->securityEvents->record(
            user: $user,
            type:
                SecurityEventType
                    ::PasswordChanged,
            request: $request
        );

        return redirect()
            ->route('settings.index')
            ->with(
                'status',
                'Kata sandi berhasil diperbarui. Perangkat lain juga telah dikeluarkan.'
            );
    }

    public function logoutOtherDevices(
        LogoutOtherDevicesRequest $request
    ): RedirectResponse {
        $user = $request->user();

        Auth::logoutOtherDevices(
            $request->validated(
                'logout_current_password'
            )
        );

        $this->securityEvents->record(
            user: $user,
            type:
                SecurityEventType
                    ::OtherSessionsLoggedOut,
            request: $request
        );

        return redirect()
            ->route('settings.index')
            ->with(
                'status',
                'Seluruh perangkat lain berhasil dikeluarkan.'
            );
    }
}
