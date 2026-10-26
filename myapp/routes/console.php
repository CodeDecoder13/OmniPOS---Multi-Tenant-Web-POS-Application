<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('shifts:close-stale')->hourly();
// 23:30 shop time (Manila), not 23:30 server time (UTC), so the summary covers the finished local day.
Schedule::command('reports:daily-summary')->dailyAt('23:30')->timezone(config('app.business_timezone'));
