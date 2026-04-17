<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('tracking:update-active')->hourly();

Schedule::command('notifications:late-shipments daily')->dailyAt('08:00');
Schedule::command('notifications:late-shipments weekly')->weeklyOn(1, '08:00');
Schedule::command('notifications:late-shipments monthly')->monthlyOn(1, '08:00');

Schedule::command('reports:late-shipments daily')->dailyAt('08:00');
Schedule::command('reports:late-shipments weekly')->weeklyOn(1, '08:00');
Schedule::command('reports:late-shipments monthly')->monthlyOn(1, '08:00');
