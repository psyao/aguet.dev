<?php

use App\Console\Commands\NotifyContactMessages;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Keep the kChat rail off by default so the email-only cases below stay isolated
// and never make stray requests if KCHAT_CONTACT_WEBHOOK_URL is set in the env.
beforeEach(function () {
    config(['services.kchat.contact_webhook_url' => null]);
});

/** Set the owner address the sweep delivers to. */
function setRecipient(string $email = 'owner@example.com'): void
{
    SiteContent::current()->update(['contact_email' => $email]);
}

/** Point the kChat rail at a fake webhook (null = unconfigured). */
function setKchatWebhook(?string $url = 'https://kchat.test/hook'): void
{
    config(['services.kchat.contact_webhook_url' => $url]);
}

// CN1 — the sweep sends to the owner (replyTo the visitor) and marks the row.
it('sends pending messages and marks them notified (CN1)', function () {
    Mail::fake();
    setRecipient();
    $row = ContactMessage::factory()->create(['email' => 'visitor@example.com']);

    $this->artisan('contact:notify')->assertSuccessful();

    Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) use ($row) {
        return $mail->hasTo('owner@example.com')
            && $mail->hasReplyTo('visitor@example.com')
            && $mail->contactMessage->is($row);
    });

    expect($row->refresh()->notified_at)->not->toBeNull();
});

// CN2 — a send failure increments attempts, leaves the row pending, and logs.
it('retries on failure without losing the row (CN2)', function () {
    setRecipient();
    $row = ContactMessage::factory()->create();

    Mail::shouldReceive('to')->andReturnSelf();
    Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP down'));

    $this->artisan('contact:notify')->assertSuccessful();

    $row->refresh();
    expect($row->notified_at)->toBeNull()
        ->and($row->notify_attempts)->toBe(1);
});

// CN3 — a row at the attempt cap is skipped (no further hammering).
it('skips rows that hit the attempt cap (CN3)', function () {
    Mail::fake();
    setRecipient();
    ContactMessage::factory()->create([
        'notify_attempts' => NotifyContactMessages::MAX_ATTEMPTS,
    ]);

    $this->artisan('contact:notify')->assertSuccessful();

    Mail::assertNothingSent();
});

// CN4 — already-notified rows are not re-sent (idempotent sweep).
it('does not re-send already-notified rows (CN4)', function () {
    Mail::fake();
    setRecipient();
    ContactMessage::factory()->notified()->create();

    $this->artisan('contact:notify')->assertSuccessful();

    Mail::assertNothingSent();
});

// F11 — with no recipient configured the run is a no-op: nothing sent, row
// untouched, no error surfaced.
it('skips the run when no recipient is configured (F11)', function () {
    Mail::fake();
    // contact_email left null.
    $row = ContactMessage::factory()->create();

    $this->artisan('contact:notify')->assertSuccessful();

    Mail::assertNothingSent();
    $row->refresh();
    expect($row->notified_at)->toBeNull()
        ->and($row->notify_attempts)->toBe(0);
});

// CN-schema — the new kChat columns cast correctly.
it('casts the kChat delivery columns', function () {
    $row = ContactMessage::factory()->create([
        'kchat_notified_at' => now(),
        'kchat_notify_attempts' => 2,
    ]);

    $row->refresh();

    expect($row->kchat_notified_at)->toBeInstanceOf(Carbon::class)
        ->and($row->kchat_notify_attempts)->toBe(2);
});

// CN5 — both rails deliver and both flags flip.
it('delivers email and kChat and flips both flags (CN5)', function () {
    Mail::fake();
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    setRecipient();
    setKchatWebhook();
    $row = ContactMessage::factory()->create();

    $this->artisan('contact:notify')->assertSuccessful();

    Mail::assertSent(ContactMessageMail::class);
    Http::assertSent(fn ($r) => $r->url() === 'https://kchat.test/hook');
    $row->refresh();
    expect($row->notified_at)->not->toBeNull()
        ->and($row->kchat_notified_at)->not->toBeNull();
});

// CN6 — email ok, kChat fails: email is never re-sent; only kChat retries.
it('retries only kChat when the ping fails, never re-emailing (CN6)', function () {
    Mail::fake();
    Http::fake(['https://kchat.test/hook' => Http::response('boom', 500)]);
    setRecipient();
    setKchatWebhook();
    $row = ContactMessage::factory()->create();

    $this->artisan('contact:notify')->assertSuccessful();
    $row->refresh();
    expect($row->notified_at)->not->toBeNull()
        ->and($row->kchat_notified_at)->toBeNull()
        ->and($row->kchat_notify_attempts)->toBe(1);

    // Second sweep: email must NOT be sent again; kChat retried.
    $this->artisan('contact:notify')->assertSuccessful();
    Mail::assertSent(ContactMessageMail::class, 1);
    expect($row->refresh()->kchat_notify_attempts)->toBe(2);
});

// CN7 — kChat ok, email fails: kChat is never re-pinged; only email retries.
it('retries only email when mail fails, never re-pinging (CN7)', function () {
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    setRecipient();
    setKchatWebhook();
    $row = ContactMessage::factory()->create();

    Mail::shouldReceive('to')->andReturnSelf();
    Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP down'));

    $this->artisan('contact:notify')->assertSuccessful();
    $row->refresh();
    expect($row->kchat_notified_at)->not->toBeNull()
        ->and($row->notified_at)->toBeNull()
        ->and($row->notify_attempts)->toBe(1);
    Http::assertSentCount(1);

    // kChat already delivered → not pinged again next sweep.
    $this->artisan('contact:notify')->assertSuccessful();
    Http::assertSentCount(1);
});

// CN8 — webhook unconfigured: email only, no HTTP call.
it('sends email only when no kChat webhook is configured (CN8)', function () {
    Mail::fake();
    Http::fake();
    setRecipient();
    setKchatWebhook(null);
    $row = ContactMessage::factory()->create();

    $this->artisan('contact:notify')->assertSuccessful();

    Mail::assertSent(ContactMessageMail::class);
    Http::assertNothingSent();
    $row->refresh();
    expect($row->notified_at)->not->toBeNull()
        ->and($row->kchat_notified_at)->toBeNull();
});

// CN9 — backfill guard: a row already kChat-delivered is never pinged.
it('never re-pings a row already marked kchat_notified (CN9)', function () {
    Mail::fake();
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    setRecipient();
    setKchatWebhook();
    ContactMessage::factory()->notified()->kchatNotified()->create();

    $this->artisan('contact:notify')->assertSuccessful();

    Http::assertNothingSent();
    Mail::assertNothingSent();
});

// CN10 — the dedicated contact webhook is used, not any other url.
it('posts to the configured contact webhook url (CN10)', function () {
    Mail::fake();
    Http::fake();
    setRecipient();
    setKchatWebhook('https://kchat.test/contact-hook');
    ContactMessage::factory()->create();

    $this->artisan('contact:notify')->assertSuccessful();

    Http::assertSent(fn ($r) => $r->url() === 'https://kchat.test/contact-hook');
});
