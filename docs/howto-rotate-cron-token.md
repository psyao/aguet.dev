# How to rotate the Infomaniak cron token

Rotate the secret that gates `GET /cron/{token}` — the HTTP trigger Infomaniak's
pseudo-cron hits every 15 minutes to run the `contact:notify` sweep. Do this if the
token may have leaked (e.g. exposed in a log export or screen share) or on routine
security hygiene.

## Prerequisites

- SSH access to the production server (see [DEPLOY.md](../DEPLOY.md) for connection
  details).
- Access to Infomaniak's control panel to update the "Planificateur de tâches" URL.

## Steps

1. SSH into the server and generate a new token, writing it straight into `.env`:

   ```bash
   php artisan cron:token --force
   ```

   This prints the new 48-character hex token and replaces any existing
   `CRON_TOKEN=` line in `.env`. Without `--force` it only prints a token —
   useful for previewing without mutating anything.

2. Rebuild the config cache so the new value actually takes effect:

   ```bash
   php artisan config:cache
   ```

   Editing `.env` alone does nothing if `config:cache` was ever run in production —
   `config('aguet.cron.token')` keeps resolving to whatever was cached at the last
   `config:cache` run, not the live `.env` file.

3. Update Infomaniak's "Planificateur de tâches" URL field to
   `https://aguet.dev/cron/<new-token>` (15-minute interval, the host's minimum).

## Verification

Hit the new URL once by hand (or wait for the next 15-minute tick) and confirm a
`200 OK` response body:

```bash
curl -i "https://aguet.dev/cron/<new-token>"
```

A `404` means the token in the URL doesn't match what `config:cache` currently has —
re-run step 2. A `200 BUSY` means a run is already in flight (the overlap lock); wait
a few minutes and retry, it isn't an error.

## Troubleshooting

- **Old token still works after rotation** — you skipped `config:cache` (step 2), or
  the app is caching config from before the rotation. Re-run `php artisan config:cache`.
- **`cron:token --force` fails with a read/write error** — `.env` is missing, or the
  deploying user lacks permission on it. The command refuses to silently create a
  fresh `.env` in production; fix the file's permissions rather than working around
  the check.
- See [Explanation: the contact message pipeline](explanation-contact-pipeline.md) for
  why the token exists and what it protects.
