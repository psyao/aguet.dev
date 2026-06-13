<?php

use App\Console\Commands\NotifyContactMessages;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/** Set the owner address the sweep delivers to. */
function setRecipient(string $email = 'owner@example.com'): void
{
    SiteContent::current()->update(['contact_email' => $email]);
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

    expect($row->kchat_notified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($row->kchat_notify_attempts)->toBe(2);
});
