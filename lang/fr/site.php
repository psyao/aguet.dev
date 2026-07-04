<?php

/*
| UI chrome strings (FR). Editorial content (hero, about, projects, contact
| lead) lives in the database and is edited in Filament — not here.
*/
return [

    'meta' => [
        'title' => 'Steve Aguet — Développeur web full-stack Laravel',
        'description' => 'Steve Aguet, développeur web full-stack à dominante back-end, spécialisé Laravel — région lémanique. Intégration & automatisation, plateformes sur mesure.',
    ],

    'skip' => 'Aller au contenu',

    'nav' => [
        'about' => 'À propos',
        'skills' => 'Compétences',
        'projects' => 'Projets',
        'contact' => 'Contact',
    ],

    // Accessible names for each <section> (visually hidden headings).
    'sections' => [
        'hero' => 'Présentation',
        'about' => 'À propos',
        'skills' => 'Compétences & stack',
        'projects' => 'Projets',
        'contact' => 'Contact',
    ],

    'chrome' => [
        'commands' => 'commandes',
        'lang' => 'Langue',
    ],

    'hero' => [
        'cta_projects' => 'Voir mes projets',
        'cta_contact' => 'Me contacter',
    ],

    'skills' => [
        'groups_word' => 'groupes',
        'tech_word' => 'technologies',
    ],

    'projects' => [
        'featured' => 'Projet phare',
        'client' => 'Client',
        'role' => 'Rôle',
        'visit' => 'Visiter',
        'attribution' => 'livrés chez',
    ],

    'contact' => [
        'copy' => 'copier',
        'copied' => 'copié',
        'cta' => 'Écrire un message',
        'copy_email' => 'Copier l’email',
        'copy_linkedin' => 'Copier le lien LinkedIn',
        'copy_github' => 'Copier le lien GitHub',

        // Terminal-prompt contact modal.
        'form' => [
            'title' => 'Écrire un message',
            'intro' => 'Ça arrive direct dans ma boîte — je réponds vite.',
            'subject_label' => 'Sujet',
            'subject_placeholder' => 'Objet de ton message',
            'email_label' => 'Ton email',
            'email_placeholder' => 'toi@exemple.com',
            'message_label' => 'Message',
            'message_placeholder' => 'Dis-moi tout…',
            'send' => 'envoyer',
            'sending' => 'envoi…',
            'success' => 'Message envoyé. Merci — je te réponds bientôt.',
            'another' => 'Écrire un autre message',

            // Barre de progression de la remise (interroge les drapeaux de la ligne).
            'progress' => [
                'email' => 'email',
                'kchat' => 'kchat',
                'state' => [
                    'pending' => 'envoi…',
                    'ok' => 'remis ✓',
                    'fail' => 'échec ✗',
                    'queued' => 'en file …',
                ],
            ],
            'error' => 'Impossible d’enregistrer le message. Réessaie dans un instant.',
            'throttled' => 'Trop de tentatives. Patiente une minute avant de réessayer.',
            'close' => 'Fermer',
            'cancel' => 'annuler',

            // Custom validation messages (Laravel ships no FR validation file).
            'err' => [
                'required' => ':attribute est requis.',
                'email' => 'Entre une adresse email valide.',
                'max' => ':attribute est trop long (:max caractères max).',
            ],
            'attr' => [
                'subject' => 'Le sujet',
                'email' => 'L’email',
                'message' => 'Le message',
            ],
        ],
    ],

    'cmd' => [
        'placeholder' => 'Tape une commande ou cherche…',
        'palette' => 'Palette de commandes',
        'close' => 'Fermer',
        'results' => 'Résultats',
        'nav' => 'Navigation',
        'actions' => 'Actions',
        'lang' => 'Switch to English',
        'email' => 'Écrire un email',
        'linkedin' => 'Ouvrir LinkedIn',
        'github' => 'Ouvrir GitHub',
        'empty' => 'rien',
        'hint_nav' => 'naviguer',
        'hint_open' => 'ouvrir',
        'hint_close' => 'fermer',
        'wq' => '"aguet.dev" écrit 💾 — site statique, rien à sauvegarder',
    ],

    'help' => [
        'motions' => 'j / k — naviguer entre les sections',
        'jumps' => 'gg / G — aller en haut / en bas',
        'excmd' => ':q  :wq  :help — commandes ex',
        'colorscheme' => ':colorscheme — thème : default · gruvbox · nord · crt · light',
        'konami' => '↑↑↓↓←→←→ B A — tu sais quoi faire',
    ],

    'footer' => [
        'note' => 'Conçu et développé par Steve Aguet',
        'switch' => 'Voir le site en anglais',
        'palette' => 'Ouvrir la palette de commandes',
        'commit' => 'Dernier déploiement',
        'repo' => 'voir le dépôt',
    ],

];
