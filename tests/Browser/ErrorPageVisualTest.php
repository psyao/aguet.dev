<?php

// French 404 baseline: an unknown path renders aguet's own self-contained
// errors/404 view. Velox's catch-all route will answer unknown paths through
// that view, so this baseline gates the move (psyao/velox#335).

it('matches the French 404 baseline', function () {
    visit('/this-route-does-not-exist')
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->assertSee('404 : route introuvable')
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});
