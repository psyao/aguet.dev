<?php

// Proves the in-process browser server renders the seeded home page, i.e. the
// served HTTP request and the test share the file-backed DB. No visual baseline
// is trustworthy until this passes.

it('renders the seeded home page through a real browser request', function () {
    $page = visit('/');

    $page->assertSee('cvci')      // seeded project legend (ProjectSeeder)
        ->assertSee('Filament');  // seeded tag (a SkillGroup tag)
});
