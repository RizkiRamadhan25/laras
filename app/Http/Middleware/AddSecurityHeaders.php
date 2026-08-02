<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $response = $next($request);

        $response->headers->set(
            'Content-Security-Policy',
            $this->contentSecurityPolicy()
        );

        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        $response->headers->set(
            'X-Frame-Options',
            'DENY'
        );

        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );

        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        $response->headers->set(
            'Cross-Origin-Opener-Policy',
            'same-origin'
        );

        $response->headers->set(
            'Cross-Origin-Resource-Policy',
            'same-origin'
        );

        $response->headers->set(
            'X-Permitted-Cross-Domain-Policies',
            'none'
        );

        if (
            $request->isSecure()
            && app()->environment('production')
        ) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $scriptSources = [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
        ];

        $styleSources = [
            "'self'",
            "'unsafe-inline'",
        ];

        $imageSources = [
            "'self'",
            'data:',
            'blob:',
        ];

        $fontSources = [
            "'self'",
            'data:',
        ];

        $connectSources = [
            "'self'",
        ];

        if (app()->environment('local', 'testing')) {
            $httpDevelopmentSources = [
                'http://localhost:*',
                'http://127.0.0.1:*',
                'http://[::1]:*',
            ];

            $scriptSources = array_merge(
                $scriptSources,
                $httpDevelopmentSources
            );

            $styleSources = array_merge(
                $styleSources,
                $httpDevelopmentSources
            );

            $imageSources = array_merge(
                $imageSources,
                $httpDevelopmentSources
            );

            $fontSources = array_merge(
                $fontSources,
                $httpDevelopmentSources
            );

            $connectSources = array_merge(
                $connectSources,
                $httpDevelopmentSources,
                [
                    'ws://localhost:*',
                    'ws://127.0.0.1:*',
                    'ws://[::1]:*',
                ]
            );
        }

        return implode(
            '; ',
            [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",

                'script-src '
                    .implode(' ', $scriptSources),

                'style-src '
                    .implode(' ', $styleSources),

                'img-src '
                    .implode(' ', $imageSources),

                'font-src '
                    .implode(' ', $fontSources),

                'connect-src '
                    .implode(' ', $connectSources),

                "worker-src 'self' blob:",
                "manifest-src 'self'",
            ]
        );
    }
}
