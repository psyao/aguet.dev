<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

function assertThemedErrorPage(int $status, string $codeText): void
{
    Route::get('/__test-throw-'.$status, fn () => abort($status));

    $response = test()->get('/__test-throw-'.$status);

    $response->assertStatus($status);
    $response->assertSee($codeText, false);
}

it('renders the themed 403 page', function () {
    assertThemedErrorPage(403, '403');
});

it('renders the themed 404 page', function () {
    assertThemedErrorPage(404, '404');
});

it('renders the themed 419 page', function () {
    assertThemedErrorPage(419, '419');
});

it('renders the themed 429 page', function () {
    assertThemedErrorPage(429, '429');
});

it('renders the themed 500 page', function () {
    assertThemedErrorPage(500, '500');
});

it('renders the themed 503 page for a raw thrown exception', function () {
    assertThemedErrorPage(503, '503');
});

it('renders the themed 503 page for artisan-down maintenance mode (distinct render path)', function () {
    Artisan::call('down');

    try {
        $response = test()->get('/');
        $response->assertStatus(503);
        $response->assertSee('503', false);
    } finally {
        Artisan::call('up');
    }
});

it('renders a 404 under an /en/ path in English, even though SetLocale never runs on unmatched routes', function () {
    $response = test()->get('/en/this-route-does-not-exist');

    $response->assertStatus(404);
    $response->assertSee('404: route not found', false);
});

it('renders a 404 under the default path in French', function () {
    $response = test()->get('/this-route-does-not-exist');

    $response->assertStatus(404);
    $response->assertSee('404 : route introuvable', false);
});

it('resolves error copy through the translation system in both locales', function () {
    app()->setLocale('fr');
    expect(__('site.errors.404.title'))->toBe('404 : route introuvable');

    app()->setLocale('en');
    expect(__('site.errors.404.title'))->toBe('404: route not found');
});

it('renders the 500 page with no Vite build output present at all', function () {
    $manifest = public_path('build/manifest.json');
    $hadManifest = file_exists($manifest);
    $backup = $hadManifest ? file_get_contents($manifest) : null;

    if ($hadManifest) {
        unlink($manifest);
    }

    try {
        Route::get('/__test-throw-500-no-build', fn () => abort(500));
        $response = test()->get('/__test-throw-500-no-build');

        $response->assertStatus(500);
        $response->assertSee('500', false);
    } finally {
        if ($hadManifest && $backup !== null) {
            file_put_contents($manifest, $backup);
        }
    }
});
