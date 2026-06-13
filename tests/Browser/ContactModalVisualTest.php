<?php

// Contact-modal open-state baselines. Mirrors the cmdk pattern: open the modal
// by clicking the contact CTA, confirm it actually opened (the "./contact.sh"
// prompt only renders inside the open dialog), then capture. Run against
// /?screenshot=1 so animations are frozen and the real font is restored.
//
// Only the OPEN state is captured here. The per-field error state needs a
// Livewire round-trip (/livewire/update), which the browser harness cannot do
// reliably because SESSION_DRIVER=array does not persist the CSRF token across
// requests. That state's behaviour is covered by the component test
// "rejects invalid input and writes nothing (F3)" in tests/Feature.

it('matches the contact modal open state (FR, desktop)', function () {
    $page = visit('/?screenshot=1')
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->wait(1);

    $page->click('.cta');

    $page->assertSee('./contact.sh')
        ->wait(1)
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
        ->assertScreenshotMatches(fullPage: false);
});
