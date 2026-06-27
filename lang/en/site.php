<?php

/*
| UI chrome strings (EN). Editorial content (hero, about, projects, contact
| lead) lives in the database and is edited in Filament — not here.
*/
return [

    'meta' => [
        'title' => 'Steve Aguet — Full-stack Laravel web developer',
        'description' => 'Steve Aguet, full-stack web developer with a back-end focus, Laravel specialist — Lake Geneva region. Integration & automation, custom platforms.',
    ],

    'skip' => 'Skip to content',

    'nav' => [
        'about' => 'About',
        'skills' => 'Skills',
        'projects' => 'Projects',
        'contact' => 'Contact',
    ],

    'sections' => [
        'hero' => 'Introduction',
        'about' => 'About',
        'skills' => 'Skills & stack',
        'projects' => 'Projects',
        'contact' => 'Contact',
    ],

    'chrome' => [
        'commands' => 'commands',
    ],

    'hero' => [
        'cta_projects' => 'View my projects',
        'cta_contact' => 'Get in touch',
    ],

    'skills' => [
        'groups_word' => 'groups',
        'tech_word' => 'technologies',
    ],

    'projects' => [
        'featured' => 'Flagship project',
        'client' => 'Client',
        'role' => 'Role',
        'visit' => 'Visit',
    ],

    'contact' => [
        'copy' => 'copy',
        'copied' => 'copied',
        'cta' => 'Send a message',

        // Terminal-prompt contact modal.
        'form' => [
            'title' => 'Send a message',
            'intro' => 'It lands straight in my inbox — I reply quickly.',
            'subject_label' => 'Subject',
            'subject_placeholder' => 'What’s it about?',
            'email_label' => 'Your email',
            'email_placeholder' => 'you@example.com',
            'message_label' => 'Message',
            'message_placeholder' => 'Tell me everything…',
            'send' => 'send',
            'sending' => 'sending…',
            'success' => 'Message sent. Thanks — I’ll get back to you soon.',
            'another' => 'Write another message',
            'error' => 'Couldn’t save your message. Please try again in a moment.',
            'throttled' => 'Too many attempts. Wait a minute before trying again.',
            'close' => 'Close',
            'cancel' => 'cancel',

            // Custom validation messages (kept symmetric with the FR file).
            'err' => [
                'required' => ':attribute is required.',
                'email' => 'Enter a valid email address.',
                'max' => ':attribute is too long (:max characters max).',
            ],
            'attr' => [
                'subject' => 'The subject',
                'email' => 'The email',
                'message' => 'The message',
            ],
        ],
    ],

    'cmd' => [
        'placeholder' => 'Type a command or search…',
        'nav' => 'Navigation',
        'actions' => 'Actions',
        'lang' => 'Passer en français',
        'email' => 'Send an email',
        'linkedin' => 'Open LinkedIn',
        'github' => 'Open GitHub',
        'empty' => 'nothing',
        'hint_nav' => 'navigate',
        'hint_open' => 'open',
        'hint_close' => 'close',
    ],

    'footer' => [
        'note' => 'Designed and built by Steve Aguet',
        'switch' => 'View the site in French',
        'palette' => 'Open the command palette',
        'commit' => 'Latest deployment',
        'repo' => 'view repository',
    ],

];
