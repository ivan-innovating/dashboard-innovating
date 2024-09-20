<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

//Schedule::command('import:cordis_json')->timezone('Europe/Madrid')->cron('5 4 * * 6')->withoutOverlapping();
Schedule::command('app:apply-rules-on-projects')->timezone('Europe/Madrid')->dailyAt('03:13')->withoutOverlapping();
Schedule::command('app:move-cdti-projects-to-innovating')->timezone('Europe/Madrid')->dailyAt('05:13')->withoutOverlapping();
Schedule::command('scrapper:cdti_proyectos')->timezone('Europe/Madrid')->dailyAt('01:13')->withoutOverlapping();