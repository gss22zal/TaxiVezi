<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Расчёт выручки каждый день в 00:05 (чтобы точно после смены суток)
Schedule::command('revenue:calculate --days=7')->dailyAt('00:05');
