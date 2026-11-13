#!/usr/bin/env bash
set -euo pipefail   # exit on error, treat unset vars as errors, catch pipe failures

# Replays aguet.dev's public URL inventory (#28 decision 6) against a base URL,
# e.g. `scripts/replay-urls.sh http://aguet-rehearsal.test` on a rehearsal of the
# Switch-over, or `scripts/replay-urls.sh https://aguet.dev` right after its `up`.
# Each line: path, then the expected "status Location" as one extended glob, the
# Location relative to the base (`-` for none). Redirects are not followed. Every
# URL is replayed; exits 1 if any differs.
#
# Velox's social image answers 200 while its file is still served from
# public/storage, then the 301 to Kami's Conversion once the Velox cleanup
# (#28 decision 7) deletes it. Herd's nginx answers robots.txt with a 404 and
# ignores public/.htaccess, which redirects `/en/` to `/en`: replay against Apache.

shopt -s extglob

BASE="${1:-}"
if [[ -z "$BASE" ]]; then
    echo "Error: pass the base URL, e.g. https://aguet.dev" >&2
    exit 1
fi
BASE="${BASE%/}"

INVENTORY='
/                                                               200 -
/en                                                             200 -
/en/                                                            301 /en
/EN                                                             301 /en
/sitemap.xml                                                    200 -
/robots.txt                                                     200 -
/up                                                             200 -
/une-page-absente                                               404 -
/storage/media/01m5pgcpaafgk30py2pce8c412/aguet-dev-1200.webp   @(200 -|301 /media/*)
'

FAILED=0
while read -r path expected; do
    [[ -z "$path" ]] && continue

    read -r status location < <(curl -sS --max-time 15 -o /dev/null --max-redirs 0 -w '%{http_code} %{redirect_url}\n' "$BASE$path")
    location="${location#"$BASE"}"
    got="$status ${location:--}"

    # Unquoted on the right: a glob.
    # shellcheck disable=SC2053
    if [[ "$got" == $expected ]]; then
        echo "ok    $got $path"
    else
        echo "FAIL  $path: expected $expected, got $got"
        FAILED=1
    fi
done <<< "$INVENTORY"

exit "$FAILED"
