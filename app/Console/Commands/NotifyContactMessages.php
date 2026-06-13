<?php

namespace App\Console\Commands;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\SiteContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Out-of-band delivery of owner notifications for contact-form submissions.
 *
 * The Livewire component never sends mail in the request path; it just writes
 * the row. This sweep — scheduled every 15 minutes (the Infomaniak pseudo-cron
 * floor) — picks up undelivered rows and emails them. A send failure is not
 * fatal: the attempt is counted and logged, `notified_at` stays null, and the
 * next run retries until the attempt cap is reached. So a transient SMTP
 * outage self-heals and a permanent one stops hammering after {@see MAX_ATTEMPTS}.
 */
class NotifyContactMessages extends Command
{
    protected $signature = 'contact:notify';

    protected $description = 'Email the owner about undelivered contact-form submissions';

    /** Give up after this many failed sends so a dead SMTP is not hammered forever. */
    public const MAX_ATTEMPTS = 5;

    public function handle(): int
    {
        $recipient = SiteContent::current()->contact_email;

        $pending = ContactMessage::query()
            ->whereNull('notified_at')
            ->where('notify_attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending contact messages.');

            return self::SUCCESS;
        }

        // A missing recipient is a misconfiguration, not a per-message failure:
        // skip the whole run, leave rows untouched, and surface it loudly.
        if (blank($recipient)) {
            Log::warning('contact:notify skipped — SiteContent.contact_email is not set.', [
                'pending' => $pending->count(),
            ]);
            $this->warn('No recipient configured (SiteContent.contact_email); skipping.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($pending as $message) {
            try {
                Mail::to($recipient)->send(new ContactMessageMail($message));
                $message->forceFill(['notified_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                $message->increment('notify_attempts');
                Log::warning('contact:notify failed to send a message; will retry.', [
                    'id' => $message->id,
                    'attempts' => $message->notify_attempts,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} of {$pending->count()} pending contact message(s).");

        return self::SUCCESS;
    }
}
