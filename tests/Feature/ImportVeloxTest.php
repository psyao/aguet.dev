<?php

use App\ContentTypes\Home;
use App\ContentTypes\Project;
use App\ContentTypes\SkillGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psyao\Kami\Content\Entry;
use Psyao\Kami\Content\SiteSettings;
use Psyao\Kami\Forms\Submission;
use Psyao\Kami\Media\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

/**
 * Velox 1.1's tables, with the columns `app:import:velox` reads, as production holds them until the cleanup.
 */
function createVeloxTables(): void
{
    Schema::create('velox_entries', function (Blueprint $table) {
        $table->id();
        $table->string('type');
        $table->string('locale', 3)->nullable();
        $table->foreignId('origin_id')->nullable();
        $table->string('title');
        $table->string('slug');
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->json('data')->nullable();
        $table->json('seo')->nullable();
        $table->unsignedInteger('position')->default(0);
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('velox_terms', function (Blueprint $table) {
        $table->id();
        $table->string('taxonomy');
        $table->string('locale', 3)->nullable();
        $table->foreignId('origin_id')->nullable();
        $table->string('name');
        $table->string('slug');
        $table->timestamps();
    });
    Schema::create('velox_entry_term', function (Blueprint $table) {
        $table->foreignId('entry_id');
        $table->foreignId('term_id');
        $table->unsignedInteger('position');
    });
    Schema::create('velox_media_items', function (Blueprint $table) {
        $table->id();
        $table->ulid();
        $table->string('disk');
        $table->string('path');
        $table->text('alt')->nullable();
        $table->timestamps();
    });
    Schema::create('velox_settings', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->json('value');
        $table->timestamps();
    });
    Schema::table('users', function (Blueprint $table) {
        $table->text('app_authentication_secret')->nullable();
        $table->text('app_authentication_recovery_codes')->nullable();
    });
}

/**
 * A small aguet.dev on Velox: the Home in two Locales, two Projects and a trashed one, a SkillGroup, three Tags,
 * the social image, two messages and the administrator with MFA.
 */
function seedVelox(): void
{
    createVeloxTables();
    $at = '2025-02-01 09:00:00';
    $entry = fn (array $row): int => DB::table('velox_entries')->insertGetId([
        'status' => 'published', 'published_at' => $at, 'position' => 0, 'created_at' => $at, 'updated_at' => $at, 'seo' => null, ...$row,
        'data' => json_encode($row['data'] ?? []),
    ]);

    $paragraph = fn (string $text): array => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'attrs' => ['textAlign' => 'start'], 'content' => [['type' => 'text', 'text' => $text]]]]];
    $home = $entry(['type' => 'home', 'locale' => 'fr', 'title' => 'Accueil', 'slug' => 'accueil', 'created_at' => '2025-01-01 08:00:00', 'seo' => json_encode(['title' => 'Steve Aguet — Développeur', 'description' => 'Laravel, région lémanique.']), 'data' => [
        'hero_title' => "Développeur *PHP*,\nbackend.", 'about_body' => $paragraph('Je développe pour le web.'), 'contact_email' => 'steve@example.com', 'contact_github' => 'https://github.com/example',
    ]]);
    $entry(['type' => 'home', 'locale' => 'en', 'origin_id' => $home, 'title' => 'Home', 'slug' => 'home', 'updated_at' => '2025-03-01 10:00:00', 'data' => [
        'hero_title' => "*PHP* developer,\nbackend.", 'about_body' => $paragraph('I build for the web.'),
    ]]);

    $tags = collect(['Laravel', 'PHP', 'Vue'])->mapWithKeys(fn (string $name) => [$name => DB::table('velox_terms')->insertGetId([
        'taxonomy' => 'tag', 'locale' => 'fr', 'name' => $name, 'slug' => Str::slug($name), 'created_at' => $at, 'updated_at' => $at,
    ])]);
    $classify = fn (int $entry, array $names) => DB::table('velox_entry_term')->insert(
        array_map(fn (string $name, int $position) => ['entry_id' => $entry, 'term_id' => $tags[$name], 'position' => $position], $names, array_keys($names)),
    );

    $cvci = $entry(['type' => 'project', 'locale' => 'fr', 'title' => 'CVCI', 'slug' => 'cvci', 'position' => 2, 'data' => ['url' => 'https://cvci.example', 'client' => 'Chambre', 'summary' => 'Portail.', 'featured' => true]]);
    $entry(['type' => 'project', 'locale' => 'en', 'origin_id' => $cvci, 'title' => 'CVCI', 'slug' => 'cvci', 'position' => 2, 'data' => ['client' => 'Chamber', 'summary' => 'Portal.']]);
    $classify($cvci, ['Vue', 'PHP', 'Laravel']);
    $animalia = $entry(['type' => 'project', 'locale' => 'fr', 'title' => 'Animalia', 'slug' => 'animalia', 'position' => 1, 'data' => ['featured' => false]]);
    $classify($animalia, ['Laravel']);
    $trashed = $entry(['type' => 'project', 'locale' => 'fr', 'title' => 'Ancien', 'slug' => 'ancien', 'deleted_at' => $at, 'data' => []]);
    $classify($trashed, ['PHP']);

    $stack = $entry(['type' => 'skill-group', 'locale' => 'fr', 'title' => 'Cœur Laravel', 'slug' => 'coeur-laravel', 'position' => 1, 'data' => ['note' => 'là où je fais la différence', 'focus' => true]]);
    $entry(['type' => 'skill-group', 'locale' => 'en', 'origin_id' => $stack, 'title' => 'Laravel core', 'slug' => 'laravel-core', 'position' => 1, 'data' => ['note' => 'where I make the difference']]);
    $classify($stack, ['PHP', 'Laravel']);

    $image = imagecreatetruecolor(4, 4);
    ob_start();
    imagepng($image);
    Storage::disk('public')->put('media/01jsocial/aguet-dev.png', ob_get_clean());
    $media = DB::table('velox_media_items')->insertGetId([
        'ulid' => '01jsocial', 'disk' => 'public', 'path' => 'media/01jsocial/aguet-dev.png', 'alt' => json_encode(['fr' => 'Steve Aguet', 'en' => 'Steve Aguet']), 'created_at' => '2025-01-11 09:00:00', 'updated_at' => '2025-01-11 09:00:00',
    ]);
    DB::table('velox_settings')->insert(['key' => 'seo', 'value' => json_encode(['site_name' => ['fr' => 'Steve Aguet', 'en' => 'Steve Aguet'], 'description' => 'Développeur Laravel.', 'image' => $media]), 'created_at' => $at, 'updated_at' => $at]);

    DB::table('contact_messages')->insert([
        ['subject' => 'Une plateforme', 'email' => 'visitor@example.com', 'message' => 'Bonjour.', 'read_at' => '2025-04-01 09:00:00', 'created_at' => '2025-04-01 08:00:00', 'updated_at' => '2025-04-01 09:00:00'],
        ['subject' => 'A platform', 'email' => 'visitor@example.org', 'message' => 'Hello.', 'read_at' => null, 'created_at' => '2025-04-02 08:00:00', 'updated_at' => '2025-04-02 08:00:00'],
    ]);

    $admin = User::factory()->create(['email' => 'owner@example.com']);
    DB::table('users')->where('id', $admin->id)->update([
        'app_authentication_secret' => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
        'app_authentication_recovery_codes' => Crypt::encryptString(json_encode(['hashed-code-1', 'hashed-code-2'])),
    ]);
}

