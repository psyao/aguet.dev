<?php

namespace Tests\Feature;

use App\ContentTypes\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;
use Velox\Cms\Models\Entry;
use Velox\Cms\Models\Term;
use Velox\Cms\Role;

class MoveContentIntoVeloxTest extends TestCase
{
    use RefreshDatabase;

    /** When a former row was created and last edited. */
    private const array FORMER_DATES = ['created_at' => '2026-06-09 20:07:21', 'updated_at' => '2026-06-26 18:29:41'];

    public function test_the_page_renders_the_moved_content_in_both_languages(): void
    {
        $this->fillOldTables();
        $this->move();

        $this->get('/')->assertOk()
            ->assertSee('Développeur <em>PHP</em>,<br>', false)
            ->assertSee('<p>Premier paragraphe.</p>', false)
            ->assertSee('<p>Second paragraphe.</p>', false)
            ->assertSee('Une accroche ?', false)
            ->assertSee('/in/someone', false)
            ->assertSeeInOrder([$this->legend('atlas'), $this->legend('borealis')], false)
            ->assertDontSee($this->legend('cachee'), false)
            ->assertSee('Client Atlas', false)
            ->assertSee('<span>Filament</span><span>Laravel</span>', false)
            ->assertSeeInOrder(['Cœur', 'Langues'], false)
            ->assertSee('<span>FR · EN</span>', false)
            ->assertSee('<title>Steve Aguet — Développeur web full-stack Laravel</title>', false);

        $this->get('/en')->assertOk()
            ->assertSee('<em>PHP</em> developer,<br>', false)
            ->assertSee('<p>First paragraph.</p>', false)
            ->assertSee('<p>Second paragraph.</p>', false)
            ->assertSee('A lead?', false)
            ->assertSee('/in/someone', false)
            ->assertSeeInOrder([$this->legend('atlas'), $this->legend('borealis')], false)
            ->assertDontSee($this->legend('cachee'), false)
            ->assertSee('Client Atlas', false) // blank in English: the French one
            ->assertSee('Borealis client', false)
            ->assertSee('<span>Filament</span><span>Laravel</span>', false)
            ->assertSeeInOrder(['Core', 'Languages'], false)
            ->assertSee('<title>Steve Aguet — Full-stack Laravel web developer</title>', false);

        $this->assertSame('Accueil', Entry::homePage('fr')?->title);
        $this->assertSame('Home', Entry::homePage('en')?->title);
    }

    public function test_every_user_becomes_a_developer(): void
    {
        $this->fillOldTables();
        DB::table('users')->insert(['name' => 'Other', 'email' => 'other@example.com', 'password' => bcrypt('secret')]);

        $this->move();

        $this->assertSame([Role::Developer->value, Role::Developer->value], User::pluck('role')->all());
    }

    public function test_a_deleted_projects_pivot_rows_are_left_out(): void
    {
        $this->fillOldTables();
        // The old models never detached a deleted project's Tags.
        DB::table('taggables')->insert(['tag_id' => DB::table('tags')->value('id'), 'taggable_type' => 'App\\Models\\Project', 'taggable_id' => 999, 'position' => 0]);

        $this->move();

        $this->assertSame(3, DB::table('velox_entry_term')->count());
    }

    public function test_a_count_mismatch_writes_nothing(): void
    {
        $this->fillOldTables();
        // A Project already in Velox: one Origin more than the old table holds.
        $existing = Entry::newOfType(new Project)->fill(['title' => 'Déjà là', 'status' => 'draft']);
        $existing->save();

        try {
            $this->move();
            $this->fail('The move should have thrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Project Origins', $exception->getMessage());
        }

        $this->assertSame([$existing->id], Entry::withTrashed()->pluck('id')->all());
        $this->assertSame(0, Term::count());
        $this->assertNull(Entry::homePage());
        $this->assertSame([null], DB::table('users')->pluck('role')->all());
    }

    public function test_the_moved_content_passes_the_fields_check(): void
    {
        $this->fillOldTables();
        $this->move();

        $this->artisan('velox:fields:check')->assertSuccessful();
    }

    public function test_the_moved_entries_keep_their_former_dates(): void
    {
        $this->fillOldTables();
        $this->move();

        foreach ([['project', 'Atlas'], ['skill-group', 'Cœur'], ['home', 'Accueil']] as [$type, $title]) {
            $this->assertSame(
                array_fill(0, 2, ['2026-06-09 20:07:21', '2026-06-26 18:29:41', '2026-06-09 20:07:21']),
                $this->datesOf($type, $title),
                "{$type} {$title}",
            );
        }

        $this->assertSame(array_values(self::FORMER_DATES), $this->termDatesOf('Laravel'));
    }

    public function test_restoring_the_dates_keeps_an_edit_made_since_the_move(): void
    {
        $this->fillOldTables();
        $this->move();
        // As the first move left them in production: every date the move's, the Home Page's Origin edited since.
        DB::table('velox_entries')->update(['created_at' => '2026-10-20 17:51:33', 'updated_at' => '2026-10-20 17:51:33', 'published_at' => '2026-10-20 17:51:33']);
        DB::table('velox_entries')->where('type', 'home')->whereNull('origin_id')->update(['updated_at' => '2026-10-20 18:30:00']);
        DB::table('velox_terms')->update(['created_at' => '2026-10-20 17:51:33', 'updated_at' => '2026-10-20 17:51:33']);

        (require database_path('migrations/2026_10_20_000000_restore_the_moved_entries_dates.php'))->up();

        $this->assertSame(array_fill(0, 2, ['2026-06-09 20:07:21', '2026-06-26 18:29:41', '2026-06-09 20:07:21']), $this->datesOf('project', 'Atlas'));
        $this->assertSame(array_fill(0, 2, ['2026-06-09 20:07:21', '2026-06-26 18:29:41', '2026-06-09 20:07:21']), $this->datesOf('skill-group', 'Cœur'));
        $this->assertSame([
            ['2026-06-09 20:07:21', '2026-10-20 18:30:00', '2026-06-09 20:07:21'],
            ['2026-06-09 20:07:21', '2026-06-26 18:29:41', '2026-06-09 20:07:21'],
        ], $this->datesOf('home', 'Accueil'));
        $this->assertSame(array_values(self::FORMER_DATES), $this->termDatesOf('Laravel'));
        $this->assertSame(['2026-10-20 17:51:33'], DB::table('velox_entries')->where('type', 'project')->where('title', 'Borealis')->distinct()->pluck('created_at')->all());
    }

