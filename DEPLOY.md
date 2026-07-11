# Laravel → Infomaniak Deploy Pipeline

A hardened, reusable GitHub Actions pipeline for deploying a **Laravel** app to **Infomaniak shared hosting** over SSH/rsync. It builds assets in CI (Infomaniak shared hosting has no Node), isolates deployment secrets from third-party dependency code, and runs the server-side release steps with automatic recovery.

Drop these files into any Laravel repo, set a handful of secrets, do a one-time server setup, and push to `main`.

## Contents

```
.github/
├── workflows/
│   ├── deploy-template.yml    Shared build → deploy → summary logic (workflow_call)
│   ├── deploy-staging.yml     Auto-deploy to staging on CI-green push to main
│   └── deploy-production.yml  Manual-only deploy to production
└── actions/
    ├── setup-stack/      Composite: PHP + Node/pnpm setup, install, audit, build
    └── notify-kchat/     Composite: chat webhook notification
scripts/
└── deploy.sh             Server-side release script — runs after rsync
```

---

## How it works

> The shape below (build → deploy) is shared by both environments via
> `deploy-template.yml` — see [Environments](#environments) for what
> triggers each one, how `notify` fits in, and how they differ.

Each deploy runs `deploy-template.yml` as **two jobs** in sequence, followed by a `summary` job that exposes both results to the caller:

1. **`build`** — installs PHP and JS dependencies, audits them, builds the front-end assets, and packages the deployable files into an artifact. This job runs **all third-party/dependency code**, and holds **only** low-value credentials (a private Composer registry login, if you use one).
2. **`deploy`** — downloads the artifact, generates `.env`, rsyncs to the server, and runs `scripts/deploy.sh`. This job holds the **high-value** secrets (the SSH key, and a secrets-manager token if used) but runs **no** dependency code.

`notify` — always runs; reports overall success/failure (across both build and deploy) to a chat webhook — is **not** part of `deploy-template.yml`. It lives in each trigger workflow (`deploy-staging.yml`/`deploy-production.yml`) instead, since it needs event context (`workflow_run`/`workflow_dispatch`) the reusable template doesn't have. See [Environments](#environments).

**What gets deployed:** app code, compiled assets, `scripts/deploy.sh`, and a fresh `.env`. `vendor/` and `storage/` are **never** rsynced — `vendor/` is rebuilt on the server by `deploy.sh`, and `storage/` holds server-side state (uploads, logs, sessions) that must survive deploys.

---

## Environments

There are two deploy targets, each a GitHub Environment (`Settings → Environments`)
with its own scoped secrets and its own Doppler config:

| Environment | Trigger | Workflow | Deploys |
|---|---|---|---|
| `staging` | Automatic — every CI-green push to `main` | `deploy-staging.yml` | Whatever CI just validated |
| `production` | Manual only — Actions tab or `gh workflow run deploy-production.yml` | `deploy-production.yml` | `main`'s current tip at trigger time (re-verified against a green CI run for that exact SHA) |

Both call the same `deploy-template.yml` reusable workflow (`workflow_call`,
inputs: `environment`, `sha`) — the build/deploy/summary logic lives once;
only the trigger and the target environment differ. `notify` deliberately
stays in each trigger workflow, not the template, since it needs the
`workflow_run`/`workflow_dispatch` event context the template doesn't have.
`deploy-production.yml` cannot
be dispatched from a branch other than `main` (hard failure, not a silent
skip) and re-checks that the resolved SHA has a successful `CI` run before
deploying, since it doesn't get that gate for free the way `workflow_run`
gives staging.

**Environment-mismatch guard:** `scripts/deploy.sh` takes an optional second
argument (the expected `APP_ENV`) and refuses to proceed — before touching
`composer install`, maintenance mode, or the database — if the `.env` that
just landed doesn't match it. This guards against a swapped or typo'd
`DEPLOY_PATH` secret shipping one environment's code and `.env` over the
other.

**Known gap:** there is no dedicated rollback mechanism yet. The only
recovery path today is re-running `deploy-production.yml` once `main` is
fixed forward, or manually SSHing in per the
[Recovery from a failed deploy](#recovery-from-a-failed-deploy) section
below.

---

## Why this shape (Infomaniak constraints)

These are the platform facts the pipeline is built around — useful to understand before adapting it:

- **No Node.js on shared hosting** → front-end assets must be built in CI and shipped; you cannot run `pnpm run build` on the server.
- **Document root must point at `public/`** → set this when creating the site in the Infomaniak manager (the rsync ships `public/` into the app root, and the site folder targets `…/public`).
- **The SSH/CLI PHP may differ from the per-site web PHP** → the server steps call an explicit binary via `DEPLOY_PHP_BIN` (default `/opt/php8.5/bin/php`) instead of bare `php`.
- **`proc_open` / shell functions must be enabled** for Composer to run on the server (toggle in the site's PHP settings).
- **`vendor/` is rebuilt server-side** by `deploy.sh` (with `--optimize-autoloader`), so it is excluded from the rsync.

---

## Adopting it in a project

1. Copy the files above into your repo, preserving paths.
2. Work through the [customization points](#customization-points).
3. Configure [secrets & variables](#secrets--variables).
4. Do the [first-deploy server setup](#first-deploy-one-time-server-setup).
5. Resolve action pins (`pinact run`) — see [Maintenance](#maintenance).
6. Push to `main`.

### Customization points

| Area | Where | What to change |
|---|---|---|
| **PHP version** | `setup-stack/action.yml`, `deploy.sh`, `DEPLOY_PHP_BIN` var | Match your target PHP (template uses 8.5). Keep all three in sync. |
| **rsync include list** | `deploy-template.yml` → *Sync files* | The `--include=` list is the standard Laravel tree. Add/remove top-level paths your app ships. The trailing `--exclude='*'` is what protects server-side `vendor/` and `storage/` from `--delete` — keep it. |
| **`.env` source** | `deploy-template.yml` → *Generate .env* | Template uses **Doppler** (`doppler secrets download`). If you don't use Doppler, replace this step — e.g. compose `.env` from GitHub secrets, or decrypt a committed `.env` via `php artisan env:decrypt`. The `.env` must end up in `release/.env` before the rsync. |
| **Private Composer registry** | `setup-stack/action.yml` → *Add credentials* + `deploy.sh` | Template configures one private satis repo via `composer config http-basic …`. If your app has no private packages, delete that step (and its inputs), and drop the matching server-side `composer config` from first-deploy setup. |
| **Notifications** | `deploy-staging.yml`/`deploy-production.yml` → `notify` job + `notify-kchat` | Each trigger workflow posts to **Kchat** (Infomaniak chat). Swap the composite for Slack/Discord/etc., or remove the `notify` job entirely, in both files. |
| **pnpm version** | `package.json` `packageManager` field | Set e.g. `"packageManager": "pnpm@11.x.y"`. `setup-stack` reads it (no `version:` is hardcoded). |
| **Node version** | `setup-stack/action.yml` | Template uses Node 24. |
| **CI gating** | `deploy-staging.yml` `on:` and `deploy-production.yml`'s `resolve-sha` job | See [Environments](#environments) — staging gates on `workflow_run` conclusion, production re-checks CI via the Checks API (see note below). |

> **CI gating:** `deploy-staging.yml` only proceeds when the triggering `CI` `workflow_run` concluded successfully. `deploy-production.yml` has no automatic CI trigger to piggyback on (it's `workflow_dispatch`-only), so it re-verifies a green `CI` run for the resolved SHA itself via the GitHub Checks API before deploying. Both still assume **branch protection** requires CI to pass before a merge into `main` — make sure `main` is protected against direct pushes (including from admins), or the re-check step is your only remaining backstop.

### Secrets & variables

**Environment secrets** — set once per GitHub Environment
(`Settings → Environments → staging` and `Settings → Environments → production`,
each with its own values):

| Name | Required | Description |
|---|---|---|
| `DEPLOY_HOST` | Yes | SSH hostname of the Infomaniak server for this environment |
| `DEPLOY_USER` | Yes | SSH username for this environment |
| `DEPLOY_PATH` | Yes | Absolute path to this environment's app root on the server |
| `DEPLOY_SSH_KEY` | Yes | Private SSH key for this environment — see [first-deploy setup](#first-deploy-one-time-server-setup) |
| `DEPLOY_KNOWN_HOSTS` | Yes | Pinned server host key for strict host-key checking |
| `DEPLOY_PORT` | No | SSH port for this environment — defaults to `22` |
| `DOPPLER_TOKEN` | If using Doppler | Service token scoped to this environment's Doppler config |

**Environment variables** — also set per GitHub Environment (`Settings →
Environments → <name> → Environment variables`, not secrets — it isn't
sensitive):

| Name | Required | Description |
|---|---|---|
| `DEPLOY_PHP_BIN` | No | Absolute PHP binary path on this environment's server — defaults to `/opt/php8.5/bin/php` |

**Repository secrets** — `Settings → Secrets and variables → Actions → Secrets`:

| Name | Required | Description |
|---|---|---|
| `KCHAT_WEBHOOK_URL` | If using notifications | Chat incoming webhook URL |
| `COMPOSER_AUTH_USER` / `COMPOSER_AUTH_PASS` | If using a private Composer registry | Login for your satis/private repo (named `FILACHECK_*` in the template) |

### First-deploy one-time server setup

**1. Private Composer credentials (only if you use a private registry)**

`composer install` runs on the server (in `deploy.sh`) and needs credentials for any private repo:

```bash
composer config --global http-basic.YOUR_SATIS_HOST USERNAME PASSWORD
```

**2. SSH deploy key**

Use a **dedicated** key (trivially revocable, scoped to deploys only):

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-deploy@your-app" -f ~/.ssh/deploy_key -N ""

# Authorize it on the server
cat ~/.ssh/deploy_key.pub | ssh USER@HOST "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"

# Put the PRIVATE key into the DEPLOY_SSH_KEY environment secret
cat ~/.ssh/deploy_key

# Verify
ssh -i ~/.ssh/deploy_key USER@HOST
```

**3. Pinned SSH host key (`DEPLOY_KNOWN_HOSTS`)**

The deploy connects with `StrictHostKeyChecking=yes` against a pinned key instead of trusting the host on first connect. Capture it once:

```bash
ssh-keyscan -p PORT HOST   # PORT defaults to 22
```

Paste the full output into the `DEPLOY_KNOWN_HOSTS` environment secret. Re-capture if the server is rebuilt or its host key rotates.

---

## Security posture

The pipeline is built to limit the blast radius of a supply-chain compromise (a malicious dependency or hijacked action):

- **Build/deploy secret isolation.** All untrusted code (Composer/pnpm dependencies, the asset build) runs in the `build` job, which holds only low-value credentials. High-value secrets — the SSH key and any secrets-manager token (which can unlock your entire production secret set) — live only in the `deploy` job, which runs no dependency code. A poisoned dependency therefore cannot reach them.
- **Pinned actions.** Third-party actions are pinned to full commit SHAs, not mutable tags. Run [`pinact run`](https://github.com/suzuki-shunsuke/pinact) after editing any workflow to (re)pin, and keep [Dependabot](https://docs.github.com/en/code-security/dependabot) on for the `github-actions` ecosystem to bump pins safely. Any `uses:` line carrying a `# TODO: pin` comment must be resolved before shipping.
- **Egress monitoring.** Each job runs [`step-security/harden-runner`](https://github.com/step-security/harden-runner) in `audit` mode. Once the egress baseline is known, switch the `deploy` job to `block` mode with an allowlist (GitHub, your secrets manager, and the server) to make exfiltration impossible at the network layer.
- **Fail-closed dependencies.** `pnpm install --frozen-lockfile` (no unreviewed version resolution); `composer audit --no-dev` fails the build on known CVEs in production dependencies; `composer validate --strict` catches lockfile drift.
- **Pinned host key.** The deploy uses `StrictHostKeyChecking=yes` against `DEPLOY_KNOWN_HOSTS` rather than trusting the host on first connection.

---

## Operations

### Monitoring

- **GitHub Actions** → the Deploy Staging / Deploy Production workflow, with per-job logs (resolve-sha / build / deploy / notify).
- **Chat webhook** — notification on every run, success or failure.

### Recovery from a failed deploy

`scripts/deploy.sh` traps `EXIT` and runs `php artisan up` automatically — even on failure — so a broken deploy **does not strand the site in maintenance mode**. Manual recovery should rarely be needed:

```bash
php artisan up
```

Caveat the trap can't fix: if `php artisan migrate --force` failed partway, the schema may be partially applied. The site will be back up, but you may need to SSH in and reconcile the migration state by hand.

---

## Maintenance

- **Pin actions before shipping.** Run `pinact run` to resolve every `# TODO: pin` to a SHA. The template ships a few verified pins (harden-runner, checkout); the rest are tagged for you to pin.
- **Verify action majors resolve.** Some pinned majors (e.g. `actions/cache`, `actions/setup-node`) may be ahead of what older docs show — confirm they resolve in your account before relying on them.
- **`composer audit` will eventually fail an unrelated commit.** It checks live advisory databases at build time, so a newly disclosed CVE can block a deploy on a commit that touched nothing related. That is the gate working. To ship an urgent unrelated hotfix while you triage, temporarily append `|| true` to the audit step, or scope it further.

---

## Server requirements (Infomaniak shared hosting)

- SSH access enabled on the hosting plan.
- PHP set per-site to your target version (template: **8.5**), with `proc_open` / shell functions enabled.
- The site's document root pointing at the app's `public/` directory.
- Composer available on the server (the release script invokes `composer2.phar` next to the PHP binary — adjust the path in `deploy.sh` if your host differs).
