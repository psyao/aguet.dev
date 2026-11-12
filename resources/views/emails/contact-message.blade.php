Nouveau message depuis aguet.dev
================================

De      : {{ $contactMessage->email }}
Sujet   : {{ $contactMessage->subject }}
Reçu le : {{ $contactMessage->created_at?->timezone(config('kami.sites.default.timezone'))->format('d.m.Y H:i') }}

--------------------------------
{{ $contactMessage->message }}
--------------------------------

Répondre directement à cet email écrit à {{ $contactMessage->email }}.
