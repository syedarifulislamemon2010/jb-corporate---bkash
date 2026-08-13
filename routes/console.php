<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sftp:fetch-bkash-files')->everyFifteenMinutes();
Schedule::command('mt940:generate --push-sftp')->dailyAt('23:30');