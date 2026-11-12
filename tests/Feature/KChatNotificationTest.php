<?php

use App\Forms\ContactForm;
use App\Notifications\Channels\KChatChannel;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Psyao\Kami\Forms\Submission;

/** Send the card alone, as Kami sends each channel of the notification on its own. */
function sendCard(string $webhook): void
{
    config(['services.kchat.contact_webhook_url' => $webhook]);
    $submission = (new Submission(['data' => ['subject' => 'Hi', 'email' => 'v@x.com', 'message' => 'Hello world']]))->forceFill(['id' => 1]);

    Notification::sendNow(
        Notification::route('mail', 'owner@example.com'),
        new ContactMessageReceived(resolve(ContactForm::class), $submission),
        [KChatChannel::class],
    );
}

it('posts the card to the configured webhook url', function () {
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);

    sendCard('https://kchat.test/hook');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://kchat.test/hook'
            && ($request['attachments'][0]['title'] ?? null) === 'New contact message';
    });
});

it('throws on a non-2xx response so the caller can retry', function () {
    Http::fake(['https://kchat.test/hook' => Http::response('boom', 500)]);

    expect(fn () => sendCard('https://kchat.test/hook'))
        ->toThrow(RuntimeException::class);
});

it('throws fast and sends nothing when the url is blank or invalid', function () {
    Http::fake();

    expect(fn () => sendCard('not-a-url'))
        ->toThrow(RuntimeException::class);

    Http::assertNothingSent();
});

it('never leaks the webhook url in the failure message (non-2xx)', function () {
    Http::fake(['https://secret.test/hook' => Http::response('err', 500)]);

    try {
        sendCard('https://secret.test/hook');
        $this->fail('Expected the channel to throw.');
    } catch (Throwable $e) {
        expect($e->getMessage())->not->toContain('secret.test');
    }
});

it('redacts the url when the request throws a connection error', function () {
    // A connection-level failure (DNS/timeout/TLS) throws before a response
    // exists; its message commonly contains the host.
    Http::fake(function () {
        throw new ConnectionException(
            'cURL error 6: Could not resolve host: secret.test'
        );
    });

    try {
        sendCard('https://secret.test/hook');
        $this->fail('Expected the channel to throw.');
    } catch (Throwable $e) {
        expect($e->getMessage())->not->toContain('secret.test');
    }
});
