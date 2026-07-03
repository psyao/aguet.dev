<?php

use App\Livewire\ContactForm;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    RateLimiter::clear('contact-form:'.request()->ip());
});

/** Fill in a valid submission and clear the min-fill-time gate. */
function validSubmit(): Testable
{
    $component = Livewire::test(ContactForm::class)
        ->set('subject', 'Bonjour')
        ->set('email', 'visitor@example.com')
        ->set('message', 'I would like to work with you.');

    test()->travel(3)->seconds();

    return $component;
}

// F1 — a valid submit persists the row, with no mail in the request path.
it('persists a valid submission without sending mail (F1)', function () {
    validSubmit()->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    $row = ContactMessage::sole();
    expect($row->subject)->toBe('Bonjour')
        ->and($row->email)->toBe('visitor@example.com')
        ->and($row->notified_at)->toBeNull();

    Mail::assertNothingSent();
});

// F2 — the row is the source of truth; capture is decoupled from delivery.
it('writes the row and never touches SMTP in the request (F2)', function () {
    validSubmit()->call('submit')->assertSet('sent', true);

    expect(ContactMessage::count())->toBe(1)
        ->and(ContactMessage::sole()->notified_at)->toBeNull();
    Mail::assertNothingSent();
});

// F3 — validation errors block the write.
it('rejects invalid input and writes nothing (F3)', function () {
    $c = Livewire::test(ContactForm::class)
        ->set('subject', '')
        ->set('email', 'not-an-email')
        ->set('message', '');
    test()->travel(3)->seconds();

    $c->call('submit')
        ->assertHasErrors(['subject', 'email', 'message'])
        ->assertSet('sent', false);

    expect(ContactMessage::count())->toBe(0);
});

// F4 — a filled honeypot is silently dropped (indistinguishable from success).
it('silently drops a honeypot submission (F4)', function () {
    $c = Livewire::test(ContactForm::class)
        ->set('subject', 'Bonjour')
        ->set('email', 'bot@example.com')
        ->set('message', 'spam spam spam')
        ->set('website', 'http://spam.example');
    test()->travel(3)->seconds();

    $c->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    expect(ContactMessage::count())->toBe(0);
});

// F4b — an implausibly fast submit (under the min fill-time) is also dropped.
it('silently drops an instant submission (F4b)', function () {
    // No time travel: elapsed fill-time is ~0s, below the threshold.
    Livewire::test(ContactForm::class)
        ->set('subject', 'Bonjour')
        ->set('email', 'fast@example.com')
        ->set('message', 'too fast to be human')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    expect(ContactMessage::count())->toBe(0);
});

// F5 — the 6th submit within a minute is throttled.
it('throttles after five submits in a minute (F5)', function () {
    $c = Livewire::test(ContactForm::class);
    test()->travel(3)->seconds();

    for ($i = 1; $i <= 5; $i++) {
        $c->set('subject', "Message {$i}")
            ->set('email', "v{$i}@example.com")
            ->set('message', 'A genuine message here.')
            ->call('submit')
            ->assertSet('throttled', false);
    }

    // 6th attempt: blocked, no further row.
    $c->set('subject', 'Message 6')
        ->set('email', 'v6@example.com')
        ->set('message', 'A genuine message here.')
        ->call('submit')
        ->assertSet('throttled', true);

    expect(ContactMessage::count())->toBe(5);
});

// F6 — the rate limiter is keyed per client IP. request()->ip() resolves the
// real client behind the proxy because trustProxies is configured (Task 5);
// here we assert the keying scheme itself is per-IP.
it('keys the rate limiter on the client IP (F6)', function () {
    $key = 'contact-form:'.request()->ip();
    expect(RateLimiter::attempts($key))->toBe(0);

    validSubmit()->call('submit')->assertSet('sent', true);

    expect(RateLimiter::attempts($key))->toBe(1);
});

// F7 — validation messages follow the active locale.
it('renders locale-aware validation messages (F7)', function () {
    app()->setLocale('en');
    $en = Livewire::test(ContactForm::class)->set('subject', '');
    test()->travel(3)->seconds();
    $en->call('submit')->assertSee('The subject is required.');

    app()->setLocale('fr');
    $fr = Livewire::test(ContactForm::class)->set('subject', '');
    test()->travel(3)->seconds();
    $fr->call('submit')->assertSee('Le sujet est requis.');
});

// F8 — only the three visitor fields are mass-assignable.
it('refuses to mass-assign bookkeeping columns (F8)', function () {
    $row = ContactMessage::create([
        'subject' => 's',
        'email' => 'e@example.com',
        'message' => 'm',
        'read_at' => now(),
        'notified_at' => now(),
        'id' => 999,
    ]);

    expect($row->read_at)->toBeNull()
        ->and($row->notified_at)->toBeNull()
        ->and($row->id)->not->toBe(999);
});

