<?php

use App\Notifications\Channels\KChatMessage;

it('builds a Slack-style attachment payload', function () {
    $payload = (new KChatMessage)
        ->title('New contact message')
        ->color('#2563eb')
        ->field('Subject', 'Hello')
        ->field('From', 'a@b.com')
        ->body('Body text')
        ->toPayload();

    expect($payload)->toHaveKey('attachments')
        ->and($payload['attachments'])->toHaveCount(1);

    $att = $payload['attachments'][0];
    expect($att['color'])->toBe('#2563eb')
        ->and($att['title'])->toBe('New contact message')
        ->and($att['fields'])->toHaveCount(2)
        ->and($att['fields'][0])->toMatchArray(['title' => 'Subject', 'value' => 'Hello', 'short' => true])
        ->and($att['text'])->toBe('Body text');
});

it('drops empty attachment keys', function () {
    $att = (new KChatMessage)->title('Only title')->toPayload()['attachments'][0];

    expect($att)->toHaveKey('title')
        ->and($att)->not->toHaveKey('fields')
        ->and($att)->not->toHaveKey('text')
        ->and($att)->not->toHaveKey('color');
});

it('breaks @mentions but preserves email addresses', function () {
    $att = (new KChatMessage)
        ->field('From', 'visitor@example.com')
        ->body('Ping @channel and @here now')
        ->toPayload()['attachments'][0];

    expect($att['fields'][0]['value'])->toContain('visitor@example.com'); // mid-token @ preserved
    expect($att['text'])->toContain("@\u{200B}channel")
        ->and($att['text'])->toContain("@\u{200B}here");
});

it('escapes Markdown so links and formatting cannot be injected', function () {
    $text = (new KChatMessage)
        ->body('[click](http://evil.test) `code` *bold*')
        ->toPayload()['attachments'][0]['text'];

    expect($text)->toContain('\\[click\\]')
        ->and($text)->toContain('\\(http://evil.test\\)')
        ->and($text)->toContain('\\`code\\`')
        ->and($text)->toContain('\\*bold\\*');
});

it('appends a trusted Markdown link to the body', function () {
    $text = (new KChatMessage)
        ->body('Message')
        ->link('Open in inbox', 'https://aguet.dev/admin/contact-messages')
        ->toPayload()['attachments'][0]['text'];

    expect($text)->toContain('[Open in inbox](https://aguet.dev/admin/contact-messages)');
});
