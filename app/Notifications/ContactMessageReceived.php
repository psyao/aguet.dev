<?php

namespace App\Notifications;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Notifications\Channels\KChatChannel;
use App\Notifications\Channels\KChatMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Owner notification for a contact-form submission, delivered to kChat via the
 * custom KChatChannel. Deliberately does NOT implement ShouldQueue: the queue
 * driver is `database` with no worker, so it must send synchronously — the sweep
 * calls notifyNow().
 */
class ContactMessageReceived extends Notification
{
    public function __construct(public ContactMessage $contactMessage) {}

    /** @return list<class-string> */
    public function via(object $notifiable): array
    {
        return [KChatChannel::class];
    }

    public function toKChat(object $notifiable): KChatMessage
    {
        $message = $this->contactMessage;

        return (new KChatMessage)
            ->title('New contact message')
            ->color('#3ecf8e')
            ->link('Open in inbox', ContactMessageResource::getUrl(name: 'index', panel: 'admin'))
            ->body(Str::limit((string) $message->message, 500))
            ->field('Subject', (string) $message->subject)
            ->field('From', (string) $message->email)
            ->field('Application', config('app.name'))
            ->field('Environment', app()->environment());
    }
}
