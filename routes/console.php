<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deliver owner notifications for contact-form submissions out of band.
// Infomaniak has no real crontab — only an admin-panel pseudo-cron with a
// 15-minute floor — so the sweep runs every fifteen minutes. The DB row is
// written instantly on submit regardless; this only governs the email lag.
Schedule::command('contact:notify')->everyFifteenMinutes()->withoutOverlapping();
