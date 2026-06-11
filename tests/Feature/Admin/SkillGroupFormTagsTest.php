<?php

namespace Tests\Feature\Admin;

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
}
