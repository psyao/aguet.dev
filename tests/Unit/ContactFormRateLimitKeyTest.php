<?php

use App\Livewire\ContactForm;

it('buckets IPv6 addresses by their /64 prefix', function () {
    $a = ContactForm::rateLimitKey('2a02:aa15:37e:8e00:d141:399e:2ac6:3085');
    $b = ContactForm::rateLimitKey('2a02:aa15:37e:8e00::1'); // same /64, compressed
    $c = ContactForm::rateLimitKey('2a02:aa15:37e:8e01::1'); // different /64

    expect($a)->toBe($b)
        ->and($a)->not->toBe($c);
});

it('keys IPv4 on the full address', function () {
    expect(ContactForm::rateLimitKey('127.0.0.1'))->toBe('contact-form:127.0.0.1')
        ->and(ContactForm::rateLimitKey('127.0.0.1'))
        ->not->toBe(ContactForm::rateLimitKey('127.0.0.2'));
});

it('handles a null or unparseable IP without crashing', function () {
    expect(ContactForm::rateLimitKey(null))->toBe('contact-form:unknown')
        ->and(ContactForm::rateLimitKey('not-an-ip'))->toBe('contact-form:not-an-ip');
});
