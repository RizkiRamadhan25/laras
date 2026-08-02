<?php

namespace App\Http\Middleware;

use App\Support\QueryMetrics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AddQueryMetricsHeaders
{
    public function __construct(
        private readonly QueryMetrics $metrics
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $this->metrics->reset();

        /** @var Response $response */
        $response = $next($request);

        $summary = $this->metrics->summary();

        $cumulativeThreshold = (float) config(
            'observability.queries.cumulative_threshold_ms',
            500
        );

        if (
            $summary['total_ms']
            >= $cumulativeThreshold
        ) {
            Log::warning(
                'Cumulative database query time exceeded threshold.',
                [
                    'request_id' => $request
                        ->attributes
                        ->get('request_id'),
                    'route' => $request
                        ->route()
                        ?->getName(),
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'query_count' => $summary['count'],
                    'total_ms' => $summary['total_ms'],
                    'slowest_ms' => $summary['slowest_ms'],
                    'slowest_fingerprint' =>
                        $summary['slowest_fingerprint'],
                ]
            );
        }

        if (
            ! config(
                'observability.queries.response_headers',
                false
            )
        ) {
            return $response;
        }

        $response->headers->set(
            'X-DB-Query-Count',
            (string) $summary['count']
        );

        $response->headers->set(
            'X-DB-Query-Time-Ms',
            number_format(
                $summary['total_ms'],
                2,
                '.',
                ''
            )
        );

        $response->headers->set(
            'X-DB-Slowest-Query-Ms',
            number_format(
                $summary['slowest_ms'],
                2,
                '.',
                ''
            )
        );

        $serverTiming = sprintf(
            'db;dur=%.2f;desc="%d queries"',
            $summary['total_ms'],
            $summary['count']
        );

        $existingServerTiming = $response
            ->headers
            ->get('Server-Timing');

        $response->headers->set(
            'Server-Timing',
            $existingServerTiming
                ? $existingServerTiming.', '.$serverTiming
                : $serverTiming
        );

        return $response;
    }
}
