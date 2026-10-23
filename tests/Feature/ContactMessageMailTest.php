<?php

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Support\Carbon;

it('renders as plain text, replies to the visitor, and strips header injection from the subject', function () {
    $message = ContactMessage::factory()->make([
        'subject' => "Hello\r\nBcc: evil@example.com",
        'email' => 'visitor@example.com',
        'message' => "Line one\nLine two with émoji 🚀 and a zero-width\u{200B}char",
    ]);
    $message->created_at = now();

    $mail = new ContactMessageMail($message);
    $envelope = $mail->envelope();

    // Subject is collapsed to a single header-safe line. The injection vector
    // is the CR/LF that would START a new header; stripping it is the defence.
    // The literal "Bcc:" text surviving inline in a one-line subject is inert.
    expect($envelope->subject)
        ->not->toContain("\r")
        ->not->toContain("\n")
        ->toContain('Hello');

    // Visitor address rides Reply-To, never From.
    expect($envelope->replyTo[0]->address)->toBe('visitor@example.com');

    // Body carries the message verbatim, rendered as text.
    $mail->assertHasSubject($envelope->subject);
    $mail->assertSeeInText('Line one');
    $mail->assertSeeInText('🚀');
    $mail->assertSeeInText('visitor@example.com');
});

it('dates the message in Zurich time, stored in UTC', function () {
    $message = ContactMessage::factory()->make();
    $message->created_at = Carbon::parse('2026-07-01 22:30', 'UTC');

    (new ContactMessageMail($message))->assertSeeInText('Reçu le : 02.07.2026 00:30');
});
