<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| WhatsApp Appointment Reminders Schedule
|--------------------------------------------------------------------------
| Runs every minute to check for appointments exactly 5 minutes away.
|
| To start the scheduler locally:
|   php artisan schedule:work
|
| On production server: add to crontab:
|   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
*/

Schedule::command('appointments:send-reminders --minutes=5 --window=2')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/appointment-reminders.log'));
