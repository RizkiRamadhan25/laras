<?php

namespace App\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QueryMetrics
{
    private int $count = 0;

    private float $totalMilliseconds = 0.0;

    private float $slowestMilliseconds = 0.0;

    private ?string $slowestFingerprint = null;

    public function reset(): void
    {
        $this->count = 0;
        $this->totalMilliseconds = 0.0;
        $this->slowestMilliseconds = 0.0;
        $this->slowestFingerprint = null;
    }

    public function record(QueryExecuted $event): void
    {
        $duration = (float) $event->time;
        $normalizedSql = $this->normalizeSql(
            $event->sql
        );

        $this->count++;
        $this->totalMilliseconds += $duration;

        if ($duration > $this->slowestMilliseconds) {
            $this->slowestMilliseconds = $duration;
            $this->slowestFingerprint = sha1(
                $normalizedSql
            );
        }

        $threshold = (float) config(
            'observability.queries.slow_query_threshold_ms',
            250
        );

        if ($duration < $threshold) {
            return;
        }

        $requestContext = $this->requestContext();

        Log::warning(
            'Slow database query detected.',
            [
                'request_id' => $requestContext['request_id'],
                'route' => $requestContext['route'],
                'method' => $requestContext['method'],
                'connection' => $event->connectionName,
                'duration_ms' => round($duration, 2),
                'sql_fingerprint' => sha1($normalizedSql),
                'sql' => $this->preview($normalizedSql),
            ]
        );
    }

    /**
     * @return array{
     *     count: int,
     *     total_ms: float,
     *     slowest_ms: float,
     *     slowest_fingerprint: string|null
     * }
     */
    public function summary(): array
    {
        return [
            'count' => $this->count,
            'total_ms' => round(
                $this->totalMilliseconds,
                2
            ),
            'slowest_ms' => round(
                $this->slowestMilliseconds,
                2
            ),
            'slowest_fingerprint' => $this->slowestFingerprint,
        ];
    }

    public function totalMilliseconds(): float
    {
        return $this->totalMilliseconds;
    }

    private function normalizeSql(string $sql): string
    {
        return trim(
            preg_replace('/\s+/', ' ', $sql)
                ?? $sql
        );
    }

    private function preview(string $sql): string
    {
        $length = max(
            100,
            (int) config(
                'observability.queries.sql_preview_length',
                1000
            )
        );

        return Str::limit(
            $sql,
            $length,
            '…'
        );
    }

    /**
     * @return array{
     *     request_id: string|null,
     *     route: string|null,
     *     method: string|null
     * }
     */
    private function requestContext(): array
    {
        if (! app()->bound('request')) {
            return [
                'request_id' => null,
                'route' => null,
                'method' => null,
            ];
        }

        $request = request();

        return [
            'request_id' => $request
                ->attributes
                ->get('request_id')
                ?? $request
                    ->headers
                    ->get('X-Request-ID'),
            'route' => $request
                ->route()
                ?->getName(),
            'method' => $request->method(),
        ];
    }
}
