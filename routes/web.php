<?php

use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;

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

    Route::middleware('onboarding.completed')
        ->prefix('accounts')
        ->name('accounts.')
        ->group(function (): void {
            Route::get(
                '/',
                [AccountController::class, 'index']
            )->name('index');

            Route::get(
                '/create',
                [AccountController::class, 'create']
            )->name('create');

            Route::post(
                '/',
                [AccountController::class, 'store']
            )->name('store');

            Route::patch(
                '/{account}/move',
                [AccountController::class, 'move']
            )
                ->whereNumber('account')
                ->name('move');

            Route::patch(
                '/{account}/restore',
                [AccountController::class, 'restore']
            )
                ->whereNumber('account')
                ->name('restore');

            Route::get(
                '/{account}/edit',
                [AccountController::class, 'edit']
            )
                ->whereNumber('account')
                ->name('edit');

            Route::put(
                '/{account}',
                [AccountController::class, 'update']
            )
                ->whereNumber('account')
                ->name('update');

            Route::delete(
                '/{account}',
                [AccountController::class, 'destroy']
            )
                ->whereNumber('account')
                ->name('destroy');
        });
});
