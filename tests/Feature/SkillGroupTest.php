<?php

namespace Tests\Feature;

use App\Models\SkillGroup;
use Database\Seeders\SkillGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_groups_are_ordered(): void
    {
        // The migration already seeds; seeding again must not duplicate.
        $this->seed(SkillGroupSeeder::class);
        $this->seed(SkillGroupSeeder::class);

        $groups = SkillGroup::ordered()->get();

        $this->assertCount(5, $groups);
        $this->assertSame('Cœur Laravel', $groups->first()->getTranslation('title', 'fr'));
        $this->assertSame('Laravel core', $groups->first()->getTranslation('title', 'en'));
        $this->assertSame(['PHP', 'Laravel', 'Filament', 'Eloquent', 'Blade'], $groups->first()->items);
    }

    public function test_focus_note_and_languages_text(): void
    {
        $this->seed(SkillGroupSeeder::class);

        $focus = SkillGroup::ordered()->get()->first(fn ($g) => $g->focus);
        $this->assertSame('Intégration & automatisation', $focus->getTranslation('title', 'fr'));
        $this->assertSame('là où je fais la différence', $focus->getTranslation('note', 'fr'));

        $languages = SkillGroup::ordered()->get()->last();
        $this->assertNull($languages->items);
        $this->assertSame('FR (natif) · EN (pro) · DE (notions)', $languages->getTranslation('text', 'fr'));
        $this->assertSame('FR (native) · EN (professional) · DE (basics)', $languages->getTranslation('text', 'en'));
    }
}
