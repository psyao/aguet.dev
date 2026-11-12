<?php

use App\ContentTypes\Home;
use App\Forms\ContactForm;
use App\Notifications\Channels\KChatChannel;
use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Psyao\Kami\Content\Actions\SaveEntry;
use Psyao\Kami\Forms\Submission;

uses(RefreshDatabase::class);

beforeEach(function () {
    setRecipient('owner@example.com');
    Http::preventStrayRequests();
});

/**
 * Post the contact Form as a browser does from the given Locale's Home.
 *
 * @param  array<string, string>  $fields
 */
function postContact(array $fields = [], string $locale = 'fr'): TestResponse
{
    return test()
        ->withHeader('Sec-Fetch-Site', 'same-origin')
        ->post('/kami/forms/contact_form', [
            'subject' => 'Bonjour',
            'email' => 'visitor@example.com',
            'message' => 'I would like to work with you.',
            '_kami_origin' => Home::entry()->id.':'.$locale,
            ...$fields,
        ]);
}

it('stores a valid message and sends the visitor back to the Home with the modal open', function () {
    Notification::fake();

    postContact()->assertRedirect(url('/').'?sent=contact_form#contact_form');

    expect(Submission::sole())
        ->form->toBe('contact_form')
        ->locale->toBe('fr')
        ->data->toBe(['subject' => 'Bonjour', 'email' => 'visitor@example.com', 'message' => 'I would like to work with you.']);

    $this->get('/?sent=contact_form')
        ->assertOk()
        ->assertSee('data-contact-autoopen', false)
        ->assertSee('Message envoyé. Merci — je te réponds bientôt.')
        ->assertDontSee('name="subject"', false);
});

it('opens the modal on its errors, in French, and stores nothing', function () {
    postContact(['subject' => '', 'email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertSee('data-contact-autoopen', false)
        ->assertSee('Le sujet est requis.')
        ->assertSee('Entre une adresse email valide.')
        ->assertSee('value="not-an-email"', false);

    expect(Submission::query()->count())->toBe(0);
});

it('renders the Home with the modal closed and the form ready', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('data-contact-autoopen', false)
        ->assertSee('id="contact_form"', false)
        ->assertSee('name="subject"', false)
        ->assertSee('maxlength="'.ContactForm::MAX_MESSAGE.'"', false);
});

it('mails the owner and pings kChat after the response', function () {
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    Notification::fake();

    $this->withoutDefer();
    postContact(['subject' => 'Hi @channel']);

    // Kami sends each channel on its own, so that each is retried on its own.
    Notification::assertSentOnDemand(
        ContactMessageReceived::class,
        fn (ContactMessageReceived $notification, array $channels, AnonymousNotifiable $notifiable): bool => $channels === ['mail']
            && $notifiable->routeNotificationFor('mail') === ['owner@example.com'],
    );
    Notification::assertSentOnDemand(
        ContactMessageReceived::class,
        fn (ContactMessageReceived $notification, array $channels): bool => $channels === [KChatChannel::class],
    );
});

it('sends kChat nothing while no webhook is configured', function () {
    config(['services.kchat.contact_webhook_url' => null]);
    Notification::fake();

    $this->withoutDefer();
    postContact();

    Notification::assertSentOnDemandTimes(ContactMessageReceived::class, 1);
    Notification::assertSentOnDemand(
        ContactMessageReceived::class,
        fn (ContactMessageReceived $notification, array $channels): bool => $channels === ['mail'],
    );
});

it('delivers the mail through Kami and the card to the configured webhook', function () {
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    Mail::fake();

    $this->withoutDefer();
    postContact(['subject' => 'Hi @channel']);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://kchat.test/hook'
        && $request['attachments'][0]['title'] === 'New contact message'
        && collect($request['attachments'][0]['fields'])->firstWhere('title', 'Subject')['value'] === "Hi @\u{200B}channel");
    $this->assertDatabaseCount('kami_form_deliveries', 2)
        ->assertDatabaseMissing('kami_form_deliveries', ['delivered_at' => null]);
});

it('reads the owner address while the Home is not visible in the current Locale', function () {
    $home = Home::entry();
    resolve(SaveEntry::class)->handle(Home::class, ['status' => 'draft'], $home, 'fr');
    resolve(SaveEntry::class)->handle(Home::class, ['status' => 'draft'], $home, 'en');

    expect(Home::entry())->toBeNull()
        ->and(resolve(ContactForm::class)->recipients())->toBe(['owner@example.com']);
});

it('mails nobody while the Home has no contact address', function () {
    setRecipient(null);

    expect(resolve(ContactForm::class)->recipients())->toBe([]);
});