// F9 — header-injection characters are stripped from the stored subject.
it('strips header-injection characters from the subject (F9)', function () {
    $c = Livewire::test(ContactForm::class)
        ->set('subject', "Hello\r\nBcc: evil@example.com")
        ->set('email', 'visitor@example.com')
        ->set('message', 'A genuine message.');
    test()->travel(3)->seconds();

    $c->call('submit')->assertSet('sent', true);

    $subject = ContactMessage::sole()->subject;
    expect($subject)->not->toContain("\r")->not->toContain("\n");
});

// The "annuler" (cancel) button clears the draft; ✕/ESC/backdrop only hide the
// modal (handled client-side) and preserve what was typed.
it('clears the form when cancelled', function () {
    Livewire::test(ContactForm::class)
        ->set('subject', 'half-typed')
        ->set('email', 'wip@example.com')
        ->set('message', 'unfinished thought')
        ->call('resetForm')
        ->assertSet('subject', '')
        ->assertSet('email', '')
        ->assertSet('message', '')
        ->assertSet('sent', false);
});

// F10 — long Unicode input round-trips through the DB intact.
it('round-trips long Unicode input (F10)', function () {
    $emoji = '🚀';                 // 4-byte
    $zw = "\u{200B}";              // zero-width space
    $body = mb_substr(str_repeat('é', 4990).$emoji.$zw.'end', 0, 5000);

    $c = Livewire::test(ContactForm::class)
        ->set('subject', 'Unicode test')
        ->set('email', 'visitor@example.com')
        ->set('message', $body);
    test()->travel(3)->seconds();

    $c->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    expect(ContactMessage::sole()->message)->toBe($body);
});

// F12 — the owner notification is dispatched via defer() after the response.
// withoutDefer() runs the deferred callback inline so we can assert it; in
// production it fires after fastcgi_finish_request, with the sweep as backstop.
it('delivers the owner notification via deferred dispatch (F12)', function () {
    $this->withoutDefer();
    Http::fake(['https://kchat.test/hook' => Http::response('', 200)]);
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);

    validSubmit()->call('submit')->assertSet('sent', true);

    $row = ContactMessage::sole();
    Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->contactMessage->is($row));
    Http::assertSent(fn ($r) => $r->url() === 'https://kchat.test/hook');
    expect($row->notified_at)->not->toBeNull()
        ->and($row->kchat_notified_at)->not->toBeNull();
});

// F13 — a configured submit arms the progress bar: row id + pending rails.
// defer() does not fire in the test (no app termination), so the flags stay
// null and both rails read pending — exactly the state the bar starts from.
it('arms the delivery progress bar with pending rails (F13)', function () {
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);

    $c = validSubmit()->call('submit')->assertSet('sent', true);

    $c->assertSet('messageId', ContactMessage::sole()->id)
        ->assertSet('rails', ['email' => 'pending', 'kchat' => 'pending'])
        ->assertSet('deliveryDone', false);
});

// F14 — polling flips each rail to ok as its flag is set; done when none pend.
it('marks rails delivered as their flags flip (F14)', function () {
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    config(['services.kchat.contact_webhook_url' => 'https://kchat.test/hook']);

    $c = validSubmit()->call('submit');
    $row = ContactMessage::sole();

    $row->forceFill(['notified_at' => now()])->save();
    $c->call('refreshDelivery')
        ->assertSet('rails', ['email' => 'ok', 'kchat' => 'pending'])
        ->assertSet('deliveryDone', false);

    $row->forceFill(['kchat_notified_at' => now()])->save();
    $c->call('refreshDelivery')
        ->assertSet('rails', ['email' => 'ok', 'kchat' => 'ok'])
        ->assertSet('deliveryDone', true);
});

// F15 — a rail that exhausts its attempts shows as failed and stops polling.
it('marks a rail failed once it exhausts its attempts (F15)', function () {
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    config(['services.kchat.contact_webhook_url' => null]);

    $c = validSubmit()->call('submit');
    ContactMessage::sole()->forceFill(['notify_attempts' => 5])->save();

    $c->call('refreshDelivery')
        ->assertSet('rails', ['email' => 'fail'])
        ->assertSet('deliveryDone', true);
});

// F16 — polling gives up after the cap, leaving a pending rail to the sweep.
it('stops polling after the cap and leaves pending rails queued (F16)', function () {
    SiteContent::current()->update(['contact_email' => 'owner@example.com']);
    config(['services.kchat.contact_webhook_url' => null]);

    $c = validSubmit()->call('submit');

    for ($i = 0; $i < 12; $i++) {
        $c->call('refreshDelivery');
    }

    $c->assertSet('deliveryDone', true)
        ->assertSet('rails', ['email' => 'pending']);
});
