<?php

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

uses(TestCase::class)
    ->beforeEach(function () {
        // Browser tests hit the app through a real HTTP request served in-process
        // (LaravelHttpServer/Amp). A transactional RefreshDatabase is not reliably
        // visible across that boundary, so seed a committed, file-backed DB instead.
        // The SQLite file is gitignored and absent on a fresh checkout, so create it.
        $db = database_path('testing.sqlite');

        if (! file_exists($db)) {
            touch($db);
        }

        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    })
    ->in('Browser');
