<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(
    'subscriptions:process'
)
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

Schedule::command(
    'budgets:sync-usage'
)
    ->dailyAt('00:10')
    ->withoutOverlapping(30);
