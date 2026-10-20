<?php

namespace Database\Seeders;

use App\ContentTypes\SkillGroup;
use App\Taxonomies\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Velox\Cms\Models\Entry;
use Velox\Cms\Models\Term;

class SkillGroupSeeder extends Seeder
{
    /**
     * The five stack groups (previously config/skills.php + lang files), each
     * a French Origin with its English Translation, in hand order. Skipped
     * once SkillGroups exist.
     */
    public function run(): void
    {
        if (Entry::ofType(new SkillGroup)->exists()) {
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

        foreach ($groups as $data) {
            $translated = Arr::only($data, ['text', 'note']);

            $group = Entry::create([
                'type' => (new SkillGroup)->handle(),
                'title' => $data['title']['fr'],
                'status' => 'published',
                'published_at' => now(),
                'data' => [...array_map(fn (array $value) => $value['fr'], $translated), 'focus' => $data['focus']],
            ]);

            $group->translate('en', [
                'title' => $data['title']['en'],
                'status' => 'published',
                'published_at' => now(),
                'data' => array_map(fn (array $value) => $value['en'], $translated),
            ]);

            $group->classify(Tag::class, array_map(
                fn (string $name) => Term::firstOrCreate(['taxonomy' => (new Tag)->handle(), 'name' => $name]),
                $data['tags'] ?? [],
            ));
        }
    }
}
