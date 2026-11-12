<?php

/*
| The messages the contact Form can show, in French: Laravel ships English only,
| and the rest of its messages fall back to it. The attributes also label the
| fields in the owner's mail, written in the Admin language.
*/
return [
    'required' => ':attribute est requis.',
    'email' => 'Entre une adresse email valide.',
    'max' => [
        'string' => ':attribute est trop long (:max caractères max).',
    ],

    'attributes' => [
        'subject' => 'Le sujet',
        'email' => 'L’email',
        'message' => 'Le message',
    ],
];
