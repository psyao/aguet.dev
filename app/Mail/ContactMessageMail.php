<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The owner notification for a contact-form submission. Sent out of band by
 * the `contact:notify` sweep (never in the request path). Plain text only —
 * the visitor-supplied message is rendered as text, never HTML, and the
 * subject is stripped of control characters so it cannot inject extra mail
 * headers. The visitor's address goes on Reply-To (a structured field), never
 * into From, so DMARC alignment on the sending domain holds.
 */
class ContactMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        // Collapse any CR/LF/control chars; Symfony also guards against this,
        // but stripping here keeps the subject line clean and predictable.
        $subject = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $this->contactMessage->subject);
        $subject = trim($subject) ?: 'Nouveau message';

        return new Envelope(
            subject: '[Contact] '.$subject,
            replyTo: [new Address($this->contactMessage->email)],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact-message',
        );
    }
}
