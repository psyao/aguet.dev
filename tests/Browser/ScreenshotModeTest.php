<?php

// Screenshot mode (?screenshot=1, testing-only) must settle the page into a
// deterministic state so visual baselines are stable.

it('settles the page deterministically in screenshot mode', function () {
    $page = visit('/?screenshot=1');

    // Seeded content is visible (the boot intro is skipped, nothing stays hidden).
    $page->assertSee('cvci')
        // Clock is frozen: a live clock shows the real HH:MM and would essentially
        // never read a static "00:00", so this proves $shot reached the statusbar
        // include AND the Alpine clock was not wired.
        ->assertSee('00:00')
        ->assertNoJavaScriptErrors();
});
