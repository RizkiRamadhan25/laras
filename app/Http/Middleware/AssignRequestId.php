<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    private const HEADER = 'X-Request-ID';

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $requestId = $this->resolveRequestId(
            $request
        );

        $request->attributes->set(
            'request_id',
            $requestId
        );

        Log::withContext([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        $response = $next($request);

        $response->headers->set(
            self::HEADER,
            $requestId
        );

        return $response;
    }

    private function resolveRequestId(
        Request $request
    ): string {
        $provided = trim(
            (string) $request->header(
                self::HEADER,
                ''
            )
        );

        if (
            $provided !== ''
            && preg_match(
                '/\A[A-Za-z0-9][A-Za-z0-9._:-]{7,99}\z/',
                $provided
            ) === 1
        ) {
            return $provided;
        }

        return (string) Str::uuid();
    }
}
