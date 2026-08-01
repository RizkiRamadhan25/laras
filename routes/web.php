<?php

use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionBillingController;
use App\Http\Controllers\ExpenseAnalysisController;
use App\Http\Controllers\RecommendationController;

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

    Route::get(
        '/analysis',
        [
            ExpenseAnalysisController::class,
            'index',
        ]
    )->name('analysis.index');

    Route::get(
        '/recommendations',
        [
            RecommendationController::class,
            'index',
        ]
    )->name('recommendations.index');

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

    Route::middleware('onboarding.completed')
        ->prefix('transactions')
        ->name('transactions.')
        ->group(function (): void {
            Route::get(
                '/',
                [TransactionController::class, 'index']
            )->name('index');

            Route::get(
                '/create',
                [TransactionController::class, 'create']
            )->name('create');

            Route::post(
                '/',
                [TransactionController::class, 'store']
            )->name('store');

            Route::get(
                '/{transaction}',
                [TransactionController::class, 'show']
            )
                ->whereNumber('transaction')
                ->name('show');

            Route::patch(
                '/{transaction}/cancel',
                [TransactionController::class, 'cancel']
            )
                ->whereNumber('transaction')
                ->name('cancel');
        });

    Route::middleware('onboarding.completed')
        ->group(function (): void {
            Route::get(
                '/activities',
                [ActivityController::class, 'index']
            )->name('activities.index');

            Route::get(
                '/priorities',
                [ActivityController::class, 'priorities']
            )->name('priorities.index');

            Route::get(
                '/activities/create',
                [ActivityController::class, 'create']
            )->name('activities.create');

            Route::post(
                '/activities',
                [ActivityController::class, 'store']
            )->name('activities.store');

            Route::get(
                '/activities/{activity}/edit',
                [ActivityController::class, 'edit']
            )
                ->whereNumber('activity')
                ->name('activities.edit');

            Route::put(
                '/activities/{activity}',
                [ActivityController::class, 'update']
            )
                ->whereNumber('activity')
                ->name('activities.update');

            Route::patch(
                '/activities/{activity}/start',
                [ActivityController::class, 'start']
            )
                ->whereNumber('activity')
                ->name('activities.start');

            Route::patch(
                '/activities/{activity}/complete',
                [ActivityController::class, 'complete']
            )
                ->whereNumber('activity')
                ->name('activities.complete');

            Route::patch(
                '/activities/{activity}/cancel',
                [ActivityController::class, 'cancel']
            )
                ->whereNumber('activity')
                ->name('activities.cancel');

            Route::patch(
                '/activities/{activity}/reopen',
                [ActivityController::class, 'reopen']
            )
                ->whereNumber('activity')
                ->name('activities.reopen');

            Route::patch(
                '/activities/{activity}/restore',
                [ActivityController::class, 'restore']
            )
                ->whereNumber('activity')
                ->name('activities.restore');

            Route::delete(
                '/activities/{activity}',
                [ActivityController::class, 'destroy']
            )
                ->whereNumber('activity')
                ->name('activities.destroy');
        });

    Route::middleware('onboarding.completed')
        ->prefix('notifications')
        ->name('notifications.')
        ->group(function (): void {
            Route::get(
                '/',
                [NotificationController::class, 'index']
            )->name('index');

            Route::patch(
                '/read-all',
                [
                    NotificationController::class,
                    'markAllRead',
                ]
            )->name('read-all');

            Route::get(
                '/{notification}/open',
                [NotificationController::class, 'open']
            )
                ->whereUuid('notification')
                ->name('open');

            Route::patch(
                '/{notification}/read',
                [
                    NotificationController::class,
                    'markRead',
                ]
            )
                ->whereUuid('notification')
                ->name('read');
        });

    Route::middleware('onboarding.completed')
        ->prefix('subscriptions')
        ->name('subscriptions.')
        ->group(function (): void {
            Route::get(
                '/',
                [SubscriptionController::class, 'index']
            )->name('index');

            Route::get(
                '/create',
                [SubscriptionController::class, 'create']
            )->name('create');

            Route::post(
                '/',
                [SubscriptionController::class, 'store']
            )->name('store');

            Route::get(
                '/{subscription}/billings/{billing}',
                [
                    SubscriptionBillingController::class,
                    'show',
                ]
            )
                ->whereNumber('subscription')
                ->whereNumber('billing')
                ->name('billings.show');

            Route::patch(
                '/{subscription}/billings/{billing}/retry',
                [
                    SubscriptionBillingController::class,
                    'retry',
                ]
            )
                ->whereNumber('subscription')
                ->whereNumber('billing')
                ->name('billings.retry');

            Route::get(
                '/{subscription}',
                [SubscriptionController::class, 'show']
            )
                ->whereNumber('subscription')
                ->name('show');

            Route::get(
                '/{subscription}/edit',
                [SubscriptionController::class, 'edit']
            )
                ->whereNumber('subscription')
                ->name('edit');

            Route::put(
                '/{subscription}',
                [SubscriptionController::class, 'update']
            )
                ->whereNumber('subscription')
                ->name('update');

            Route::patch(
                '/{subscription}/pause',
                [SubscriptionController::class, 'pause']
            )
                ->whereNumber('subscription')
                ->name('pause');

            Route::patch(
                '/{subscription}/resume',
                [SubscriptionController::class, 'resume']
            )
                ->whereNumber('subscription')
                ->name('resume');

            Route::patch(
                '/{subscription}/cancel',
                [SubscriptionController::class, 'cancel']
            )
                ->whereNumber('subscription')
                ->name('cancel');
        });
});
