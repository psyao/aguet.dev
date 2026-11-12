<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Posts a notification's card to the kChat (Mattermost) incoming webhook in
 * `services.kchat.contact_webhook_url`.
 *
 * Failure model: a blank/invalid URL or a non-2xx response THROWS, so Kami
 * records the failed delivery and retries it from a Tick. The webhook URL is a
 * secret and is never put into the thrown message, which Kami stores.
 */
class KChatChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $url = config('services.kchat.contact_webhook_url');

        // Only http(s) — FILTER_VALIDATE_URL alone would accept ftp:// etc.
        if (! is_string($url) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('kChat webhook URL is missing or invalid.');
        }

        /** @var KChatMessage $message */
        $message = $notification->toKChat($notifiable);

        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                // Recover from a transient blip (reset, brief 5xx) in-process so it
                // doesn't burn one of Kami's attempts or wait ~15 min for the next
                // Tick. throw: false lets a persistent HTTP error fall
                // through to the failed() check below with the URL still hidden.
                ->retry([100, 500], throw: false)
                ->asJson()
                ->post($url, $message->toPayload());
        } catch (\Throwable $e) {
            // Connection/timeout/TLS errors (ConnectionException) carry the
            // URL/host in their message — never re-expose it. Keep only the
            // exception class as a hint.
            throw new RuntimeException('kChat webhook request failed: '.class_basename($e));
        }

        if ($response->failed()) {
            // Carry status + a short body excerpt for diagnosis — never the URL.
            throw new RuntimeException(
                'kChat webhook returned HTTP '.$response->status().': '
                .substr((string) $response->body(), 0, 200)
            );
        }
    }
}
