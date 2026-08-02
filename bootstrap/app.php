<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\RedirectIfOnboardingIsComplete;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AddSecurityHeaders::class,
        ]);

        $middleware->alias([
            'onboarding.completed' => EnsureOnboardingIsComplete::class,
            'onboarding.pending' => RedirectIfOnboardingIsComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
