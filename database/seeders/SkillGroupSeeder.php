<?php

namespace Database\Seeders;

use App\Models\SkillGroup;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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
                'tags' => ['PHP', 'Laravel', 'Filament', 'Eloquent', 'Blade'],
                'focus' => false,
            ],
            [
                'sort_order' => 2,
                'title' => ['fr' => 'Front-end', 'en' => 'Front-end'],
                'tags' => ['HTML', 'Sass/CSS', 'JavaScript'],
                'focus' => false,
            ],
            [
                'sort_order' => 3,
                'title' => ['fr' => 'Intégration & automatisation', 'en' => 'Integration & automation'],
                'tags' => ['XML', 'FTP', 'CRM·Dataverse', 'SSO·Entra', 'PDF', 'jobs·cron'],
                'focus' => true,
                'note' => ['fr' => 'là où je fais la différence', 'en' => 'where I make the difference'],
            ],
            [
                'sort_order' => 4,
                'title' => ['fr' => 'Bases de données', 'en' => 'Databases'],
                'tags' => ['SQLite', 'MySQL', 'PostgreSQL'],
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

        // The create_skill_groups migration runs this seeder before the tags
        // tables exist; tag sync is skipped there and done by the final seed.
        $canSyncTags = Schema::hasTable('tags');

        foreach ($groups as $data) {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $group = SkillGroup::updateOrCreate(['sort_order' => $data['sort_order']], $data);

            if (! $canSyncTags) {
                continue;
            }

            // sync (not attach) keeps re-seeding idempotent.
            $group->tags()->sync(
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
