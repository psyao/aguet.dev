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
    | Contact coordinates
    |--------------------------------------------------------------------------
    | Shared across the contact section and the command palette. Not editorial
    | (no translation needed), so kept in config rather than the database.
    */
    'contact' => [
        'email' => 'steve@aguet.dev',
        'linkedin' => 'https://www.linkedin.com/in/steveaguet',
        'linkedin_label' => '/in/steveaguet',
        'github' => 'https://github.com/psyao',
        'github_label' => '/psyao',
    ],

];
