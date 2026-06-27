<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * The real projects (FR authored; EN translated), snapshot of prod content.
     * CVCI is the flagship (featured). Order: featured first, then sort_order.
     */
    public function run(): void
    {
        $projects = [
            [
                'slug' => 'cvci',
                'name' => ['fr' => 'CVCI', 'en' => 'CVCI'],
                'client' => [
                    'fr' => 'Chambre vaudoise du commerce et de l’industrie',
                    'en' => 'Vaud Chamber of Commerce and Industry',
                ],
                'role' => [
                    'fr' => 'Full stack / Backend heavy',
                    'en' => 'Full stack / Back-end heavy',
                ],
                'summary' => [
                    'fr' => 'Portail institutionnel, CMS et espace membres pour la CVCI, avec synchronisation bidirectionnelle au CRM Microsoft Dynamics.',
                    'en' => 'Institutional portal, CMS and member area for the CVCI, with two-way Microsoft Dynamics CRM synchronisation.',
                ],
                'tags' => ['Laravel', 'MS Entra', 'MS Graph', 'Livewire', 'Blade', 'Filament', 'Tailwind CSS'],
                'url' => 'https://cvci.ch',
                'featured' => true,
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'slug' => 'animalia',
                'name' => ['fr' => 'Animalia', 'en' => 'Animalia'],
                'client' => ['fr' => 'Vaudoise Assurance', 'en' => 'Vaudoise Assurance'],
                'role' => ['fr' => 'Backend', 'en' => 'Backend'],
                'summary' => [
                    'fr' => 'Back-end de la première assurance santé animale suisse : devis, contrats, sinistres, exposés via API.',
                    'en' => 'Back-end of Switzerland\'s first pet health insurance: quotes, contracts, claims, exposed through APIs.',
                ],
                'tags' => ['Laravel', 'API', 'AWS', 'Laravel Nova'],
                'url' => 'https://animalia.ch',
                'featured' => false,
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'slug' => 'atr-booking',
                'name' => ['fr' => 'ATR Booking', 'en' => 'ATR Booking'],
                'client' => ['fr' => 'After The Rain', 'en' => 'After The Rain'],
                'role' => ['fr' => 'Backend', 'en' => 'Back-end'],
                'summary' => [
                    'fr' => 'Système de réservation et de planification pour un centre de soins : agendas, prestations, salles, bons cadeaux et abonnements.',
                    'en' => 'Booking and scheduling system for a care centre: calendars, services, rooms, gift vouchers and subscriptions.',
                ],
                'tags' => ['Laravel', 'Filament', 'FullCalendar'],
                'featured' => false,
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'slug' => 'cvci-125',
                'name' => ['fr' => 'CVCI 125 ans', 'en' => 'CVCI 125 ans'],
                'client' => [
                    'fr' => 'Chambre vaudoise du commerce et de l\'industrie',
                    'en' => 'Chambre vaudoise du commerce et de l\'industrie',
                ],
                'role' => [
                    'fr' => 'Backend + Frontend',
                    'en' => 'Back-end + Front-end',
                ],
                'summary' => [
                    'fr' => 'Quiz interactif sur place pour les 125 ans de la CVCI : check-in par QR code, questions et lots.',
                    'en' => 'On-site interactive quiz for the CVCI\'s 125th anniversary: QR-code check-in, questions and prizes.',
                ],
                'tags' => ['Laravel', 'Blade', 'Tailwind CSS', 'VueJS'],
                'featured' => false,
                'sort_order' => 4,
                'is_published' => true,
            ],
            [
                'slug' => 'montreux-noel-2023',
                'name' => ['fr' => 'Montreux Noël 2023', 'en' => 'Montreux Noël 2023'],
                'client' => ['fr' => 'Montreux Noël', 'en' => 'Montreux Noël'],
                'role' => ['fr' => 'Fullstack', 'en' => 'Frontend + Backend'],
                'summary' => [
                    'fr' => 'Microsite jeu-concours du marché de Noël de Montreux 2023 : check-in par QR code, tirages au sort et lots des partenaires.',
                    'en' => 'Prize-game microsite for the 2023 Montreux Christmas market: QR-code check-in, prize draws and partner prizes.',
                ],
                'tags' => ['Laravel', 'Filament', 'Livewire', 'Tailwind CSS'],
                'featured' => false,
                'sort_order' => 5,
                'is_published' => true,
            ],
            [
                'slug' => 'terre-et-nature',
                'name' => ['fr' => 'Terre & Nature', 'en' => 'Terre & Nature'],
                'client' => ['fr' => 'Terre & Nature', 'en' => 'Weekly newspaper'],
                'role' => ['fr' => 'Frontend + Backend', 'en' => 'Frontend + Backend'],
                'summary' => [
                    'fr' => 'Digitalisation du magazine Terre & Nature avec import XML automatisé des contenus print.',
                    'en' => 'Digitisation of the Terre & Nature magazine with automated XML import of print content.',
                ],
                'tags' => ['WordPress', 'XML', 'Digitization', 'Blade'],
                'url' => 'https://terrenature.ch',
                'featured' => false,
                'sort_order' => 6,
                'is_published' => true,
            ],
            [
                'slug' => 'vaud-rando',
                'name' => ['fr' => 'Vaud Rando', 'en' => 'Vaud Rando'],
                'client' => ['fr' => 'Vaud Rando', 'en' => 'Vaud Rando'],
                'role' => ['fr' => 'Frontend + Backend', 'en' => 'Frontend + Backend'],
                'summary' => [
                    'fr' => 'Plateforme associative pour Vaud Rando : catalogue de randonnées, inscriptions, espace membre et gestion des bénévoles (chefs de course et baliseurs).',
                    'en' => 'Association platform for Vaud Rando: hiking catalogue, registrations, member area and volunteer management (hike leaders and trail markers).',
                ],
                'tags' => ['Laravel', 'Livewire', 'Blade', 'Filament', 'Tailwind CSS'],
                'url' => 'https://vaud-rando.ch',
                'featured' => false,
                'sort_order' => 7,
                'is_published' => true,
            ],
        ];

        foreach ($projects as $data) {
            $tags = $data['tags'];
            unset($data['tags']);

            $project = Project::updateOrCreate(['slug' => $data['slug']], $data);

            // sync (not attach) keeps re-seeding idempotent.
            $project->tags()->sync(
                collect($tags)
                    ->values()
                    ->mapWithKeys(fn (string $name, int $index) => [
                        Tag::firstOrCreate(['name' => $name])->id => ['position' => $index],
                    ])
                    ->all(),
            );
        }
    }
}
