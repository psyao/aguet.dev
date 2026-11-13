<?php

use Psyao\Kami\Forms\Submission;

// Contact-modal open-state baselines. Mirrors the cmdk pattern: open the modal
// by clicking the contact CTA, confirm it actually opened (the "./contact.sh"
// prompt only renders inside the open dialog), then capture. Run against
// /?screenshot=1 so animations are frozen and the real font is restored.
//
// resources/js/contact-form.js submits the Kami Form without a reload, through
// its JSON answers, so the modal stays open over the errors or the
// confirmation (tests below), each state with its baseline. Without
// JavaScript the Form posts a full page, and app.js opens the modal by itself
// over the result: tests/Feature/ContactFormTest.php covers that page, and the
// `?sent=` test below its opening.

it('matches the contact modal open state (FR, desktop)', function () {
    $page = visit('/?screenshot=1')
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->wait(1);

    $page->click('.cta');

    $page->assertSee('./contact.sh')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches(fullPage: false);
});

it('matches the contact modal open state (EN, desktop)', function () {
    $page = visit('/en?screenshot=1')
        ->withTimezone('Europe/Zurich')
        ->withLocale('en-GB')
        ->wait(1);

    $page->click('.cta');

    $page->assertSee('./contact.sh')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches(fullPage: false);
});

it('matches the contact modal open state (FR, mobile)', function () {
    $page = visit('/?screenshot=1')
        ->on()->mobile()
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->wait(1);

    $page->click('.cta');

    $page->assertSee('./contact.sh')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches(fullPage: false);
});

it('matches the contact modal open state (EN, mobile)', function () {
    $page = visit('/en?screenshot=1')
        ->on()->mobile()
        ->withTimezone('Europe/Zurich')
        ->withLocale('en-GB')
        ->wait(1);

    $page->click('.cta');

    $page->assertSee('./contact.sh')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches(fullPage: false);
});

it('opens the modal by itself over the confirmation of a sent message', function () {
    visit('/?sent=contact_form')
        ->assertVisible('#contact-modal-panel')
        ->assertSee('Message envoyé. Merci — je te réponds bientôt.')
        ->assertNoJavaScriptErrors();
});

it('keeps the modal open over the errors of a refused message, without a reload', function () {
    $page = visit('/?screenshot=1')
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->wait(1);

    $page->click('.cta')
        ->type('#subject', 'Bonjour')
        ->type('#email', 'visitor@example.com')
        ->type('#message', str_repeat('a', 3))
        ->script("window.notReloaded = true; document.getElementById('email').value = 'not-an-email'; document.getElementById('email').type = 'text'");

    $page->click('.cf-actions button[type=submit]')
        ->assertSeeIn('#email-error', 'Entre une adresse email valide.')
        ->assertVisible('#contact-modal-panel')
        ->assertAttribute('#email', 'aria-invalid', 'true')
        ->assertScript('window.notReloaded', true)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches(fullPage: false);

    expect(Submission::count())->toBe(0);
});

it('shows the confirmation in the open modal, without a reload', function () {
    $page = visit('/?screenshot=1')
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->wait(1);

    $page->click('.cta')
        ->type('#subject', 'Bonjour')
        ->type('#email', 'visitor@example.com')
        ->type('#message', 'I would like to work with you.')
        ->script('window.notReloaded = true');

    $page->click('.cf-actions button[type=submit]')
        ->assertSeeIn('#contact_form-status', 'Message envoyé. Merci — je te réponds bientôt.')
        ->assertVisible('#contact-modal-panel')
        ->assertMissing('#contact_form')
        ->assertScript('window.notReloaded', true)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches(fullPage: false);

    expect(Submission::sole()->data['subject'])->toBe('Bonjour');
});

it('says in the open modal that a message could not be sent, and keeps it', function () {
    $page = visit('/');

    // An origin the route cannot find answers 422 with no field errors, like a 403 or a 429.
    $page->click('.cta')
        ->type('#subject', 'Bonjour')
        ->type('#email', 'visitor@example.com')
        ->type('#message', 'I would like to work with you.')
        ->script("window.notReloaded = true; document.querySelector('[name=_kami_origin]').value = 'unknown:fr'");

    $page->click('.cf-actions button[type=submit]')
        ->assertSeeIn('#contact_form-status', 'Le message n’est pas parti. Réessaie dans un moment.')
        ->assertVisible('#contact_form')
        ->assertValue('#message', 'I would like to work with you.')
        ->assertScript('window.notReloaded', true)
        ->assertNoJavaScriptErrors();

    expect(Submission::count())->toBe(0);
});

it('offers a new, empty message once the sent one is closed', function () {
    $page = visit('/');

    $page->click('.cta')
        ->type('#subject', 'Bonjour')
        ->type('#email', 'visitor@example.com')
        ->type('#message', 'I would like to work with you.');

    $page->click('.cf-actions button[type=submit]')
        ->assertSeeIn('#contact_form-status', 'Message envoyé.');

    $page->click('[data-cf-done]')
        ->click('.cta')
        ->assertVisible('#contact_form')
        ->assertValue('#subject', '')
        ->assertSeeNothingIn('#contact_form-status')
        ->assertMissing('[data-cf-done]')
        ->assertNoJavaScriptErrors();
});

it('offers a new message from the page of a message sent without JavaScript', function () {
    visit('/?sent=contact_form')
        ->assertSee('Message envoyé.')
        ->click('[data-cf-done]')
        ->click('.cta')
        ->assertVisible('#contact_form')
        ->assertSeeNothingIn('#contact_form-status')
        ->assertNoJavaScriptErrors();
});
