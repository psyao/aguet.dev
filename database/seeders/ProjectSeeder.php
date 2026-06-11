<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * The four real projects (FR from the brief; EN translated).
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
                    'fr' => 'Back-end · équipe de 3',
                    'en' => 'Back-end · team of 3',
                ],
                'summary' => [
                    'fr' => 'Portail institutionnel : SSO Microsoft Entra, synchronisation CRM Dataverse, forte exigence d’accessibilité.',
                    'en' => 'Institutional portal: Microsoft Entra SSO, Dataverse CRM synchronization, strong accessibility requirements.',
                ],
                'tags' => ['Laravel', 'SSO Entra', 'Dataverse', 'a11y'],
                'url' => 'https://cvci.ch',
                'featured' => true,
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'slug' => 'animalia',
                'name' => ['fr' => 'Animalia', 'en' => 'Animalia'],
                'client' => ['fr' => 'Groupe Vaudoise', 'en' => 'Groupe Vaudoise'],
                'role' => [
                    'fr' => 'Seul dev back-end',
                    'en' => 'Sole back-end dev',
                ],
                'summary' => [
                    'fr' => 'Back-end de la première assurance santé animale suisse : logique métier, données, API.',
                    'en' => 'Back-end of Switzerland’s first animal health insurance: business logic, data, API.',
                ],
                'tags' => ['Laravel', 'API'],
                'url' => 'https://animalia.ch',
                'featured' => false,
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'slug' => 'vaud-rando',
                'name' => ['fr' => 'Vaud Rando', 'en' => 'Vaud Rando'],
                'client' => [
                    'fr' => 'Association de randonnée · 2 400+ membres',
                    'en' => 'Hiking association · 2,400+ members',
                ],
                'role' => ['fr' => 'Seul dev', 'en' => 'Sole dev'],
                'summary' => [
                    'fr' => 'Catalogue de randonnées, moteur de recherche, espace membre et back-office sur mesure.',
                    'en' => 'Hike catalog, search engine, member area and a custom back-office.',
                ],
                'tags' => ['Laravel'],
                'url' => 'https://vaud-rando.ch',
                'featured' => false,
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'slug' => 'terre-et-nature',
                'name' => ['fr' => 'Terre & Nature', 'en' => 'Terre & Nature'],
                'client' => ['fr' => 'Hebdomadaire', 'en' => 'Weekly newspaper'],
                'role' => [
                    'fr' => 'Seul dev · intégration',
                    'en' => 'Sole dev · integration',
                ],
                'summary' => [
                    'fr' => 'Pipeline d’ingestion automatisé : flux XML + images via FTP → création d’articles.',
                    'en' => 'Automated ingestion pipeline: XML feeds + images over FTP → article creation.',
                ],
                'tags' => ['XML', 'FTP', 'Laravel'],
                'url' => 'https://terrenature.ch',
                'featured' => false,
                'sort_order' => 4,
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
