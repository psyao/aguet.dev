<?php

namespace App\Notifications\Channels;

/**
 * Fluent builder for a kChat (Mattermost incoming-webhook) "attachment" card.
 * Mirrors the Slack-style payload the build pipeline posts
 * (.github/actions/notify-kchat/action.yml): {attachments: [{color, title, fields, text}]}.
 *
 * Visitor-supplied values (subject, from, body) are UNTRUSTED. Mattermost renders
 * Markdown and @mentions, so field()/body() neutralize them: standalone @mentions
 * are broken with a zero-width space and Markdown control chars are escaped. The
 * inbox link added by link() is system-generated and is the only Markdown we emit
 * intentionally.
 */
class KChatMessage
{
    private string $title = '';

    private string $color = '';

    private string $body = '';

    private ?string $linkLabel = null;

    private ?string $linkUrl = null;

    /** @var list<array{title: string, value: string, short: bool}> */
    private array $fields = [];

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function color(string $hex): static
    {
        $this->color = $hex;

        return $this;
    }

    /** Add a field. $value is untrusted and is neutralized. */
    public function field(string $title, string $value, bool $short = true): static
    {
        $this->fields[] = [
            'title' => $title,
            'value' => self::neutralize($value),
            'short' => $short,
        ];

        return $this;
    }

    /** Set the card body. $text is untrusted and is neutralized. */
    public function body(string $text): static
    {
        $this->body = self::neutralize($text);

        return $this;
    }

    /** Append a trusted Markdown link to the body (system-generated URL only). */
    public function link(string $label, string $url): static
    {
        $this->linkLabel = $label;
        $this->linkUrl = $url;

        return $this;
    }

    /**
     * De-fang untrusted text so it cannot mention, link, or format the channel:
     * break @mentions that begin a token (preserving emails like a@b.com), then
     * escape Markdown control characters.
     */
    public static function neutralize(string $value): string
    {
        // Insert a zero-width space after an @ that starts a token.
        $value = preg_replace('/(^|\s)@/u', "$1@\u{200B}", $value);

        // Escape Markdown control chars (breaks links, code, emphasis, quotes, tables).
        return preg_replace('/([\\\\`*_~\[\]()>|#])/u', '\\\\$1', $value);
    }

    /** @return array{attachments: list<array<string, mixed>>} */
    public function toPayload(): array
    {
        $text = $this->body;

        if ($this->linkUrl !== null) {
            $label = $this->linkLabel ?? 'Open';
            $text = trim($text."\n\n[".$label.']('.$this->linkUrl.')');
        }

        $attachment = array_filter([
            'color' => $this->color,
            'title' => $this->title,
            'fields' => $this->fields,
            'text' => $text,
        ], fn ($v) => $v !== '' && $v !== []);

        return ['attachments' => [$attachment]];
    }
}
