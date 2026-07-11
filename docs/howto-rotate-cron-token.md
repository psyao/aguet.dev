# How to rotate the Infomaniak cron token

Rotate the secret that gates `GET /cron/{token}` — the HTTP trigger Infomaniak's
pseudo-cron hits every 15 minutes to run the `contact:notify` sweep. Do this if the
token may have leaked (e.g. exposed in a log export or screen share) or on routine
security hygiene.

**Staging and production each hold an independent `CRON_TOKEN`.** Rotating one
never touches the other — repeat these steps once per environment you're rotating.

## Prerequisites

- Access to the Doppler project (config `stg` for staging, the production config
  for production) — this is now the **only** place `CRON_TOKEN` should live. See
  [DEPLOY.md](../DEPLOY.md) for how deploys pull `.env` from Doppler.
- Access to Infomaniak's control panel to update the "Planificateur de tâches" URL
  for the environment you're rotating.

## Steps

1. Generate a new token (print-only — do **not** use `--force` here; that writes
   straight to whatever `.env` the command runs against, which the next deploy
   would silently overwrite anyway since `.env` is regenerated from Doppler on
   every deploy):

   ```bash
   php artisan cron:token
   ```

   This prints a new 48-character hex token without touching any file.

2. Paste the printed token into the `CRON_TOKEN` secret of the relevant Doppler
   config (`stg` for staging, the production config for production).

3. Deploy that environment (or otherwise trigger `config:cache` on the server) so
   the new value actually takes effect — `.env` on disk is regenerated from
   Doppler on every deploy, and `config('aguet.cron.token')` resolves to whatever
   was cached at the last `config:cache` run, not the live `.env` file. This is
   also why hand-editing `.env` over SSH doesn't work as a rotation mechanism: the
   very next deploy reverts it.

4. Update the corresponding Infomaniak "Planificateur de tâches" URL field to
   `https://<environment-domain>/cron/<new-token>` (15-minute interval, the
   host's minimum) — `aguet.dev` for production, `staging.aguet.dev` for staging.

## Verification

Hit the new URL once by hand (or wait for the next 15-minute tick) and confirm a
`200 OK` response body:

```bash
curl -i "https://<environment-domain>/cron/<new-token>"
```

A `404` means the token in the URL doesn't match what `config:cache` currently has —
re-deploy (or re-run `config:cache` on the server) so it picks up the value you just
put in Doppler. A `200 BUSY` means a run is already in flight (the overlap lock);
wait a few minutes and retry, it isn't an error.

## Troubleshooting

- **Old token still works after rotation** — the environment hasn't been
  redeployed since you updated Doppler (step 3), or the Doppler config you
  edited doesn't match the environment you're testing (`stg` vs production).
- **New token 404s everywhere** — confirm you pasted it into the right Doppler
  config, and that a deploy has actually run since (check the deploy's Kchat
  notification / GitHub Actions run for the environment).
- `cron:token --force` still exists for local development, where writing
  straight to a local `.env` is fine — it is **not** the production/staging
  rotation path (see step 1 above).
- See [Explanation: the contact message pipeline](explanation-contact-pipeline.md) for
  why the token exists and what it protects.
