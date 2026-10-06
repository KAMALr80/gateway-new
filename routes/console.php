<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Shared hosting (Hostinger) has no process supervisor, so the queue is drained by the scheduler.
 * A single cron entry runs `php artisan schedule:run` every minute (see RELEASE_CHECKLIST / deployment notes).
 */

// Send queued orders to the ERP. Exits when the queue is empty or after 55s, so runs never pile up.
Schedule::command('queue:work database --queue=default --stop-when-empty --max-time=55 --tries=3 --sleep=3')
    ->everyMinute()
    ->withoutOverlapping(10);

// Safety net: re-queue orders that were never synced (e.g. the worker was down).
Schedule::command('erp:retry-orders')->hourly()->withoutOverlapping();

// Keep the failed_jobs table small.
Schedule::command('queue:prune-failed --hours=720')->daily();
