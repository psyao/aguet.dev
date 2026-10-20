<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Velox\Cms\Filament\Pages\SeoSettings;
use Velox\Cms\Filament\Resources\Accounts\AccountResource;
use Velox\Cms\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'aguet.admin.email' => 'owner@aguet.dev',
        'aguet.admin.password' => 'secret-password',
    ]);

    $this->seed(AdminUserSeeder::class);
});

it('signs the seeded admin in to the Admin', function () {
    Livewire::test(Login::class)
        ->fillForm(['email' => 'owner@aguet.dev', 'password' => 'secret-password'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs(User::firstWhere('email', 'owner@aguet.dev'));
});

it('seeds the admin as a Developer', function () {
    expect(Role::of(User::firstWhere('email', 'owner@aguet.dev')))->toBe(Role::Developer);
});

it('opens the SEO settings and the Accounts to the seeded admin', function () {
    $this->actingAs(User::firstWhere('email', 'owner@aguet.dev'));

    $this->get(SeoSettings::getUrl())->assertOk();
    $this->get(AccountResource::getUrl())->assertOk();
});

it('refuses the Admin to a user without a Role', function () {
    $this->actingAs(User::factory()->create(['role' => null]));

    $this->get('/admin')->assertForbidden();
});
