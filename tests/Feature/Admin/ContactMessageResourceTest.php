<?php

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists contact messages for an admin', function () {
    $messages = ContactMessage::factory()->count(3)->create();

    $this->get('/admin/contact-messages')->assertOk();

    Livewire::test(ListContactMessages::class)
        ->assertCanSeeTableRecords($messages);
});

it('marks a message read via the table action', function () {
    $message = ContactMessage::factory()->create(['read_at' => null]);

    Livewire::test(ListContactMessages::class)
        ->callTableAction('markRead', $message);

    expect($message->refresh()->read_at)->not->toBeNull();
});

it('does not expose a create path', function () {
    expect(ContactMessageResource::canCreate())->toBeFalse()
        ->and(ContactMessageResource::getPages())->not->toHaveKey('create');

    // No create route is registered.
    expect(fn () => route('filament.admin.resources.contact-messages.create'))
        ->toThrow(RouteNotFoundException::class);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get('/admin/contact-messages')->assertRedirect('/admin/login');
});
