#!/usr/bin/env bash
# Live CSRF (sesskey) demonstration for the laboratory demo and for screenshot 08.
#
# Proves that a fully authenticated user, with a valid session cookie, still
# cannot change data unless the request also carries the correct sesskey.
# That is the whole point of CSRF protection: the cookie is attached by the
# browser automatically and therefore proves nothing about intent, while the
# sesskey can only be known by a page served from this site.
#
# The real sesskey is NEVER printed or sent. Both attempts below use either no
# token at all or an obviously fake one, so this output is safe to screenshot.
#
# Usage:  ./tests/demo_csrf.sh

set -uo pipefail

cd "$(dirname "$0")/.."

dc() { ( export MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL='*'; docker compose "$@" ); }

SITE=http://127.0.0.1:8080
AJAX="${SITE}/local/securecoursehub/ajax.php"
JARDIR=tests/.demo
JAR="${JARDIR}/staff1.jar"

mkdir -p "$JARDIR"
trap 'rm -rf "$JARDIR"' EXIT

db() { dc exec -T mariadb mariadb -ubn_moodle bitnami_moodle -N -B -e "$1" 2>/dev/null | tr -d '\r'; }

echo '=============================================================='
echo ' CSRF / sesskey demonstration - local_securecoursehub'
echo '=============================================================='
echo
echo 'Logging in as staff1 (editing teacher) through the real login form...'

TOKEN="$(curl -s -c "$JAR" "${SITE}/login/index.php" \
    | sed -n 's/.*name="logintoken"[^>]*value="\([^"]*\)".*/\1/p' | head -1)"
curl -s -L -b "$JAR" -c "$JAR" -o /dev/null \
    --data-urlencode 'username=staff1' \
    --data-urlencode 'password=Test1234!' \
    --data-urlencode "logintoken=${TOKEN}" \
    "${SITE}/login/index.php"

# Captured to a variable rather than piped into grep -q: grep exits at the first
# match, which SIGPIPEs curl and trips pipefail even though the match succeeded.
PAGE="$(curl -s -b "$JAR" "${SITE}/local/securecoursehub/index.php?courseid=2")"
case "$PAGE" in
    *securecoursehub-current-user*) ;;
    *)
        echo 'FATAL: could not log in as staff1. Is the environment running? Try ./setup.sh' >&2
        exit 1
        ;;
esac

echo '  logged in: session cookie is held in the cookie jar.'
echo '  (the real sesskey is deliberately NOT read or shown)'
echo

ID="$(db 'SELECT id FROM mdl_local_securecoursehub_req ORDER BY id LIMIT 1;')"
BEFORE="$(db "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${ID};")"

echo "Target record : id=${ID}"
echo "Stored status : ${BEFORE}"
echo
echo 'Both attempts below try to force this record to "resolved".'
echo '--------------------------------------------------------------'

attempt() {
    local label="$1" payload="$2" raw code body
    echo
    echo "ATTEMPT: ${label}"
    echo "  request : ${payload}"

    raw="$(curl -s -b "$JAR" -X POST -H 'Content-Type: application/json' \
        --data-binary "$payload" -w $'\n%{http_code}' "$AJAX")"
    code="${raw##*$'\n'}"
    body="${raw%$'\n'*}"

    echo "  response: HTTP ${code}"
    echo "            ${body}"

    if [ "$code" = "403" ]; then
        echo '  verdict : REJECTED as expected'
    else
        echo "  verdict : UNEXPECTED - expected HTTP 403, got ${code}"
    fi
}

# 1. A valid session, but the sesskey field is absent entirely.
attempt 'authenticated, sesskey field omitted' \
    "{\"action\":\"update_status\",\"id\":${ID},\"status\":\"resolved\"}"

# 2. A valid session with a forged token - what a cross-site attacker could do,
#    since they can make the browser send the cookie but cannot read our page.
attempt 'authenticated, forged sesskey "forged0000"' \
    "{\"action\":\"update_status\",\"id\":${ID},\"status\":\"resolved\",\"sesskey\":\"forged0000\"}"

AFTER="$(db "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${ID};")"

echo
echo '--------------------------------------------------------------'
echo "Stored status before : ${BEFORE}"
echo "Stored status after  : ${AFTER}"

if [ "$BEFORE" = "$AFTER" ]; then
    echo 'RESULT: PASS - both requests rejected, database unchanged.'
else
    echo 'RESULT: FAIL - the record changed. Investigate immediately.'
    exit 1
fi

cat <<'EXPLAIN'

Why this matters
----------------
The session cookie was present and valid in both attempts: the server knew
exactly who the user was. Authentication alone was still not enough to change
data.

A cross-site attacker can cause a victim's browser to issue a request, and the
browser will attach the MoodleSession cookie automatically. What the attacker
cannot do is read our page, so they cannot learn the sesskey. Requiring the
sesskey in the request body is what separates "a request carrying your cookie"
from "a request you actually intended".

Enforced at: plugin/ajax.php (confirm_sesskey, every action)
             plugin/index.php (require_sesskey, every form POST)
EXPLAIN
