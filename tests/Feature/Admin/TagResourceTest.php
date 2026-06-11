<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagResourceTest extends TestCase
{
    use RefreshDatabase;

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
