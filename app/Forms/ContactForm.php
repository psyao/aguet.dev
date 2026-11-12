<?php

namespace App\Forms;

use App\ContentTypes\Home;
use App\Notifications\ContactMessageReceived;
use Illuminate\Notifications\Notification;
use Psyao\Kami\Content\Entry;
use Psyao\Kami\Forms\Form;
use Psyao\Kami\Forms\Submission;

/**
 * The contact modal's terminal-prompt form. Kami stores each Submission, keeps
 * it 12 months (its default retention), and delivers it after the response
 * through {@see ContactMessageReceived}: a mail and, when configured, a kChat card.
 */
final class ContactForm extends Form
{
    public const int MAX_MESSAGE = 5000;

    /** Not `contact`: the Form's id would clash with the `#contact` section, a public anchor. */
    public function handle(): string
    {
        return 'contact_form';
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'message' => ['required', 'string', 'max:'.self::MAX_MESSAGE],
        ];
    }

    /**
     * The Home's shared `contact_email`, read whatever the Home's visibility in
     * the current Locale: a delivery runs after the response or inside a Tick.
     *
     * @return list<string>
     */
    public function recipients(): array
    {
        $email = Entry::query()->ofType(Home::class)->withUnpublished()->first()?->contact_email;

        return filled($email) ? [$email] : [];
    }

    public function replyTo(): string
    {
        return 'email';
    }

    public function notification(Submission $submission): Notification
    {
        return new ContactMessageReceived($this, $submission);
    }
}
