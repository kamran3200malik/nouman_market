<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:disable-expired-banners')->daily();
Schedule::command('app:suspend-expired-subscriptions')->everyMinute();
Schedule::command('app:backup-database')->dailyAt('02:00');
