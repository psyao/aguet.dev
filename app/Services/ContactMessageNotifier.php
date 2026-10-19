<?php

namespace App\Services;

use App\Console\Commands\NotifyContactMessages;
use App\Livewire\ContactForm;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Velox\Cms\Models\Entry;

/**
 * Delivers the owner notification for a contact-form submission on two
 * independent rails — email (notified_at / notify_attempts) and kChat
 * (kchat_notified_at / kchat_notify_attempts). Each rail flips its own flag on
 * success and counts failures in its own counter, so the rails never block each
 * other.
 *
 * Two callers share this logic:
 *  - the after-response defer() path in {@see ContactForm} (near-
 *    instant delivery for a single fresh row, no queue worker required); and
 *  - the {@see NotifyContactMessages} sweep (the durable
 *    15-min backstop that retries anything the deferred run didn't flag).
 *
 * Delivery is at-least-once: a rail sends, then saves its flag.
 */
class ContactMessageNotifier
{
    /** Give up on a rail after this many failed sends so a dead endpoint is not hammered forever. */
    public const MAX_ATTEMPTS = 5;

    /**
     * Deliver every pending rail for a single message, resolving the recipient
     * and webhook itself. Used by the after-response defer() path. Safe to call
     * more than once: a rail already flagged delivered is skipped.
     */
    public function deliver(ContactMessage $message): void
    {
        $recipient = self::recipient();
        $webhook = config('services.kchat.contact_webhook_url');

        if (blank($recipient)) {
            Log::warning('Contact message not emailed: the Home Page has no contact_email.', ['id' => $message->id]);
        } elseif ($this->emailPending($message)) {
            $this->deliverEmail($message, $recipient);
        }

        if (filled($webhook) && $this->kchatPending($message)) {
            $this->deliverKChat($message, $webhook);
        }
    }

    /** The owner's address: the Home Page's contact_email, entered once for every Locale. */
    public static function recipient(): ?string
    {
        return Entry::homePage()?->contact_email;
    }

    public function emailPending(ContactMessage $message): bool
    {
        return $message->notified_at === null && $message->notify_attempts < self::MAX_ATTEMPTS;
    }

    public function kchatPending(ContactMessage $message): bool
    {
        return $message->kchat_notified_at === null && $message->kchat_notify_attempts < self::MAX_ATTEMPTS;
    }

    /** Email rail. Returns true on success. */
    public function deliverEmail(ContactMessage $message, string $recipient): bool
    {
        try {
            Mail::to($recipient)->send(new ContactMessageMail($message));
            $message->forceFill(['notified_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            $message->increment('notify_attempts');
            Log::warning('contact:notify failed to email a message; will retry.', [
                'id' => $message->id,
                'attempts' => $message->notify_attempts,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** kChat rail. Returns true on success. The channel redacts the URL from errors. */
    public function deliverKChat(ContactMessage $message, string $webhook): bool
    {
        try {
            Notification::route('kchat', $webhook)
                ->notifyNow(new ContactMessageReceived($message));
            $message->forceFill(['kchat_notified_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            $message->increment('kchat_notify_attempts');
            Log::warning('contact:notify failed to ping kChat; will retry.', [
                'id' => $message->id,
                'attempts' => $message->kchat_notify_attempts,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
