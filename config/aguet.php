<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filament admin user
    |--------------------------------------------------------------------------
    | Seeded by AdminUserSeeder. Never commit real credentials — these come
    | from the environment.
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    | The default locale lives at "/", the others under their own URL prefix.
    */
    'locales' => ['fr', 'en'],
    'default_locale' => 'fr',

    /*
    |--------------------------------------------------------------------------
    | Source repository
    |--------------------------------------------------------------------------
    | Trusted constant (never DB content) used to build the footer commit/repo
    | links. Override per-environment with AGUET_REPO_URL if the repo moves.
    */
    'repo_url' => env('AGUET_REPO_URL', 'https://github.com/psyao/aguet.dev'),

    /*
    |--------------------------------------------------------------------------
    | Cron trigger token
    |--------------------------------------------------------------------------
    | Shared secret for the Infomaniak HTTP cron trigger (GET /cron/{token}).
    | Staging and production each hold an independent value in Doppler — see
    | docs/howto-rotate-cron-token.md to generate/rotate one. `--force` on
    | `cron:token` is a local-dev-only convenience; it is not the production
    | rotation path.
    */
    'cron' => [
        'token' => env('CRON_TOKEN'),
    ],

];
