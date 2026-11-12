<?php

use App\ContentTypes\Home;
use App\ContentTypes\Project;
use App\ContentTypes\SkillGroup;
use App\Forms\ContactForm;
use App\Taxonomies\Tag;

/*
| Kami's settings aguet.dev changes; every other key keeps the package's
| default (vendor/psyao/kami/config/kami.php).
*/
return [

    'types' => [
        Home::class,
        Project::class,
        SkillGroup::class,
        Tag::class,
    ],

    'forms' => [
        ContactForm::class,
    ],

    /*
    | French at "/", English at "/en". The regions give `og:locale` fr_CH and en_GB.
    */
    'sites' => [
        'default' => [
            'url' => env('APP_URL', 'http://localhost'),
            'locales' => ['fr' => ['region' => 'CH'], 'en' => ['region' => 'GB']],
            'timezone' => 'Europe/Zurich',
        ],
    ],

    /*
    | Added to Kami's front policy, never replacing it. Cloudflare Web Analytics
    | loads its beacon from one origin and reports to another. The hashes allow
    | the layout's inline scripts that must run before paint, and the testing-only
    | screenshot style; HomePageTest fails when one changes without its hash.
    */
    'security' => [
        'csp' => [
            'script-src' => [
                'https://static.cloudflareinsights.com',
                // layouts/app: the boot reveal, the saved colorscheme, and the screenshot mode's fonts-ready flag.
                "'sha256-N2mGtMbi4/m0TMPn5CUM46YPYuk47P9OYy1mwmg/Puk='",
                "'sha256-07p7upQ2Qr1HGkeCxbF9MZH6jU/sNwT7ywfkgnK5P44='",
                "'sha256-rYrxRmCDayU90CtSRPVY1Kb25P7YGNlONr8yDwo+aX4='",
            ],
            'style-src' => [
                // errors/_shell: the self-contained error pages' style.
                "'sha256-rzr52lOF6Bs+HTygCsdNROMC1xnErgwq//arbJ+F8+s='",
                // Testing only: layouts/app's screenshot mode, and the style Pest's assertScreenshotMatches() injects.
                ...env('APP_ENV') === 'testing' ? [
                    "'sha256-A7X7Jry5KzXKgFtgYO7MQDUrbW0nDuEfrhPA7DagOQQ='",
                    "'sha256-GNkWZZqVyG1KGT0K/M6LYfaxytPEGR21QVTdGd1Kbqo='",
                ] : [],
            ],
            'connect-src' => ['https://cloudflareinsights.com'],
        ],
        'headers' => [],
        'csp_report_uri' => null,
        'csp_report_only' => false,
    ],

];
