<?php

/*
| Skills are static content (the brief's data model has no Skill model), so
| they live here rather than in the database. Group titles are translated via
| lang/{locale}/site.php (the `title_key`); the tech tags are language-neutral.
| The "languages" group renders a single translated string instead of tags.
*/
return [

    'groups' => [
        [
            'title_key' => 'site.skills.g1',
            'items' => ['PHP', 'Laravel', 'Filament', 'Eloquent', 'Blade'],
            'focus' => false,
        ],
        [
            'title_key' => 'site.skills.g2',
            'items' => ['HTML', 'Sass/CSS', 'JavaScript'],
            'focus' => false,
        ],
        [
            'title_key' => 'site.skills.g3',
            'items' => ['XML', 'FTP', 'CRM·Dataverse', 'SSO·Entra', 'PDF', 'jobs·cron'],
            'focus' => true,
            'note_key' => 'site.skills.g3_note',
        ],
        [
            'title_key' => 'site.skills.g4',
            'items' => ['SQLite', 'MySQL', 'PostgreSQL'],
            'focus' => false,
        ],
        [
            'title_key' => 'site.skills.g5',
            'items_key' => 'site.skills.g5_items', // single translated string, not tags
            'focus' => false,
        ],
    ],

];