/** As the Switch-over deploy does: `kami:install` creates the Home as a Draft and `SiteSettings` before the import. */
function installKami(): void
{
    test()->artisan('kami:install', ['--no-interaction' => true, '--email' => 'owner@example.com'])->assertSuccessful();
}

it('imports the Velox content, Media, messages and MFA, then proves the counts', function () {
    seedVelox();
    installKami();
    $password = DB::table('users')->value('password');

    $this->artisan('app:import:velox')
        ->expectsOutputToContain('Counts match')
        ->assertSuccessful();

    $home = Home::entry();
    expect($home->hero_title->raw())->toBe("Développeur *PHP*,\nbackend.")
        ->and($home->about_body->text())->toBe('Je développe pour le web.')
        ->and($home->contact_email)->toBe('steve@example.com')
        ->and($home->meta_title)->toBe('Steve Aguet — Développeur')
        ->and($home->status)->toBe('published')
        ->and($home->created_at->toDateTimeString())->toBe('2025-01-01 08:00:00')
        ->and($home->updated_at->toDateTimeString())->toBe('2025-03-01 10:00:00');
    expect($home->translation('en')->title)->toBe('Home');

    $projects = Entry::query()->ofType(Project::class)->ordered()->get();
    expect($projects->pluck('title')->all())->toBe(['Animalia', 'CVCI'])
        ->and($projects[1]->website)->toBe('https://cvci.example')
        ->and($projects[1]->directory)->toBe('cvci')
        ->and($projects[1]->featured)->toBeTrue()
        ->and($projects[1]->tags->pluck('title')->all())->toBe(['Vue', 'PHP', 'Laravel']);
    expect(Entry::query()->ofType(SkillGroup::class)->sole()->tags->pluck('title')->all())->toBe(['PHP', 'Laravel']);

    $media = Media::query()->sole();
    expect(SiteSettings::entry()->default_social_image?->id)->toBe($media->id)
        ->and(SiteSettings::entry()->site_name)->toBe('Steve Aguet')
        ->and($media->created_at->toDateTimeString())->toBe('2025-01-11 09:00:00');

    expect(Submission::query()->where('form', 'contact_form')->orderBy('created_at')->get()->map->only(['locale', 'read_at'])->toArray())->toEqual([
        ['locale' => 'fr', 'read_at' => CarbonImmutable::parse('2025-04-01 09:00:00')],
        ['locale' => 'fr', 'read_at' => null],
    ])->and(DB::table('kami_form_deliveries')->count())->toBe(0);

    $admin = User::query()->sole();
    expect($admin->getAppAuthenticationSecret())->toBe('JBSWY3DPEHPK3PXP')
        ->and($admin->getAppAuthenticationRecoveryCodes())->toBe(['hashed-code-1', 'hashed-code-2'])
        ->and($admin->password)->toBe($password);
});

