<?php

use App\Support\Content;

it('renders hero accent from markdown italic', function () {
    expect((string) Content::heroTitle('*x*'))->toContain('<em>x</em>');
});

it('renders hero bold from markdown bold', function () {
    expect((string) Content::heroTitle('**x**'))->toContain('<strong>x</strong>');
});

it('converts hero newlines to br', function () {
    expect((string) Content::heroTitle("a\nb"))->toContain('<br');
});

it('strips raw html in hero title', function () {
    expect((string) Content::heroTitle('<script>alert(1)</script>'))
        ->not->toContain('<script');
});

it('renders inline markdown without a paragraph wrapper', function () {
    $html = (string) Content::md('*x*');
    expect($html)->toContain('<em>x</em>')
        ->and($html)->not->toContain('<p>');
});

it('handles null safely', function () {
    expect((string) Content::md(null))->toBe('')
        ->and((string) Content::heroTitle(null))->toBe('');
});
