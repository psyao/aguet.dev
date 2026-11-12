#!/usr/bin/env bash
set -euo pipefail   # exit on error, treat unset vars as errors, catch pipe failures

# Copies production's database and all of storage/app (public files and Kami's
# private originals) into storage/backups/<timestamp>/, for scripts/restore.sh
# to load locally. Runs on your machine, never on the server: it reaches
# production through the `tsumigeya` SSH alias (aguet.dev shares its Infomaniak
# account) and reads the database credentials from the server's own .env, so no
# production secret is written here. Taken right before the Switch-over, the
# snapshot is both its backup and the fixture of its rehearsals: keep a copy off
# this machine too.

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

PRODUCTION_PATH="$(grep -E '^PRODUCTION_PATH=' .env | tail -n1 | cut -d= -f2- || true)"
PRODUCTION_PATH="${PRODUCTION_PATH%\"}"; PRODUCTION_PATH="${PRODUCTION_PATH#\"}"
if [[ -z "$PRODUCTION_PATH" ]]; then
    echo "Error: set PRODUCTION_PATH in .env to the app's folder on the server" >&2
    echo "(the DEPLOY_PATH secret)." >&2
    exit 1
fi

# How many snapshots survive a pull, oldest dropped first: enough to hold the
# Switch-over's until the Velox cleanup, a week after it.
KEEP=10

# Oldest first. Timestamped names sort chronologically; a `.partial` left by a
# run that died outside its trap is not one.
snapshots() {
    find storage/backups -mindepth 1 -maxdepth 1 -type d ! -name '*.partial' | sort
}

SNAPSHOT="storage/backups/$(date +%Y-%m-%d-%H%M%S)"
PARTIAL="$SNAPSHOT.partial"
mkdir -p "$PARTIAL"

# A failed run leaves nothing behind that restore.sh could take for the latest snapshot.
trap 'rm -rf "$PARTIAL"' EXIT

# Rows of these tables mean nothing off the server: prod's sessions, cache, and
# pending jobs a local queue:work would otherwise replay. Their structure is kept.
STRUCTURE_ONLY="sessions cache cache_locks jobs job_batches failed_jobs"

echo "Dumping the database…"
ssh tsumigeya bash -s -- "$(printf '%q' "$PRODUCTION_PATH")" "$(printf '%q' "$STRUCTURE_ONLY")" <<'REMOTE' > "$PARTIAL/db.sql.gz"
set -euo pipefail
cd "$1"
read -ra structure_only <<< "$2"

dotenv() {
    local value
    value="$(grep -E "^$1=" .env | tail -n1 | cut -d= -f2- || true)"
    value="${value%\"}"; value="${value#\"}"
    printf '%s' "${value:-${2:-}}"
}

database="$(dotenv DB_DATABASE)"
export MYSQL_PWD="$(dotenv DB_PASSWORD)"
dump="$(command -v mariadb-dump || command -v mysqldump)"

# --no-tablespaces: shared hosting grants no PROCESS privilege, which the
# tablespace dump needs.
options=(--host="$(dotenv DB_HOST 127.0.0.1)" --port="$(dotenv DB_PORT 3306)" --user="$(dotenv DB_USERNAME)"
    --single-transaction --no-tablespaces --skip-lock-tables)

ignored=()
for table in "${structure_only[@]}"; do
    ignored+=(--ignore-table="$database.$table")
done

{
    "$dump" "${options[@]}" "${ignored[@]}" "$database"
    "$dump" "${options[@]}" --no-data "$database" "${structure_only[@]}"
} | gzip
REMOTE

echo "Copying storage/app…"
# Files barely change between pulls, so the ones already in the previous
# snapshot are hardlinked rather than copied again: a second snapshot costs its
# dump and little else. Safe because nothing ever writes into a snapshot.
# ${LINK[@]+…} because macOS's bash 3.2 calls an empty array unbound under `set -u`.
PREVIOUS="$(snapshots | tail -n1)"
LINK=()
if [[ -n "$PREVIOUS" && -d "$PREVIOUS/app" ]]; then
    LINK=(--link-dest="$APP_DIR/$PREVIOUS/app/")
fi

mkdir -p "$PARTIAL/app"
rsync -a ${LINK[@]+"${LINK[@]}"} tsumigeya:"$PRODUCTION_PATH/storage/app/" "$PARTIAL/app/"

mv "$PARTIAL" "$SNAPSHOT"
echo "Snapshot written to $SNAPSHOT"

# A snapshot's dump holds production's real rows, password hashes included, so
# old ones are not kept around for the sake of it. Pruning happens after a
# successful pull: a failed one must never take the snapshot you still have.
TOTAL="$(snapshots | wc -l)"
if (( TOTAL > KEEP )); then
    snapshots | head -n "$((TOTAL - KEEP))" | while read -r old; do
        echo "Dropping $old"
        rm -rf "$old"
    done
fi
