<?php

// Contact-modal open-state baselines. Mirrors the cmdk pattern: open the modal
// by clicking the contact CTA, confirm it actually opened (the "./contact.sh"
// prompt only renders inside the open dialog), then capture. Run against
// /?screenshot=1 so animations are frozen and the real font is restored.
//
// The Kami Form posts a full page; its confirmation and its errors come back
// on a fresh load, where app.js opens the modal by itself (tests below).

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

it('opens the modal by itself over the errors of a refused message', function () {
    $page = visit('/');

    $page->click('.cta')
        ->type('#subject', 'Bonjour')
        ->type('#email', 'visitor@example.com')
        ->type('#message', str_repeat('a', 3))
        ->script("document.getElementById('email').value = 'not-an-email'; document.getElementById('email').type = 'text'");

    $page->click('.cf-actions button[type=submit]')
        ->assertVisible('#contact-modal-panel')
        ->assertSee('Entre une adresse email valide.')
        ->assertNoJavaScriptErrors();
});
