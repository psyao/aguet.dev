<?php

/*
| Latest deployed commit, baked at deploy time by the "Generate build info"
| step in .github/workflows/deploy.yml. The committed default holds nulls so
| local dev and `config:cache` never break before a deploy has run. The
| commit subject is base64-encoded ('message_b64') and decoded server-side.
*/

return [
    'sha' => null,
    'message_b64' => null,
    'date' => null,
];