    /**
     * An Origin's, then its Translation's, creation, last edit and publication dates.
     *
     * @return list<list<?string>>
     */
    private function datesOf(string $type, string $title): array
    {
        $origin = DB::table('velox_entries')->where('type', $type)->whereNull('origin_id')->where('title', $title)->value('id');

        return DB::table('velox_entries')->where('id', $origin)->orWhere('origin_id', $origin)->orderBy('id')
            ->get(['created_at', 'updated_at', 'published_at'])
            ->map(fn (object $row): array => [(string) $row->created_at, (string) $row->updated_at, (string) $row->published_at])
            ->all();
    }

    /** @return list<string> a Term's creation and last edit dates */
    private function termDatesOf(string $name): array
    {
        $term = DB::table('velox_terms')->where('name', $name)->sole();

        return [(string) $term->created_at, (string) $term->updated_at];
    }

    /** The old tables' rows, as aguet's former models wrote them: spatie JSON per Locale, Tags through a morph pivot. */
    private function fillOldTables(): void
    {
        $json = fn (string $fr, ?string $en = null): string => json_encode(['fr' => $fr, 'en' => $en ?? $fr], JSON_UNESCAPED_UNICODE);

        DB::table('site_contents')->insert([
            'hero_title' => $json("Développeur *PHP*,\nbackend.", "*PHP* developer,\nbackend."),
            'hero_subtitle' => $json('Sous-titre.', 'Subtitle.'),
            'hero_role' => $json('Rôle', 'Role'),
            'hero_location' => $json('Lieu', 'Place'),
            'hero_exp' => $json('10 ans', '10 years'),
            'hero_focus' => $json('Laravel'),
            'about_body' => $json("Premier paragraphe.\n\nSecond paragraphe.", "First paragraph.\n\nSecond paragraph."),
            'contact_lead' => $json('Une accroche ?', 'A lead?'),
            'contact_email' => 'someone@example.com',
            'contact_linkedin' => 'https://www.linkedin.com/in/someone',
            'contact_linkedin_label' => '/in/someone',
            'contact_github' => 'https://github.com/someone',
            'contact_github_label' => '/someone',
            ...self::FORMER_DATES,
        ]);

        // Hand order: borealis, atlas, cachee; atlas featured, so shown first; cachee unpublished.
        $projects = [
            ['slug' => 'atlas', 'name' => $json('Atlas'), 'client' => json_encode(['fr' => 'Client Atlas', 'en' => '']), 'featured' => true, 'sort_order' => 2, 'is_published' => true, ...self::FORMER_DATES],
            ['slug' => 'borealis', 'name' => $json('Borealis'), 'client' => $json('Client Borealis', 'Borealis client'), 'featured' => false, 'sort_order' => 1, 'is_published' => true, 'created_at' => null, 'updated_at' => null],
            ['slug' => 'cachee', 'name' => $json('Cachée', 'Hidden'), 'client' => null, 'featured' => false, 'sort_order' => 3, 'is_published' => false, 'created_at' => null, 'updated_at' => null],
        ];

        foreach ($projects as $project) {
            DB::table('projects')->insert([...$project, 'role' => $json('Backend'), 'summary' => $json('Résumé.', 'Summary.'), 'url' => 'https://example.com']);
        }

        DB::table('skill_groups')->insert([
            ['title' => $json('Langues', 'Languages'), 'text' => $json('FR · EN'), 'note' => null, 'focus' => false, 'sort_order' => 2, 'created_at' => null, 'updated_at' => null],
            ['title' => $json('Cœur', 'Core'), 'text' => null, 'note' => $json('ici', 'here'), 'focus' => true, 'sort_order' => 1, ...self::FORMER_DATES],
        ]);

        DB::table('tags')->insert([['name' => 'Laravel', ...self::FORMER_DATES], ['name' => 'Filament', 'created_at' => null, 'updated_at' => null]]);

        $tag = fn (string $name): int => DB::table('tags')->where('name', $name)->value('id');
        $project = fn (string $slug): int => DB::table('projects')->where('slug', $slug)->value('id');
        $core = DB::table('skill_groups')->where('sort_order', 1)->value('id');

        DB::table('taggables')->insert([
            ['tag_id' => $tag('Laravel'), 'taggable_type' => 'App\\Models\\Project', 'taggable_id' => $project('atlas'), 'position' => 1],
            ['tag_id' => $tag('Filament'), 'taggable_type' => 'App\\Models\\Project', 'taggable_id' => $project('atlas'), 'position' => 0],
            ['tag_id' => $tag('Laravel'), 'taggable_type' => 'App\\Models\\SkillGroup', 'taggable_id' => $core, 'position' => 0],
        ]);

        DB::table('users')->insert(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => bcrypt('secret')]);
    }

    private function move(): void
    {
        (require database_path('migrations/2026_10_19_000000_move_content_into_velox.php'))->up();
    }

    private function legend(string $slug): string
    {
        return '<span class="slash">~/projects/</span><b>'.$slug.'</b>';
    }
}
