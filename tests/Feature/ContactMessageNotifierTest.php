<?php

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\SiteContent;
use App\Services\ContactMessageNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.kchat.contact_webhook_url' => null]);
});

function notifier(): ContactMessageNotifier
{
    return app(ContactMessageNotifier::class);
}

// CMN1 — deliver() runs both rails when both are configured.
it('delivers both rails and flips both flags (CMN1)', function () {
    Mail::fake();
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);
    $row = ContactMessage::factory()->create();

    notifier()->deliver($row);

    Mail::assertSent(ContactMessageMail::class);
    Http::assertSent(fn ($r) => $r->url() === 'https://kchat.test/hook');
    $row->refresh();
    expect($row->notified_at)->not->toBeNull()
        ->and($row->kchat_notified_at)->not->toBeNull();
});

// CMN2 — deliver() skips the email rail when no recipient is configured.
it('skips the email rail when no recipient is set (CMN2)', function () {
    Mail::fake();
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    // contact_email left null.
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);
    $row = ContactMessage::factory()->create();

    notifier()->deliver($row);

    Mail::assertNothingSent();
    Http::assertSent(fn ($r) => $r->url() === 'https://kchat.test/hook');
    $row->refresh();
    expect($row->notified_at)->toBeNull()
        ->and($row->kchat_notified_at)->not->toBeNull();
});

// CMN3 — deliver() skips the kChat rail when no webhook is configured.
it('skips the kChat rail when no webhook is configured (CMN3)', function () {
    Mail::fake();
    Http::fake();
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    $row = ContactMessage::factory()->create();

    notifier()->deliver($row);

    Mail::assertSent(ContactMessageMail::class);
    Http::assertNothingSent();
    $row->refresh();
    expect($row->notified_at)->not->toBeNull()
        ->and($row->kchat_notified_at)->toBeNull();
});

// CMN4 — deliver() is a no-op for an already-delivered row (idempotent).
it('does not re-deliver an already-delivered row (CMN4)', function () {
    Mail::fake();
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);
    $row = ContactMessage::factory()->notified()->kchatNotified()->create();

    notifier()->deliver($row);

    Mail::assertNothingSent();
    Http::assertNothingSent();
});

// CMN5 — a failed kChat ping increments only its own counter, leaves it pending.
it('increments the kChat counter on failure without touching email (CMN5)', function () {
    Mail::fake();
    Http::fake(['https://kchat.test/hook' => Http::response('boom', 500)]);
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);
    $row = ContactMessage::factory()->create();

    notifier()->deliver($row);

    $row->refresh();
    expect($row->notified_at)->not->toBeNull()         // email succeeded
        ->and($row->kchat_notified_at)->toBeNull()      // kChat failed
        ->and($row->kchat_notify_attempts)->toBe(1);
});
