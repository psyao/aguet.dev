<?php

use Database\Seeders\HomeSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Velox\Cms\Models\Entry;

uses(TestCase::class)->in('Feature', 'Unit');

uses(TestCase::class)
    ->beforeEach(function () {
        // Browser tests hit the app through a real HTTP request served in-process
        // (LaravelHttpServer/Amp). A transactional RefreshDatabase is not reliably
        // visible across that boundary, so seed a committed, file-backed DB instead.
        // BROWSER_KEEP_DATABASE=1 runs on the database as it is, e.g. a restored production dump.
        if (env('BROWSER_KEEP_DATABASE')) {
            return;
        }

        // The SQLite file is gitignored and absent on a fresh checkout, so create it.
        $db = database_path('testing.sqlite');

        if (! file_exists($db)) {
            touch($db);
        }

        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    })
    ->in('Browser');

/** Set the owner address the contact pipeline delivers to: the Home Page's contact_email, seeded first if need be. */
function setRecipient(?string $email = 'owner@example.com'): void
{
    if (Entry::homePage() === null) {
        (new HomeSeeder)->run();
    }

    $home = Entry::homePage();
    $home->update(['data' => [...$home->data, 'contact_email' => $email]]);
}
