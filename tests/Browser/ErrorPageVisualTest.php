<?php

// French 404 baseline: an unknown path renders aguet's own self-contained
// errors/404 view, answered through Kami's fallback route under its CSP.

it('matches the French 404 baseline', function () {
    visit('/this-route-does-not-exist')
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->assertSee('404 : route introuvable')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});
