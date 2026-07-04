<?php

// ⌘K command-palette open-state baseline. Open it by clicking the chrome's
// ".kbtn" button (wired to $store.cmdk.open() in partials/chrome.blade.php) —
// more reliable than a synthetic Ctrl+K chord in headless Chromium.

it('matches the command palette open-state baseline', function () {
    $page = visit('/?screenshot=1')
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->wait(1);

    $page->click('.kbtn');

    // Confirm the palette actually opened before capturing: "esc" only renders
    // (x-show) when the palette is open.
    $page->assertSee('esc')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

// Print: pest-plugin-browser 4.x has no print-media emulation, so print cannot be
// captured automatically. It is covered by the manual print-to-PDF check in
// docs/visual-testing.md. Recorded here so the gap is never read as "covered".
it('covers print rendering', function () {
    // intentionally empty — see skip reason
})->skip('No print-media emulation in pest-plugin-browser; print is manual-only. See docs/visual-testing.md.');
