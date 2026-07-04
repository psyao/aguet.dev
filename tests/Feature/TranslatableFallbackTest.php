<?php

use App\Models\Project;
use App\Models\SiteContent;
use App\Models\SkillGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// The #[Translatable] attribute must be picked up by HasTranslations — if the
// wiring breaks, every field stops JSON-encoding per locale.
it('registers translatable fields from the #[Translatable] attribute', function () {
    expect((new Project)->getTranslatableAttributes())
        ->toContain('name', 'client', 'role', 'summary')
        ->and((new SkillGroup)->getTranslatableAttributes())
        ->toContain('title', 'text', 'note')
        ->and((new SiteContent)->getTranslatableAttributes())
        ->toContain('hero_title', 'about_body', 'contact_lead');
});

// The EN page renders FR for any field left blank in EN. This relies on spatie's
// default allowEmptyStringForTranslation=false: a blank locale is not counted as
// translated, so the read falls back to app.fallback_locale (fr). Guard it — a
// config flip or a locale left unset would otherwise blank the EN page silently.
it('falls back to the default locale when a translation is blank', function () {
    config()->set('app.fallback_locale', 'fr');

    $project = Project::create([
        'name' => ['fr' => 'Nom', 'en' => 'Name'],
        'role' => ['fr' => 'Backend', 'en' => ''],   // EN deliberately blank
        'slug' => 'fallback-demo',
    ]);

    expect($project->getTranslation('role', 'en'))->toBe('Backend')   // falls back to FR
        ->and($project->getTranslation('role', 'fr'))->toBe('Backend')
        ->and($project->getTranslation('name', 'en'))->toBe('Name');  // present → no fallback
});

it('reads the active-locale value through the magic accessor', function () {
    $project = Project::create([
        'name' => ['fr' => 'Bonjour', 'en' => 'Hello'],
        'slug' => 'accessor-demo',
    ]);

    app()->setLocale('en');
    expect($project->name)->toBe('Hello');

    app()->setLocale('fr');
    expect($project->name)->toBe('Bonjour');
});
