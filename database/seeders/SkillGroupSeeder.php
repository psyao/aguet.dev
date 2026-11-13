<?php

namespace Database\Seeders;

use App\ContentTypes\SkillGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Psyao\Kami\Content\Actions\SaveEntry;
use Psyao\Kami\Content\Entry;

class SkillGroupSeeder extends Seeder
{
    /**
     * The five stack groups (previously config/skills.php + lang files), each
     * in French and English, in hand order. Skipped
     * once SkillGroups exist.
     */
    public function run(): void
    {
        if (Entry::query()->withUnpublished()->ofType(SkillGroup::class)->exists()) {
            return;
        }

        $groups = [
            [
                'title' => ['fr' => 'Cœur Laravel', 'en' => 'Laravel core'],
                'tags' => ['PHP', 'Laravel', 'Filament', 'Eloquent', 'Blade', 'Livewire'],
                'focus' => false,
            ],
            [
                'title' => ['fr' => 'Intégration & automatisation', 'en' => 'Integration & automation'],
                'tags' => ['XML', 'FTP', 'MS Graph', 'MS Entra'],
                'focus' => true,
                'note' => ['fr' => 'là où je fais la différence', 'en' => 'where I make the difference'],
            ],
            [
                'title' => ['fr' => 'Bases de données', 'en' => 'Databases'],
                'tags' => ['SQLite', 'MySQL', 'PostgreSQL'],
                'focus' => false,
            ],
            [
                'title' => ['fr' => 'Frontend', 'en' => 'Front-end'],
                'tags' => ['HTML', 'Sass/CSS', 'JavaScript'],
                'focus' => false,
            ],
            [
                'title' => ['fr' => 'Langues', 'en' => 'Languages'],
                'text' => [
                    'fr' => 'FR (natif) · EN (pro) · DE (notions)',
                    'en' => 'FR (native) · EN (professional) · DE (basics)',
                ],
                'focus' => false,
            ],
        ];

        $save = resolve(SaveEntry::class);

        foreach ($groups as $data) {
            $translated = Arr::only($data, ['text', 'note']);

            $group = $save->handle(SkillGroup::class, [
                'title' => $data['title']['fr'],
                'status' => 'published',
                ...array_map(fn (array $value) => $value['fr'], $translated),
                'focus' => $data['focus'],
                'tags' => ProjectSeeder::tagIds($data['tags'] ?? []),
            ], locale: 'fr');

            $save->handle(SkillGroup::class, [
                'title' => $data['title']['en'],
                'status' => 'published',
                ...array_map(fn (array $value) => $value['en'], $translated),
            ], $group->refresh(), 'en');
        }
    }
}
