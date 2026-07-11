#!/usr/bin/env bash
set -euo pipefail   # exit on error, treat unset vars as errors, catch pipe failures

PHP_BIN="${1:-/opt/php8.5/bin/php}"
EXPECTED_ENV="${2:-}"
COMPOSER_BIN="$(dirname "$PHP_BIN")/composer2.phar"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ "$PHP_BIN" != /* ]]; then
    echo "Error: PHP_BIN must be an absolute path (got: $PHP_BIN)" >&2
    exit 1
fi

cd "$APP_DIR"

# Fail closed if the .env that just landed here doesn't match the environment
# the caller believes it deployed. Guards against a swapped/typo'd DEPLOY_PATH
# GitHub Environment secret shipping one environment's code+.env over another.
# Runs before anything else touches the filesystem or the app.
if [[ -n "$EXPECTED_ENV" ]]; then
    if [[ ! -r .env ]]; then
        echo "Error: .env not found or unreadable in $APP_DIR — cannot verify" >&2
        echo "environment before deploying." >&2
        exit 1
    fi
    # Parses Doppler's own `doppler secrets download --format env` output
    # (KEY=value, optionally quoted) — not an arbitrary .env dialect (no
    # `export KEY=value`, no spaces around `=`). `|| true` on the pipeline
    # keeps a no-match grep from tripping `set -e -o pipefail` before we can
    # print our own error below.
    ACTUAL_ENV="$(grep -E '^APP_ENV=' .env | tail -n1 | cut -d= -f2- || true)"
    ACTUAL_ENV="${ACTUAL_ENV%\"}"; ACTUAL_ENV="${ACTUAL_ENV#\"}"
    ACTUAL_ENV="${ACTUAL_ENV%\'}"; ACTUAL_ENV="${ACTUAL_ENV#\'}"
    if [[ -z "$ACTUAL_ENV" ]]; then
        echo "Error: .env has no APP_ENV= line — cannot verify environment" >&2
        echo "before deploying." >&2
        exit 1
    fi
    if [[ "$ACTUAL_ENV" != "$EXPECTED_ENV" ]]; then
        echo "Error: .env APP_ENV ('$ACTUAL_ENV') does not match the expected" >&2
        echo "environment ('$EXPECTED_ENV'). Refusing to deploy — check the" >&2
        echo "DEPLOY_PATH secret for the '$EXPECTED_ENV' GitHub Environment." >&2
        exit 1
    fi
fi

mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p bootstrap/cache

# Install dependencies BEFORE entering maintenance mode to keep downtime minimal.
$PHP_BIN "$COMPOSER_BIN" install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader

$PHP_BIN artisan down

# From here on, always bring the site back up on exit — success OR failure — so a
# broken step (e.g. a failed migration) never strands the site in maintenance mode.
# Note: a failed migration may still leave a partially-applied schema to reconcile
# manually, but the site itself will not be left dark.
trap '$PHP_BIN artisan up || true' EXIT

$PHP_BIN artisan storage:link --force
$PHP_BIN artisan migrate --force
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan queue:restart

# The EXIT trap runs `artisan up` here.
