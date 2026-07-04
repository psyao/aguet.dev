<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Custom notification channel that posts a card to a kChat (Mattermost) incoming
 * webhook. Routed on-demand:
 *   Notification::route('kchat', $webhookUrl)->notifyNow(new ContactMessageReceived($m)).
 *
 * Failure model: a blank/invalid URL or a non-2xx response THROWS so the caller
 * (the contact:notify sweep) counts the failure and retries. The webhook URL is a
 * secret and is never put into the thrown message.
 */
class KChatChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        // AnonymousNotifiable::routeNotificationFor() takes one argument.
        $url = $notifiable->routeNotificationFor('kchat');

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
                // doesn't burn one of the sweep's MAX_ATTEMPTS or wait ~15 min for
                // the next tick. throw: false lets a persistent HTTP error fall
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
