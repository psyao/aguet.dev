# Explanation: the contact message pipeline

The contact modal's form, its anti-spam gates, the owner notification and the
Infomaniak planner that backstops them are one system, built around a single
constraint: **this app runs on shared hosting with no queue worker and no real cron.**
Infomaniak's task planner can only call a URL, every 15 minutes at best. Almost all of
the machinery is Kami's; aguet declares the Form and the notification.

## The flow

```
POST (contact modal, Accept: application/json)
        │
        ▼
App\Forms\ContactForm  ── honeypot / throttle / rules()
        │
        ▼
Kami stores the Submission + one delivery per channel   (one transaction)
        │
        ▼  after the response, through defer()
ContactMessageReceived  ──  mail  +  kChat card (when configured)
        │
        ▼  a channel failed?
kami:forms:deliver, in the Tick  ◄──  planner: GET /kami/tick/{token}
```

- **`App\Forms\ContactForm`** (handle `contact_form`) declares the fields' `rules()`,
  the recipient (the Home Page's shared `contact_email`) and the reply-to field.
  Kami adds the honeypot and the per-IP throttle (an IPv6 address counts with its
  /64), and answers a bot caught by the honeypot with the same success as a real
  Submission. `resources/js/contact-form.js` posts the form with
  `Accept: application/json`, so the modal shows the errors or the confirmation
  without a reload; without JavaScript the form posts a full page.
- **Kami stores each Submission** before anything is sent, and keeps it 12 months (its
  default retention). The admin lists them on **Messages reçus**.
- **`App\Notifications\ContactMessageReceived`** is sent after the response, through
  `defer()`, in the same PHP process: Kami's own mail, plus a kChat card through
  `App\Notifications\Channels\KChatChannel` while `services.kchat.contact_webhook_url`
  is set. Each channel is delivered and retried on its own, so a kChat outage never
  blocks the mail. It is never `ShouldQueue`: nothing would drain the queue, and Kami's
  retry would no longer see a failure.
- **The Tick** runs Kami's scheduled tasks (`Kami::schedule()` in `routes/console.php`):
  `kami:forms:deliver` retries a failed delivery (up to 5 attempts), and
  `kami:forms:prune` applies the retention. Infomaniak's planner starts it by calling
  `/kami/tick/{token}`; `php artisan kami:tick:url` prints that URL and the
  `KAMI_TICK_TOKEN` line for `.env`. A wrong or empty token answers 404.

## Why this shape

- **No worker, no cron.** A queued notification would wait forever, and
  `schedule:run` never fires. Sending after the response keeps the visitor's answer
  fast; the Tick is the actual delivery guarantee.
- **At least once.** A mail server that accepted a mail but answered too late gets it
  again. A duplicate notification is a minor annoyance; a lost lead is not.
- **Untrusted text in Markdown.** The kChat card renders the visitor's subject and
  message: `KChatMessage::neutralize()` breaks `@mentions` and escapes Markdown first.
  `KChatChannel` accepts only an http(s) webhook URL and never puts it in an error
  message, which Kami stores with the failed delivery.

## Related

- Kami's guides: [Forms](https://github.com/psyao/kami/blob/main/docs/guide/forms.md)
  (validation, anti-spam, delivery, retries, retention) and
  [Scheduler](https://github.com/psyao/kami/blob/main/docs/guide/scheduler.md) (the
  Tick and its URL).
- [Reference: admin panel internals](reference-admin-panel.md)
