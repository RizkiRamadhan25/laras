<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Listeners\UpdateLastLoginAt;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
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
        Event::listen(
            Login::class,
            UpdateLastLoginAt::class,
        );

        View::composer(
            'partials.app-topbar',
            function (
                \Illuminate\View\View $view
            ): void {
                $user = Auth::user();

                if ($user === null) {
                    $view->with([
                        'headerNotifications' => collect(),
                        'headerUnreadNotificationCount' => 0,
                    ]);

                    return;
                }

                $view->with([
                    'headerNotifications' =>
                        $user->notifications()
                            ->latest()
                            ->limit(5)
                            ->get(),

                    'headerUnreadNotificationCount' =>
                        $user->unreadNotifications()
                            ->count(),
                ]);
            }
        );
    }
}
