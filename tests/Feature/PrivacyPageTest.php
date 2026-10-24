<?php

it('renders the privacy policy in French at /privacy', function () {
    $response = $this->get('/privacy');

    $response->assertOk();
    $response->assertSee('<html lang="fr">', false);
    $response->assertSee('<h1>Politique de confidentialité</h1>', false);
    $response->assertSee('<link rel="canonical" href="'.url('/privacy').'">', false);
    $response->assertHeader('Content-Security-Policy');
});

it('renders the privacy policy in English at /en/privacy', function () {
    $response = $this->get('/en/privacy');

    $response->assertOk();
    $response->assertSee('<html lang="en">', false);
    $response->assertSee('<h1>Privacy policy</h1>', false);
});

it('answers the privacy policy as Markdown to agents asking for it', function () {
    $response = $this->get('/en/privacy', ['Accept' => 'text/markdown']);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    expect($response->getVary())->toContain('Accept');
    expect($response->getContent())->toStartWith('# Privacy policy');
});

it('publishes an llms.txt with when-to-use guidance', function () {
    $llms = file_get_contents(public_path('llms.txt'));

    expect($llms)
        ->toStartWith("# Steve Aguet\n\n> ")
        ->toContain("\n## When to use this\n")
        ->toContain('- [Privacy policy](https://aguet.dev/en/privacy)');
});
