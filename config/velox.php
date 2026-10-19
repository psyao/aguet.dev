<?php

use App\ContentTypes\Home;

return [

    /*
     * The Content Types of this Site: Content Type classes, each extending
     * Velox\Cms\ContentType. `php artisan velox:make:content-type` scaffolds one.
     */
    'content_types' => [
        Home::class,
    ],

    /*
     * The Menus of this Site, the slots of its layout that Writers fill in the
     * Admin: handle => label and maximum depth (1 to 3, 1 by default).
     * Render one with Velox::menu('primary').
     */
    'menus' => [],

    /*
     * The Locales this Site publishes in: code => native name, such as
     * ['fr' => 'Français', 'de' => 'Deutsch']. The first is the default,
     * served unprefixed; fewer than two leave the Site monolingual.
     */
    'locales' => [
        'fr' => 'Français',
        'en' => 'English',
    ],

];
