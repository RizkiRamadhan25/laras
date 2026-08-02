<?php

namespace App\Providers;

use App\Support\QueryMetrics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(
            QueryMetrics::class,
            fn (): QueryMetrics => new QueryMetrics()
        );
    }

    public function boot(): void
    {
        Model::shouldBeStrict(
            (bool) config(
                'observability.eloquent.strict',
                false
            )
        );

        if (
            ! config(
                'observability.queries.enabled',
                false
            )
        ) {
            return;
        }

        DB::listen(
            function (QueryExecuted $event): void {
                app(QueryMetrics::class)
                    ->record($event);
            }
        );
    }
}
