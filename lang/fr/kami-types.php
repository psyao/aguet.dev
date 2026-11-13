<?php

$inlineMarkdown = '*mot* = accent vert · **mot** = gras.';

return [
    'home' => [
        'label' => 'Page d’accueil',
        'plural' => 'Accueil',
        'fields' => [
            'hero_title' => ['label' => 'Titre', 'help' => $inlineMarkdown.' Un retour à la ligne = saut de ligne.'],
            'hero_subtitle' => ['label' => 'Sous-titre', 'help' => $inlineMarkdown],
            'hero_role' => ['label' => 'Rôle (ligne « whoami »)'],
            'hero_location' => ['label' => 'Localisation'],
            'hero_exp' => ['label' => 'Expérience'],
            'hero_focus' => ['label' => 'Focus'],
            'about_body' => ['label' => 'À propos'],
            'contact_lead' => ['label' => 'Accroche du contact', 'help' => $inlineMarkdown],
            'contact_email' => ['label' => 'Email de contact', 'help' => 'Reçoit les messages du formulaire de contact.'],
            'contact_linkedin' => ['label' => 'LinkedIn (URL)'],
            'contact_linkedin_label' => ['label' => 'LinkedIn (libellé affiché)'],
            'contact_github' => ['label' => 'GitHub (URL)'],
            'contact_github_label' => ['label' => 'GitHub (libellé affiché)'],
        ],
    ],
    'project' => [
        'label' => 'Projet',
        'plural' => 'Projets',
        'fields' => [
            'client' => ['label' => 'Client'],
            'role' => ['label' => 'Rôle'],
            'summary' => ['label' => 'Résumé', 'help' => $inlineMarkdown],
            'website' => ['label' => 'URL du site'],
            'featured' => ['label' => 'Projet phare', 'help' => 'Affiché en premier, en pleine largeur.'],
            'directory' => ['label' => 'Dossier (~/projects/…)', 'help' => 'La légende de la carte, la même dans chaque langue.'],
            'tags' => ['label' => 'Tags'],
        ],
    ],
    'skill_group' => [
        'label' => 'Groupe de compétences',
        'plural' => 'Compétences',
        'fields' => [
            'text' => ['label' => 'Texte (au lieu des tags)', 'help' => 'Si rempli, remplace les tags — ex. le groupe « Langues ».'],
            'note' => ['label' => 'Note', 'help' => 'Affichée dans le pied de l’arbre pour le groupe ★.'],
            'focus' => ['label' => 'Groupe ★ (focus)', 'help' => 'Mis en avant dans l’arbre et repris dans le pied.'],
            'tags' => ['label' => 'Tags'],
        ],
    ],
    'tag' => [
        'label' => 'Tag',
        'plural' => 'Tags',
    ],
];
