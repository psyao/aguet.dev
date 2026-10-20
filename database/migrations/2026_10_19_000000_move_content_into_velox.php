<?php

use App\ContentTypes\Home;
use App\ContentTypes\Project;
use App\ContentTypes\SkillGroup;
use App\Taxonomies\Tag;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Velox\Cms\ContentType;
use Velox\Cms\Models\Entry;
use Velox\Cms\Models\Term;
use Velox\Cms\Role;

return new class extends Migration
{
    /** The page's meta texts, once `site.meta.*` in the lang files, now the Home Page's SEO. */
    private const array SEO = [
        'fr' => [
            'title' => 'Steve Aguet — Développeur web full-stack Laravel',
            'description' => 'Steve Aguet, développeur web full-stack à dominante back-end, spécialisé Laravel — région lémanique. Intégration & automatisation, plateformes sur mesure.',
        ],
        'en' => [
            'title' => 'Steve Aguet — Full-stack Laravel web developer',
            'description' => 'Steve Aguet, full-stack web developer with a back-end focus, Laravel specialist — Lake Geneva region. Integration & automation, custom platforms.',
        ],
    ];

    /**
     * Moves the content of aguet's former models into Velox, once, on the deploy that switches over: the Tags, the
     * Projects and SkillGroups (French Origins with English Translations, in hand order, classified in pivot order), the
     * singleton as the Home Page, and every user a Developer. Through the query builder for the old tables, whose models
     * are gone. All of it or nothing: a count that doesn't match throws, failing the deploy. The old tables stay.
     */
    public function up(): void
    {
        // A fresh database has nothing to move: return before touching a model, so a later rename can't break `migrate`.
        if (collect(['site_contents', 'projects', 'skill_groups', 'tags'])->every(fn (string $table): bool => DB::table($table)->doesntExist())) {
            return;
        }

        DB::transaction(function (): void {
            $terms = DB::table('tags')->orderBy('id')->get()->mapWithKeys(fn (object $tag): array => [
                $tag->id => Term::create(['taxonomy' => (new Tag)->handle(), 'name' => $tag->name])->id,
            ]);

            // Keyed by the morph type and id the old pivot stores.
            $entries = [];

            foreach (DB::table('projects')->orderBy('sort_order')->orderBy('id')->get() as $row) {
                $entries["App\\Models\\Project#{$row->id}"] = $this->entry(
                    new Project,
                    $this->texts(['title' => $row->name, 'client' => $row->client, 'role' => $row->role, 'summary' => $row->summary]),
                    ['url' => $row->url, 'featured' => (bool) $row->featured],
                    $row->is_published ? 'published' : 'draft',
                    ['slug' => $row->slug],
                );
            }

            foreach (DB::table('skill_groups')->orderBy('sort_order')->orderBy('id')->get() as $row) {
                $entries["App\\Models\\SkillGroup#{$row->id}"] = $this->entry(
                    new SkillGroup,
                    $this->texts(['title' => $row->title, 'text' => $row->text, 'note' => $row->note]),
                    ['focus' => (bool) $row->focus],
                    'published',
                );
            }

            // The old models left a deleted project's or group's pivot rows behind: dead, so left out.
            $pivots = DB::table('taggables')->orderBy('position')->orderBy('id')->get()
                ->filter(fn (object $pivot): bool => isset($entries["{$pivot->taggable_type}#{$pivot->taggable_id}"]));
            $pivots
                ->groupBy(fn (object $pivot): string => "{$pivot->taggable_type}#{$pivot->taggable_id}")
                ->each(fn ($owned, string $key) => $entries[$key]->classify(Tag::class, $owned->map(fn (object $pivot): int => $terms[$pivot->tag_id])->all()));

            if (($row = DB::table('site_contents')->orderBy('id')->first()) !== null) {
                $texts = $this->texts([
                    'title' => json_encode(['fr' => 'Accueil', 'en' => 'Home']),
                    ...collect((array) $row)->only(['hero_title', 'hero_subtitle', 'hero_role', 'hero_location', 'hero_exp', 'hero_focus', 'about_body', 'contact_lead']),
                ]);
                $texts['about_body'] = array_map(
                    fn (?string $markdown): ?array => $markdown === null ? null : RichContentRenderer::make(Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]))->toArray(),
                    $texts['about_body'],
                );

                Entry::setHomePage($this->entry(
                    new Home,
                    $texts,
                    collect((array) $row)->only(['contact_email', 'contact_linkedin', 'contact_linkedin_label', 'contact_github', 'contact_github_label'])->all(),
                    'published',
                    ['seo' => self::SEO['fr']],
                    ['seo' => self::SEO['en']],
                ));
            }

            DB::table('users')->update(['role' => Role::Developer->value]);

            $this->check([
                'Project Origins' => [DB::table('projects')->count(), Entry::ofType(new Project)->whereNull('origin_id')->count()],
                'Project Translations' => [DB::table('projects')->count(), Entry::ofType(new Project)->whereNotNull('origin_id')->count()],
                'SkillGroup Origins' => [DB::table('skill_groups')->count(), Entry::ofType(new SkillGroup)->whereNull('origin_id')->count()],
                'SkillGroup Translations' => [DB::table('skill_groups')->count(), Entry::ofType(new SkillGroup)->whereNotNull('origin_id')->count()],
                'classifications' => [$pivots->count(), DB::table('velox_entry_term')->count()],
                'Home Page' => [min(1, DB::table('site_contents')->count()), Entry::homePage() === null ? 0 : 1],
            ]);
        });
    }

    public function down(): void
    {
        // One-way move; the old tables stay, which a revert PR reads.
    }

    /**
     * Each spatie JSON column's French value, and its English one, a blank one taking the French: Velox has no per-Field fallback.
     *
     * @param  array<string, ?string>  $columns
     * @return array<string, array{fr: ?string, en: ?string}>
     */
    private function texts(array $columns): array
    {
        return array_map(function (?string $json): array {
            $values = json_decode($json ?? '[]', true) ?: [];
            $fr = filled($values['fr'] ?? null) ? $values['fr'] : null;

            return ['fr' => $fr, 'en' => filled($values['en'] ?? null) ? $values['en'] : $fr];
        }, $columns);
    }

    /**
     * Saves a French Origin, last in its type's hand order, with its English Translation.
     *
     * @param  array<string, array{fr: mixed, en: mixed}>  $texts  the title, then the translatable Fields
     * @param  array<string, mixed>  $shared  the untranslated Fields, on the Origin only
     * @param  array<string, mixed>  $origin  the Origin's other attributes
     * @param  array<string, mixed>  $translation  the Translation's other attributes
     */
    private function entry(ContentType $type, array $texts, array $shared, string $status, array $origin = [], array $translation = []): Entry
    {
        $publication = ['status' => $status, 'published_at' => $status === 'published' ? now() : null];
        $in = fn (string $locale): array => array_map(fn (array $text): mixed => $text[$locale], Arr::except($texts, 'title'));

        $entry = Entry::newOfType($type)->fill([...$origin, ...$publication, 'title' => $texts['title']['fr'], 'data' => [...$in('fr'), ...$shared]]);
        $entry->save();
        $entry->translate('en', [...$translation, ...$publication, 'title' => $texts['title']['en'], 'data' => $in('en')]);

        return $entry;
    }

    /** @param  array<string, array{int, int}>  $counts  what each source holds, and what Velox now holds of it */
    private function check(array $counts): void
    {
        foreach ($counts as $what => [$expected, $moved]) {
            if ($expected !== $moved) {
                throw new RuntimeException("aguet's content move expected {$expected} {$what}, Velox holds {$moved}: nothing moved.");
            }
        }
    }
};
