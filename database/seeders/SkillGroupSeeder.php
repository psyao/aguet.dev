<?php

namespace Database\Seeders;

use App\Models\SkillGroup;
use Illuminate\Database\Seeder;

class SkillGroupSeeder extends Seeder
{
    /**
     * The five stack groups (previously config/skills.php + lang files).
     * Idempotent: keyed on sort_order, so re-seeding never duplicates.
     */
    public function run(): void
    {
        $groups = [
            [
                'sort_order' => 1,
                'title' => ['fr' => 'Cœur Laravel', 'en' => 'Laravel core'],
                'items' => ['PHP', 'Laravel', 'Filament', 'Eloquent', 'Blade'],
                'focus' => false,
            ],
            [
                'sort_order' => 2,
                'title' => ['fr' => 'Front-end', 'en' => 'Front-end'],
                'items' => ['HTML', 'Sass/CSS', 'JavaScript'],
                'focus' => false,
            ],
            [
                'sort_order' => 3,
                'title' => ['fr' => 'Intégration & automatisation', 'en' => 'Integration & automation'],
                'items' => ['XML', 'FTP', 'CRM·Dataverse', 'SSO·Entra', 'PDF', 'jobs·cron'],
                'focus' => true,
                'note' => ['fr' => 'là où je fais la différence', 'en' => 'where I make the difference'],
            ],
            [
                'sort_order' => 4,
                'title' => ['fr' => 'Bases de données', 'en' => 'Databases'],
                'items' => ['SQLite', 'MySQL', 'PostgreSQL'],
                'focus' => false,
            ],
            [
                'sort_order' => 5,
                'title' => ['fr' => 'Langues', 'en' => 'Languages'],
                'text' => [
                    'fr' => 'FR (natif) · EN (pro) · DE (notions)',
                    'en' => 'FR (native) · EN (professional) · DE (basics)',
                ],
                'focus' => false,
            ],
        ];

        foreach ($groups as $data) {
            SkillGroup::updateOrCreate(['sort_order' => $data['sort_order']], $data);
        }
    }
}
