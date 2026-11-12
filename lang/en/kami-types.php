<?php

$inlineMarkdown = '*word* = green accent · **word** = bold.';

return [
    'home' => [
        'label' => 'Home page',
        'plural' => 'Home',
        'fields' => [
            'hero_title' => ['label' => 'Title', 'help' => $inlineMarkdown.' A newline = a line break.'],
            'hero_subtitle' => ['label' => 'Subtitle', 'help' => $inlineMarkdown],
            'hero_role' => ['label' => 'Role (« whoami » line)'],
            'hero_location' => ['label' => 'Location'],
            'hero_exp' => ['label' => 'Experience'],
            'hero_focus' => ['label' => 'Focus'],
            'about_body' => ['label' => 'About'],
            'contact_lead' => ['label' => 'Contact lead', 'help' => $inlineMarkdown],
            'contact_email' => ['label' => 'Contact email', 'help' => 'Receives the contact form’s messages.'],
            'contact_linkedin' => ['label' => 'LinkedIn (URL)'],
            'contact_linkedin_label' => ['label' => 'LinkedIn (label shown)'],
            'contact_github' => ['label' => 'GitHub (URL)'],
            'contact_github_label' => ['label' => 'GitHub (label shown)'],
        ],
    ],
    'project' => [
        'label' => 'Project',
        'plural' => 'Projects',
        'fields' => [
            'client' => ['label' => 'Client'],
            'role' => ['label' => 'Role'],
            'summary' => ['label' => 'Summary', 'help' => $inlineMarkdown],
            'website' => ['label' => 'Website'],
            'featured' => ['label' => 'Featured project', 'help' => 'Shown first, full width.'],
            'directory' => ['label' => 'Directory (~/projects/…)', 'help' => 'The card’s legend, the same in every language.'],
            'tags' => ['label' => 'Tags'],
        ],
    ],
    'skill_group' => [
        'label' => 'Skill group',
        'plural' => 'Skills',
        'fields' => [
            'text' => ['label' => 'Text (instead of tags)', 'help' => 'When filled, replaces the tags — e.g. the « Languages » group.'],
            'note' => ['label' => 'Note', 'help' => 'Shown in the tree’s footer for the ★ group.'],
            'focus' => ['label' => '★ group (focus)', 'help' => 'Highlighted in the tree and repeated in the footer.'],
            'tags' => ['label' => 'Tags'],
        ],
    ],
    'tag' => [
        'label' => 'Tag',
        'plural' => 'Tags',
    ],
];
