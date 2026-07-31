<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfOnboardingIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->onboarding_completed_at !== null) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
