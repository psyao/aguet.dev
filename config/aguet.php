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

];
