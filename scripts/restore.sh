#!/usr/bin/env bash
set -euo pipefail   # exit on error, treat unset vars as errors, catch pipe failures

# Replaces the local database and storage/app with a snapshot taken by
# scripts/backup.sh — the latest one, or the folder given as the first argument.
# Every user signs in afterwards with their email and `password`, with no second
# factor: so a restore cannot prove that the MFA survives the Switch-over.

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

SNAPSHOT="${1:-$(find storage/backups -mindepth 1 -maxdepth 1 -type d ! -name '*.partial' 2>/dev/null | sort | tail -n1)}"
SNAPSHOT="${SNAPSHOT%/}"
if [[ ! -f "$SNAPSHOT/db.sql.gz" ]]; then
    echo "Error: no snapshot to restore — run scripts/backup.sh first, or pass" >&2
    echo "a folder of storage/backups/." >&2
    exit 1
fi

# This wipes the database it is pointed at: never anything but a local one.
if [[ "$(php artisan env)" != *"[local]"* ]]; then
    echo "Error: APP_ENV is not local. Refusing to restore over this database." >&2
    exit 1
fi

dotenv() {
    local value
    value="$(grep -E "^$1=" .env | tail -n1 | cut -d= -f2- || true)"
    value="${value%\"}"; value="${value#\"}"
    printf '%s' "${value:-${2:-}}"
}

echo "Restoring ${SNAPSHOT}…"
php artisan db:wipe --force
# A newer mariadb-dump opens with a sandbox-mode line older clients reject.
gunzip -c "$SNAPSHOT/db.sql.gz" | sed '/^\/\*M!999999/d' | MYSQL_PWD="$(dotenv DB_PASSWORD)" mariadb \
    --host="$(dotenv DB_HOST 127.0.0.1)" --port="$(dotenv DB_PORT 3306)" --user="$(dotenv DB_USERNAME)" "$(dotenv DB_DATABASE)"

# The branch may be ahead of production.
php artisan migrate --force

# Velox kept the second factor on `users`, Kami keeps it in `kami_user_mfa`.
php artisan tinker --execute '
    DB::table("users")->update([
        "password" => Hash::make("password"),
        "remember_token" => null,
        "app_authentication_secret" => null,
        "app_authentication_recovery_codes" => null,
    ]);
    if (Schema::hasTable("kami_user_mfa")) {
        DB::table("kami_user_mfa")->delete();
    }
'

# The repository's .gitignore files are excluded, so --delete keeps them.
rsync -a --delete --exclude='.gitignore' "$SNAPSHOT/app/" storage/app/

echo "Restored. Sign in with any user's email and \`password\`."
