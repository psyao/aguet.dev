<?php

namespace App\Notifications;

use App\Forms\ContactForm;
use App\Notifications\Channels\KChatChannel;
use App\Notifications\Channels\KChatMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Psyao\Kami\Forms\Submission;
use Psyao\Kami\Forms\SubmissionReceived;

/**
 * Tells the owner about a contact Submission: Kami's own mail, plus a kChat
 * card while `services.kchat.contact_webhook_url` is set. Kami delivers and
 * retries each channel on its own. Never `ShouldQueue`: the host runs no worker,
 * and Kami's retry would no longer see a failure.
 */
class ContactMessageReceived extends Notification
{
    public function __construct(private ContactForm $form, private Submission $submission) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return filled(config('services.kchat.contact_webhook_url'))
            ? ['mail', KChatChannel::class]
            : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new SubmissionReceived($this->form, $this->submission))->toMail($notifiable);
    }

    public function toKChat(object $notifiable): KChatMessage
    {
        $data = $this->submission->data;

        return (new KChatMessage)
            ->title('New contact message')
            ->color('#3ecf8e')
            ->link('Open in the admin', route('filament.admin.resources.submissions.view', $this->submission))
            ->body(Str::limit((string) ($data['message'] ?? ''), 500))
            ->field('Subject', (string) ($data['subject'] ?? ''))
            ->field('From', (string) ($data['email'] ?? ''))
            ->field('Application', (string) config('app.name'))
            ->field('Environment', app()->environment());
    }
}
