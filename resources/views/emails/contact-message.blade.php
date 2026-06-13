Nouveau message depuis aguet.dev
================================

De      : {{ $contactMessage->email }}
Sujet   : {{ $contactMessage->subject }}
Reçu le : {{ $contactMessage->created_at?->format('d.m.Y H:i') }}

--------------------------------
{{ $contactMessage->message }}
--------------------------------

Répondre directement à cet email écrit à {{ $contactMessage->email }}.
