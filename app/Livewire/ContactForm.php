<?php

namespace App\Livewire;

use App\Models\ContactMessage;
use App\Services\ContactMessageNotifier;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The terminal-prompt contact form, mounted inside the Alpine modal shell.
 *
 * DB-first: a valid submission writes a {@see ContactMessage} row before any
 * notification leaves, so a slow or dead endpoint can never lose a lead or hang
 * the request. The owner notification then fires on two rails (email + kChat)
 * via {@see ContactMessageNotifier}, dispatched with `defer()` so it runs after
 * this response is flushed, in the same PHP process — the host has no queue
 * worker, and `proc_open` is disabled so the queue 'background' connection is
 * unavailable. The `contact:notify` 15-min sweep is the durable backstop: if the
 * deferred run is torn down (e.g. the visitor navigates away mid-request) or a
 * rail fails, the sweep retries it.
 *
 * Spam is held off by three cheap gates, all inside the component (the route
 * `throttle` middleware does not apply — Livewire posts to /livewire/update):
 * a honeypot field, a minimum fill-time, and a per-IP rate limit. A rejected
 * spam submit shows the same success state as a real one, so a bot cannot tell
 * it was dropped.
 */
class ContactForm extends Component
{
    public string $subject = '';

    public string $email = '';

    public string $message = '';

    /** Honeypot: hidden from real users, irresistible to dumb bots. */
    public string $website = '';

    /** Unix timestamp captured at mount; guards against instant bot submits. */
    public int $startedAt = 0;

    public bool $sent = false;

    public bool $throttled = false;

    public ?string $generalError = null;

    public const MAX_MESSAGE = 5000;

    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    private const MIN_FILL_SECONDS = 2;

    public function mount(): void
    {
        $this->startedAt = now()->timestamp;
    }

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'message' => ['required', 'string', 'max:'.self::MAX_MESSAGE],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'required' => __('site.contact.form.err.required'),
            'email' => __('site.contact.form.err.email'),
            'max' => __('site.contact.form.err.max'),
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'subject' => __('site.contact.form.attr.subject'),
            'email' => __('site.contact.form.attr.email'),
            'message' => __('site.contact.form.attr.message'),
        ];
    }

    public function submit(): void
    {
        $this->reset(['throttled', 'generalError']);

        // Per-IP rate limit. request()->ip() returns REMOTE_ADDR (the real
        // client): Infomaniak passes it directly and X-Forwarded-For is not
        // trusted, so the key cannot be spoofed to dodge the limit.
        $key = self::rateLimitKey(request()->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->throttled = true;

            return;
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        // A filled honeypot or an implausibly fast submit is silently accepted:
        // the bot sees success, but nothing is validated or stored.
        $looksAutomated = $this->website !== ''
            || (now()->timestamp - $this->startedAt) < self::MIN_FILL_SECONDS;

        if ($looksAutomated) {
            $this->markSent();

            return;
        }

        $validated = $this->validate();

        try {
            $message = ContactMessage::create([
                'subject' => $this->sanitize($validated['subject']),
                'email' => $validated['email'],
                'message' => $validated['message'],
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->generalError = __('site.contact.form.error');

            return;
        }

        // Deliver the owner notification after this response is flushed (same
        // process, no worker). The row is already saved, so the contact:notify
        // sweep still delivers it if this deferred run never completes.
        defer(fn () => app(ContactMessageNotifier::class)->deliver($message));

        $this->markSent();
    }

    /** Clear the form and flip to the success state. */
    private function markSent(): void
    {
        $this->reset(['subject', 'email', 'message', 'website']);
        $this->sent = true;
    }

    /**
     * Start over: clears the form and re-arms the fill-time gate. Used by the
     * "write another" button and the "annuler" (cancel) button. Dismissing the
     * modal via ✕ / ESC / backdrop only hides it and preserves the draft.
     */
    public function resetForm(): void
    {
        $this->reset(['subject', 'email', 'message', 'website', 'sent', 'throttled', 'generalError']);
        $this->resetValidation();
        $this->startedAt = now()->timestamp;
    }

    /**
     * Rate-limit key for a client IP. IPv6 collapses to its /64 prefix (one ISP
     * allocation) so a visitor can't rotate through addresses in their own block
     * for fresh limits; IPv4 keys on the full address. inet_pton normalizes
     * compressed forms (::) before slicing, which a naive explode(':') would not.
     */
    public static function rateLimitKey(?string $ip): string
    {
        $packed = $ip !== null ? @inet_pton($ip) : false;

        if ($packed !== false && strlen($packed) === 16) {
            return 'contact-form:'.bin2hex(substr($packed, 0, 8)).'::/64';
        }

        return 'contact-form:'.($ip ?? 'unknown');
    }

    /** Collapse control characters so they cannot poison the mail subject. */
    private function sanitize(string $value): string
    {
        return trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value));
    }

    public function render(): View
    {
        return view('livewire.contact-form');
    }
}
