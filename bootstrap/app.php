<?php

use App\Http\Middleware\AddQueryMetricsHeaders;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\RedirectIfOnboardingIsComplete;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(
        function (Middleware $middleware): void {
            $middleware->web(append: [
                AssignRequestId::class,
                AddQueryMetricsHeaders::class,
                AddSecurityHeaders::class,
            ]);

            $middleware->alias([
                'onboarding.completed' => EnsureOnboardingIsComplete::class,

                'onboarding.pending' => RedirectIfOnboardingIsComplete::class,
            ]);
        }
    )
    ->withExceptions(
        function (Exceptions $exceptions): void {
            $exceptions->dontReportDuplicates();

            $exceptions->shouldRenderJsonWhen(
                fn (Request $request): bool => $request->is('api/*'),
            );

            $exceptions->respond(
                function (Response $response): Response {
                    $requestId = request()
                        ->attributes
                        ->get('request_id');

                    if (is_string($requestId)) {
                        $response->headers->set(
                            'X-Request-ID',
                            $requestId
                        );
                    }

                    return $response;
                }
            );
        }
    )
    ->create();
