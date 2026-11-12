<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'aguet.admin.email' => 'owner@aguet.dev',
        'aguet.admin.password' => 'secret-password',
    ]);

    $this->seed(AdminUserSeeder::class);
});

it('seeds the admin as a Kami administrator', function () {
    expect(User::firstWhere('email', 'owner@aguet.dev')->kamiRoles())->toBe(['administrator']);
});

it('sends the seeded admin to set MFA up, then opens the Admin and its Projects', function () {
    $admin = User::firstWhere('email', 'owner@aguet.dev');
    $this->actingAs($admin);

    $this->get('/admin')->assertRedirect('/admin/multi-factor-authentication/set-up');

    $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    $this->get('/admin')->assertOk();
    $this->get('/admin/projects')->assertOk();
});

it('refuses the Admin to a user without a Role', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertForbidden();
});
