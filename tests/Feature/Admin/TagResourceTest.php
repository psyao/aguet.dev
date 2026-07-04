<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TagResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_the_name(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateTag::class)
            ->fillForm(['name' => 'GraphQL'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('GraphQL', Tag::firstWhere('name', 'GraphQL')?->name);
    }

    public function test_tags_index_is_reachable_by_admins(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/tags')->assertOk();
    }

    public function test_tags_index_requires_auth(): void
    {
        $this->get('/admin/tags')->assertRedirect('/admin/login');
    }
}
