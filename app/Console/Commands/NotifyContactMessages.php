<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use App\Models\SiteContent;
use App\Services\ContactMessageNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Out-of-band delivery of owner notifications for contact-form submissions.
 *
 * The Livewire component never notifies in the request path; it just writes the
 * row. This sweep — scheduled every 15 minutes (the Infomaniak pseudo-cron floor)
 * with withoutOverlapping — picks up undelivered rows and delivers them on two
 * independent rails: email (notified_at / notify_attempts) and kChat
 * (kchat_notified_at / kchat_notify_attempts). Each rail flips its own flag on
 * success and counts failures in its own counter, retrying until its cap. The
 * rails never block each other: a kChat outage cannot withhold or duplicate email.
 * Delivery is at-least-once (send then save), bounded by withoutOverlapping.
 */
class NotifyContactMessages extends Command
{
    protected $signature = 'contact:notify';

    protected $description = 'Notify the owner (email + kChat) about undelivered contact-form submissions';

    /** Give up on a rail after this many failed sends so a dead endpoint is not hammered forever. */
    public const MAX_ATTEMPTS = ContactMessageNotifier::MAX_ATTEMPTS;

    public function handle(ContactMessageNotifier $notifier): int
    {
        $recipient = SiteContent::current()->contact_email;
        $webhook = config('services.kchat.contact_webhook_url');
        $kchatConfigured = filled($webhook);

        $pending = ContactMessage::query()
            ->where(function ($query) {
                $query->whereNull('notified_at')
                    ->where('notify_attempts', '<', self::MAX_ATTEMPTS);
            })
            ->when($kchatConfigured, function ($query) {
                $query->orWhere(function ($query) {
                    $query->whereNull('kchat_notified_at')
                        ->where('kchat_notify_attempts', '<', self::MAX_ATTEMPTS);
                });
            })
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending contact messages.');

            return self::SUCCESS;
        }

        // A missing recipient is a misconfiguration of the email rail only — it no
        // longer aborts the run, so the kChat rail can still deliver.
        if (blank($recipient)) {
            Log::warning('contact:notify — SiteContent.contact_email is not set; email rail skipped.', [
                'pending' => $pending->count(),
            ]);
            $this->warn('No recipient configured (SiteContent.contact_email); email rail skipped.');
        }

        $emailed = 0;
        $pinged = 0;

        foreach ($pending as $message) {
            if (filled($recipient) && $notifier->emailPending($message) && $notifier->deliverEmail($message, $recipient)) {
                $emailed++;
            }

            if ($kchatConfigured && $notifier->kchatPending($message) && $notifier->deliverKChat($message, $webhook)) {
                $pinged++;
            }
        }

        $this->info("contact:notify — emailed {$emailed}, pinged kChat {$pinged} (of {$pending->count()} row(s)).");

        return self::SUCCESS;
    }
}
