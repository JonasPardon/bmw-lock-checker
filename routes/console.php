<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Optional: poll every 10 minutes and warn when the car is unlocked. CarData REST allows
// 50 requests/day per client, so keep this coarse (144/day would exceed it — use 30m or event-driven).
// Schedule::command('bmw:check --notify')->everyThirtyMinutes();
