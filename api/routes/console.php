<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sites:check')->everyMinute();
Schedule::command('sites:digest')
    ->cron('0 0,6,12,18 * * *')
    ->timezone('America/Guayaquil');
