<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Psyao\Kami\Facades\Kami;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Kami's own tasks: the sitemap's rebuild, publications, the contact Form's
// delivery retries and its 12-month retention. Infomaniak's planner runs them
// through the Tick URL, `php artisan kami:tick:url`.
Kami::schedule();
