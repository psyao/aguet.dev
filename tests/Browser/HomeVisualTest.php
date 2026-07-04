<?php

// Full-page visual baselines of the CURRENT terminal design (FR/EN, desktop +
// mobile). Run against /?screenshot=1 so animations are frozen, the clock/year
// are static, and the real JetBrains Mono font is restored over Pest's Arial
// injection. Pin timezone + locale for reproducibility.

it('matches the French home baseline on desktop', function () {
    visit('/?screenshot=1')
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('matches the English home baseline on desktop', function () {
    visit('/en?screenshot=1')
        ->withTimezone('Europe/Zurich')
        ->withLocale('en-GB')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('matches the French home baseline on mobile', function () {
    visit('/?screenshot=1')
        ->on()->mobile()
        ->withTimezone('Europe/Zurich')
        ->withLocale('fr-CH')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});

it('matches the English home baseline on mobile', function () {
    visit('/en?screenshot=1')
        ->on()->mobile()
        ->withTimezone('Europe/Zurich')
        ->withLocale('en-GB')
        ->wait(1)
        ->assertNoJavaScriptErrors()
        ->assertScreenshotMatches();
});
