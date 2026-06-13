<?php

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('posts the card to the routed webhook url', function () {
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    $row = ContactMessage::factory()->make(['subject' => 'Hi', 'email' => 'v@x.com', 'message' => 'Hello world']);

    Notification::route('kchat', 'https://kchat.test/hook')
        ->notifyNow(new ContactMessageReceived($row));

    Http::assertSent(function ($request) {
        return $request->url() === 'https://kchat.test/hook'
            && ($request['attachments'][0]['title'] ?? null) === 'New contact message';
    });
});

it('throws on a non-2xx response so the caller can retry', function () {
    Http::fake(['https://kchat.test/hook' => Http::response('boom', 500)]);
    $row = ContactMessage::factory()->make();

    expect(fn () => Notification::route('kchat', 'https://kchat.test/hook')
        ->notifyNow(new ContactMessageReceived($row)))
        ->toThrow(RuntimeException::class);
});

it('throws fast and sends nothing when the url is blank or invalid', function () {
    Http::fake();
    $row = ContactMessage::factory()->make();

    expect(fn () => Notification::route('kchat', 'not-a-url')
        ->notifyNow(new ContactMessageReceived($row)))
        ->toThrow(RuntimeException::class);

    Http::assertNothingSent();
});

it('never leaks the webhook url in the failure message (non-2xx)', function () {
    Http::fake(['https://secret.test/hook' => Http::response('err', 500)]);
    $row = ContactMessage::factory()->make();

    try {
        Notification::route('kchat', 'https://secret.test/hook')
            ->notifyNow(new ContactMessageReceived($row));
        $this->fail('Expected the channel to throw.');
    } catch (\Throwable $e) {
        expect($e->getMessage())->not->toContain('secret.test');
    }
});

it('redacts the url when the request throws a connection error', function () {
    // A connection-level failure (DNS/timeout/TLS) throws before a response
    // exists; its message commonly contains the host.
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException(
            'cURL error 6: Could not resolve host: secret.test'
        );
    });
    $row = ContactMessage::factory()->make();

    try {
        Notification::route('kchat', 'https://secret.test/hook')
            ->notifyNow(new ContactMessageReceived($row));
        $this->fail('Expected the channel to throw.');
    } catch (\Throwable $e) {
        expect($e->getMessage())->not->toContain('secret.test');
    }
});

it('does not implement ShouldQueue (must send synchronously)', function () {
    expect(is_subclass_of(ContactMessageReceived::class, ShouldQueue::class))->toBeFalse();
});

it('builds an absolute admin inbox link from a non-HTTP context', function () {
    // Force the URL root deterministically — setting config('app.url') after
    // boot does not reliably move the generator's root.
    URL::useOrigin('https://aguet.dev');

    $url = ContactMessageResource::getUrl(name: 'index', panel: 'admin');

    // Scheme-agnostic: in production the scheme comes from APP_URL at bootstrap;
    // here we only assert getUrl resolves to an absolute URL with the right
    // host + Filament slug from a non-HTTP context.
    expect($url)->toStartWith('http')
        ->and($url)->toEndWith('aguet.dev/admin/contact-messages');

    URL::useOrigin(null); // reset for other tests
});
