<?php

use App\Filament\Pages\DatabaseBackup;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Velox\Cms\Role;

// Not RefreshDatabase: the restorer drops every table, which SQLite (VACUUM) refuses inside a transaction.
uses(DatabaseMigrations::class);

/** A dump taken before the move: aguet's users table without Velox's Role and MFA columns, its migrations table not listing Velox's. */
function preVeloxDump(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('aguet-pre-velox.sql', <<<'SQL'
        DROP TABLE IF EXISTS "migrations";
        CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);
        INSERT INTO "migrations" ("migration", "batch") VALUES ('0001_01_01_000000_create_users_table', 1);
        DROP TABLE IF EXISTS "users";
        CREATE TABLE "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar not null, "email_verified_at" datetime, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime);
        CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");
        INSERT INTO "users" ("name", "email", "password") VALUES ('Old name', 'owner@aguet.dev', 'old-hash');
        DROP TABLE IF EXISTS "password_reset_tokens";
        CREATE TABLE "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));
        DROP TABLE IF EXISTS "sessions";
        CREATE TABLE "sessions" ("id" varchar not null, "user_id" integer, "ip_address" varchar, "user_agent" text, "payload" text not null, "last_activity" integer not null, primary key ("id"));
        SQL);
}

it('opens the backup page to a Developer', function () {
    $this->actingAs(User::factory()->create());

    $this->get(DatabaseBackup::getUrl())->assertOk();
});

it('refuses the backup page to a Writer', function () {
    $this->actingAs(User::factory()->create(['role' => Role::Writer->value]));

    $this->get(DatabaseBackup::getUrl())->assertForbidden();
});

it('keeps the admin signing in as a Developer after restoring a dump taken before the move', function () {
    $admin = User::factory()->create(['email' => 'owner@aguet.dev', 'password' => 'secret-password']);
    $this->actingAs($admin);

    Livewire::test(DatabaseBackup::class)
        ->callAction('restore', ['dump' => preVeloxDump()])
        ->assertHasNoFormErrors()
        ->assertNotified('Base restaurée');

    auth()->logout();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'owner@aguet.dev', 'password' => 'secret-password'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    expect(Role::of(auth()->user()))->toBe(Role::Developer);
});

it('keeps the admin\'s MFA secret and recovery codes after a restore', function () {
    $admin = User::factory()->create([
        'app_authentication_secret' => 'MFA-SECRET',
        'app_authentication_recovery_codes' => ['code-one', 'code-two'],
    ]);
    $this->actingAs($admin);

    Livewire::test(DatabaseBackup::class)
        ->callAction('restore', ['dump' => preVeloxDump()]);

    $restored = User::firstWhere('email', $admin->email);

    expect($restored->app_authentication_secret)->toBe('MFA-SECRET')
        ->and($restored->app_authentication_recovery_codes)->toBe(['code-one', 'code-two']);
});
