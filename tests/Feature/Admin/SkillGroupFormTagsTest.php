<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\SkillGroups\Pages\CreateSkillGroup;
use App\Filament\Resources\SkillGroups\Pages\EditSkillGroup;
use App\Models\SkillGroup;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SkillGroupFormTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_selection_order_is_saved_as_pivot_positions(): void
    {
        $this->actingAs(User::factory()->create());

        $group = SkillGroup::create([
            'title' => ['fr' => 'Démo', 'en' => 'Demo'],
            'sort_order' => 99,
        ]);
        $php = Tag::create(['name' => 'PHP']);
        $vue = Tag::create(['name' => 'Vue']);

        Livewire::test(EditSkillGroup::class, ['record' => $group->getRouteKey()])
            ->fillForm(['tags' => [$vue->id, $php->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Vue', 'PHP'], $group->refresh()->tags->pluck('name')->all());
        $this->assertSame([0, 1], $group->tags->pluck('pivot.position')->all());
    }

    public function test_create_persists_every_field_including_both_locales(): void
    {
        $this->actingAs(User::factory()->create());

        $php = Tag::create(['name' => 'PHP']);

        Livewire::test(CreateSkillGroup::class)
            ->fillForm([
                'title' => ['fr' => 'Titre FR', 'en' => 'Title EN'],
                'text' => ['fr' => 'Texte FR', 'en' => 'Text EN'],
                'note' => ['fr' => 'Note FR', 'en' => 'Note EN'],
                'tags' => [$php->id],
                'sort_order' => 5,
                'focus' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // The DDL+DML migration seeds default groups, so key on the just-created
        // row (highest id) rather than a column value that a seed row may share.
        $group = SkillGroup::latest('id')->first();

        $this->assertNotNull($group);
        $this->assertSame('Titre FR', $group->getTranslation('title', 'fr'));
        $this->assertSame('Title EN', $group->getTranslation('title', 'en'));
        $this->assertSame('Texte FR', $group->getTranslation('text', 'fr'));
        $this->assertSame('Note EN', $group->getTranslation('note', 'en'));
        $this->assertSame(5, $group->sort_order);
        $this->assertTrue($group->focus);
        $this->assertSame(['PHP'], $group->tags->pluck('name')->all());
    }
}
