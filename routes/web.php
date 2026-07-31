<?php

use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function (): void {
    Route::middleware('onboarding.pending')->group(function (): void {
        Route::get('/onboarding', [OnboardingController::class, 'show'])
            ->name('onboarding.show');

        Route::get(
            '/onboarding/preferences',
            [OnboardingController::class, 'editPreferences']
        )->name('onboarding.preferences.edit');

        Route::post(
            '/onboarding/preferences',
            [OnboardingController::class, 'storePreferences']
        )->name('onboarding.preferences.store');

        Route::get(
            '/onboarding/accounts',
            [OnboardingController::class, 'accounts']
        )->name('onboarding.accounts');

        Route::post(
            '/onboarding/accounts',
            [OnboardingController::class, 'storeAccounts']
        )->name('onboarding.accounts.store');
    });

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )
        ->middleware('onboarding.completed')
        ->name('dashboard');
});