it('exits without writing once Velox is imported, unless given --again', function () {
    seedVelox();
    installKami();
    $this->artisan('app:import:velox')->assertSuccessful();
    DB::table('velox_entries')->where('title', 'CVCI')->update(['title' => 'CVCI renamed']);

    $this->artisan('app:import:velox')
        ->expectsOutputToContain('Velox is imported already')
        ->assertSuccessful();

    expect(Entry::query()->ofType(Project::class)->get()->pluck('title')->sort()->values()->all())->toBe(['Animalia', 'CVCI']);
});

it('replays with --again from the source, with no duplicated message', function () {
    seedVelox();
    installKami();
    $this->artisan('app:import:velox')->assertSuccessful();
    DB::table('velox_entries')->where('title', 'CVCI')->update(['title' => 'CVCI renamed']);

    $this->artisan('app:import:velox', ['--again' => true])
        ->expectsOutputToContain('0 created, 1 updated')
        ->assertSuccessful();

    expect(Entry::query()->ofType(Project::class)->get()->pluck('title')->sort()->values()->all())->toBe(['Animalia', 'CVCI renamed'])
        ->and(Submission::query()->count())->toBe(2);
});

it('exits non-zero when a count does not match the source', function () {
    seedVelox();
    installKami();
    // Two identical messages in the same second fold into one Submission.
    DB::table('contact_messages')->insert(DB::table('contact_messages')->select(['subject', 'email', 'message', 'read_at', 'created_at', 'updated_at'])->where('subject', 'A platform')->get()->map(fn (object $row) => (array) $row)->all());

    $this->artisan('app:import:velox')
        ->expectsOutputToContain('Count mismatch')
        ->assertFailed();
});
