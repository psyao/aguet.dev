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
 * @param  array<string, string>  $headers
 */
function postContact(array $fields = [], string $locale = 'fr', array $headers = []): TestResponse
{
    return test()
        ->withHeaders(['Sec-Fetch-Site' => 'same-origin', ...$headers])
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
        ->assertSee('id="contact_form" class="kami-form cf" aria-label="Écrire un message" hidden="hidden"', false);
});

it('answers the script with the confirmation in the page\'s Locale', function (string $locale, string $confirmation) {
    Notification::fake();

    postContact(locale: $locale, headers: ['Accept' => 'application/json'])
        ->assertOk()
        ->assertExactJson(['message' => $confirmation]);

    expect(Submission::sole()->locale)->toBe($locale);
})->with([
    'fr' => ['fr', 'Message envoyé. Merci — je te réponds bientôt.'],
    'en' => ['en', 'Message sent. Thanks — I’ll get back to you soon.'],
]);

it('opens the modal on its errors, in French, and stores nothing', function () {
    postContact(['subject' => '', 'email' => 'not-an-email', 'message' => 'é😀'])
        ->assertUnprocessable()
        ->assertSee('<span data-cf-count>3</span>', false)
        ->assertSee('data-contact-autoopen', false)
        ->assertSee('Le sujet est requis.')
        ->assertSee('Entre une adresse email valide.')
        ->assertSee('value="not-an-email"', false);

    expect(Submission::query()->count())->toBe(0);
});

it('refuses a field posted as an array with its errors, not a 500', function () {
    postContact(['subject' => ['Bonjour']])
        ->assertUnprocessable()
        ->assertSee('data-contact-autoopen', false);

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

it('mails the owner and posts the card to the configured webhook after the response', function () {
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

it('delivers the card with its link to the Submission and records both deliveries', function () {
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    Mail::fake();

    $this->withoutDefer();
    postContact(['subject' => 'Hi @channel']);

    $card = fn ($request): array => $request['attachments'][0];
    Http::assertSent(fn ($request): bool => $request->url() === 'https://kchat.test/hook'
        && $card($request)['title'] === 'New contact message'
        && str_contains($card($request)['text'], url('/admin/submissions/'.Submission::sole()->getKey()))
        && collect($card($request)['fields'])->firstWhere('title', 'Subject')['value'] === "Hi @\u{200B}channel");
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
