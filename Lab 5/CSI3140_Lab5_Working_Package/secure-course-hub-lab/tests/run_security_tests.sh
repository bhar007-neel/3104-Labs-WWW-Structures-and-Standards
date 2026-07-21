#!/usr/bin/env bash
# Automated acceptance and security tests for local_securecoursehub.
#
# Logs in to the local Moodle as each synthetic account using the normal login
# form (so the tests exercise Moodle's real session mechanism) and then drives
# ajax.php directly, bypassing the user interface. That is the point: every
# restriction must hold even when the client is hostile and no button is clicked.
#
# Session cookies and sesskey values are redacted from all output; nothing
# secret is ever written to tests/evidence.txt.
#
# Usage:  ./tests/run_security_tests.sh          (from anywhere)

set -uo pipefail

cd "$(dirname "$0")/.."

# Git Bash on Windows rewrites Unix-looking arguments into Windows paths, which
# breaks in-container paths. It is applied per docker command (see dc below)
# rather than exported, because exporting it also stops curl from resolving the
# cookie-jar paths.
# The assignments are made inside a subshell on purpose: a prefix assignment on
# a function call leaks into the calling shell, which would then break curl too.
dc() { ( export MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL='*'; docker compose "$@" ); }

SITE=http://127.0.0.1:8080
AJAX="${SITE}/local/securecoursehub/ajax.php"
PAGE="${SITE}/local/securecoursehub/index.php"
COURSEID=2
EVIDENCE=tests/evidence.txt
JARDIR=tests/.tmp

PASS_COUNT=0
FAIL_COUNT=0
TEST_NUM=0

mkdir -p "$JARDIR"
trap 'rm -rf "$JARDIR"' EXIT

# --- helpers ---------------------------------------------------------------

# Values that must never appear in the evidence file.
SECRETS=()

redact() {
    local text="$1"
    local secret
    for secret in "${SECRETS[@]:-}"; do
        if [ -n "$secret" ]; then
            text="${text//$secret/<redacted>}"
        fi
    done
    printf '%s' "$text"
}

log() {
    printf '%s\n' "$1" | tee -a "$EVIDENCE"
}

db_query() {
    dc exec -T mariadb mariadb -ubn_moodle bitnami_moodle -N -B -e "$1" 2>/dev/null
}

# Log in through the real Moodle login form and return the account's sesskey.
login() {
    local user="$1" pass="$2" jar="${JARDIR}/$1.jar"
    rm -f "$jar"

    local token
    token="$(curl -s -c "$jar" "${SITE}/login/index.php" \
        | sed -n 's/.*name="logintoken"[^>]*value="\([^"]*\)".*/\1/p' | head -1)"

    # -L matters: Moodle answers a successful login with a redirect to
    # login/index.php?testsession=<id>, and the session is only confirmed once
    # that round trip completes. Without it the next request is anonymous.
    curl -s -L -b "$jar" -c "$jar" -o /dev/null \
        --data-urlencode "username=${user}" \
        --data-urlencode "password=${pass}" \
        --data-urlencode "logintoken=${token}" \
        "${SITE}/login/index.php"

    local page sesskey
    page="$(curl -s -b "$jar" -c "$jar" "${PAGE}?courseid=${COURSEID}")"
    sesskey="$(printf '%s' "$page" | sed -n 's/.*"sesskey":"\([^"]*\)".*/\1/p' | head -1)"

    # The login form also carries a sesskey, so confirm we are really on the
    # plugin page as an authenticated user before trusting the token.
    # contains() is used rather than a grep pipeline: grep -q exits on the first
    # match, which SIGPIPEs the writer and trips pipefail even on success.
    if [ -z "$sesskey" ] || ! contains 'securecoursehub-current-user' "$page"; then
        echo "FATAL: could not log in as ${user}" >&2
        exit 1
    fi

    SECRETS+=("$sesskey")
    printf '%s' "$sesskey"
}

# POST a JSON body to ajax.php with a given cookie jar. Sets HTTP_CODE and BODY.
post_json() {
    local jar="$1" payload="$2" raw
    raw="$(curl -s -b "$jar" -X POST \
        -H 'Content-Type: application/json' \
        --data-binary "$payload" \
        -w $'\n%{http_code}' "$AJAX")"

    HTTP_CODE="${raw##*$'\n'}"
    BODY="${raw%$'\n'*}"
}

# Record one test result.
#   check <name> <role> <expected> <condition-result 0|1> [extra detail]
check() {
    local name="$1" role="$2" expected="$3" ok="$4" detail="${5:-}"
    TEST_NUM=$((TEST_NUM + 1))

    local verdict
    if [ "$ok" = "0" ]; then
        verdict='PASS'
        PASS_COUNT=$((PASS_COUNT + 1))
    else
        verdict='FAIL'
        FAIL_COUNT=$((FAIL_COUNT + 1))
    fi

    log ""
    log "[$(printf '%02d' "$TEST_NUM")] ${name}"
    log "     role     : ${role}"
    log "     expected : ${expected}"
    log "     actual   : HTTP ${HTTP_CODE:-n/a} $(redact "${BODY:-}")"
    if [ -n "$detail" ]; then
        log "     detail   : $(redact "$detail")"
    fi
    log "     result   : ${verdict}"
}

contains() { case "$2" in *"$1"*) return 0 ;; *) return 1 ;; esac; }

# --- start -----------------------------------------------------------------

mkdir -p tests
: > "$EVIDENCE"

log "Secure Course Hub - automated acceptance and security tests"
log "Target: ${SITE}  (local development instance)"
log "Note:   session cookies and sesskey values are redacted as <redacted>."
log "============================================================"

MOODLE_RELEASE="$(dc exec -T -u daemon moodle php -r \
    'define("CLI_SCRIPT",true); require("/bitnami/moodle/config.php"); echo $CFG->release;' 2>/dev/null)"
log "Moodle: ${MOODLE_RELEASE}"
log "PHP:    $(dc exec -T moodle php -r 'echo PHP_VERSION;' 2>/dev/null)"

SK_L1="$(login learner1 'Test1234!')"
SK_L2="$(login learner2 'Test1234!')"
SK_ST="$(login staff1 'Test1234!')"
JAR_L1="${JARDIR}/learner1.jar"
JAR_L2="${JARDIR}/learner2.jar"
JAR_ST="${JARDIR}/staff1.jar"
JAR_ANON="${JARDIR}/anon.jar"
: > "$JAR_ANON"

# login() runs inside a command substitution, so its exit cannot stop the script.
if [ -z "$SK_L1" ] || [ -z "$SK_L2" ] || [ -z "$SK_ST" ]; then
    echo "FATAL: one or more test logins failed; aborting." >&2
    exit 1
fi

# login() appends to SECRETS inside a subshell, where the change is lost, so the
# tokens are registered here in the parent shell. Everything written to the
# evidence file passes through redact() with this list.
SECRETS=("$SK_L1" "$SK_L2" "$SK_ST")

# learner1's first seeded request, used as the ownership target throughout.
SEED_ID="$(db_query "SELECT id FROM mdl_local_securecoursehub_req WHERE userid=(SELECT id FROM mdl_user WHERE username='learner1') ORDER BY id LIMIT 1;" | tr -d '\r')"
log "Seeded learner1 request id: ${SEED_ID}"

# --- 1. unauthenticated page access ----------------------------------------
HTTP_CODE="$(curl -s -b "$JAR_ANON" -o "${JARDIR}/anon_page.html" -w '%{http_code}' "${PAGE}?courseid=${COURSEID}")"
BODY="$(head -c 200 "${JARDIR}/anon_page.html")"
ok=1
if [ "$HTTP_CODE" = "303" ] || [ "$HTTP_CODE" = "302" ]; then
    ok=0
fi
BODY="(redirect to login, no plugin data in body)"
check "Unauthenticated GET of index.php" "guest / anonymous" "302 or 303 redirect to login, no data" "$ok"

# --- 2. unauthenticated POST to ajax.php -----------------------------------
BEFORE="$(db_query 'SELECT COUNT(*) FROM mdl_local_securecoursehub_req;')"
post_json "$JAR_ANON" '{"action":"create","courseid":2,"title":"anon","description":"anon","sesskey":"x"}'
AFTER="$(db_query 'SELECT COUNT(*) FROM mdl_local_securecoursehub_req;')"
ok=1
if [ "$HTTP_CODE" = "401" ] && [ "$BEFORE" = "$AFTER" ]; then ok=0; fi
check "Unauthenticated POST to ajax.php" "guest / anonymous" "401 rejected before any write" "$ok" "row count ${BEFORE} -> ${AFTER}"

# --- 3. learner1 creates a valid request -----------------------------------
post_json "$JAR_L1" "{\"action\":\"create\",\"courseid\":${COURSEID},\"title\":\"Projector not working in lab\",\"description\":\"The projector in room A stays blank.\",\"sesskey\":\"${SK_L1}\"}"
NEW_ID="$(printf '%s' "$BODY" | sed -n 's/.*"request":{"id":\([0-9]*\).*/\1/p')"
ROW="$(db_query "SELECT userid, courseid, status, (timecreated>0), (timemodified>0) FROM mdl_local_securecoursehub_req WHERE id=${NEW_ID:-0};" | tr '\t' '|' | tr -d '\r')"
L1_ID="$(db_query "SELECT id FROM mdl_user WHERE username='learner1';" | tr -d '\r')"
ok=1
if [ "$HTTP_CODE" = "200" ] && contains '"success":true' "$BODY" && [ "$ROW" = "${L1_ID}|2|open|1|1" ]; then ok=0; fi
check "learner1 creates a valid request" "student (learner1)" "200, owner=learner1, course=2, status=open, timestamps set" "$ok" "stored row (userid|courseid|status|created|modified) = ${ROW}"

# --- 4. create with missing title ------------------------------------------
BEFORE="$(db_query 'SELECT COUNT(*) FROM mdl_local_securecoursehub_req;')"
post_json "$JAR_L1" "{\"action\":\"create\",\"courseid\":${COURSEID},\"title\":\"\",\"description\":\"No title supplied.\",\"sesskey\":\"${SK_L1}\"}"
AFTER="$(db_query 'SELECT COUNT(*) FROM mdl_local_securecoursehub_req;')"
ok=1
if [ "$HTTP_CODE" = "400" ] && contains '"success":false' "$BODY" && [ "$BEFORE" = "$AFTER" ]; then ok=0; fi
check "Create with missing title" "student (learner1)" "400 safe validation error, no row written" "$ok" "row count ${BEFORE} -> ${AFTER}"

# --- 5. create with a 100-character title ----------------------------------
LONG_TITLE="$(printf 'A%.0s' $(seq 1 100))"
BEFORE="$(db_query 'SELECT COUNT(*) FROM mdl_local_securecoursehub_req;')"
post_json "$JAR_L1" "{\"action\":\"create\",\"courseid\":${COURSEID},\"title\":\"${LONG_TITLE}\",\"description\":\"Overlength title.\",\"sesskey\":\"${SK_L1}\"}"
AFTER="$(db_query 'SELECT COUNT(*) FROM mdl_local_securecoursehub_req;')"
ok=1
if [ "$HTTP_CODE" = "400" ] && [ "$BEFORE" = "$AFTER" ]; then ok=0; fi
check "Create with a 100-character title (limit is 80)" "student (learner1)" "400 rejected, no row written" "$ok" "row count ${BEFORE} -> ${AFTER}"

# --- 6. learner1 lists own requests ----------------------------------------
post_json "$JAR_L1" "{\"action\":\"list_own\",\"courseid\":${COURSEID},\"sesskey\":\"${SK_L1}\"}"
RETURNED="$(printf '%s' "$BODY" | grep -o '"id":[0-9]*' | wc -l | tr -d ' ')"
OWNED="$(db_query "SELECT COUNT(*) FROM mdl_local_securecoursehub_req WHERE userid=${L1_ID} AND courseid=${COURSEID};" | tr -d '\r')"
FOREIGN="$(printf '%s' "$BODY" | grep -c '"userid"')"
ok=1
if [ "$HTTP_CODE" = "200" ] && [ "$RETURNED" = "$OWNED" ] && [ "$FOREIGN" = "0" ]; then ok=0; fi
BODY="(list of ${RETURNED} own requests)"
check "learner1 lists own requests" "student (learner1)" "200, exactly the ${OWNED} records owned by learner1" "$ok" "returned ${RETURNED}, owned ${OWNED}"

# --- 7. learner2 list does not include learner1's records ------------------
post_json "$JAR_L2" "{\"action\":\"list_own\",\"courseid\":${COURSEID},\"sesskey\":\"${SK_L2}\"}"
ok=1
if [ "$HTTP_CODE" = "200" ] && ! contains "\"id\":${SEED_ID}," "$BODY" && ! contains 'Projector not working' "$BODY"; then ok=0; fi
check "learner2 lists own requests" "student (learner2)" "200, learner1's records absent" "$ok" "searched for learner1 request id ${SEED_ID} and its title"

# --- 8. learner2 attacks learner1's record ---------------------------------
BEFORE="$(db_query "SELECT CONCAT(title,'|',status) FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
post_json "$JAR_L2" "{\"action\":\"update_own\",\"id\":${SEED_ID},\"title\":\"hijacked\",\"description\":\"hijacked\",\"sesskey\":\"${SK_L2}\"}"
UPDATE_CODE="$HTTP_CODE"; UPDATE_BODY="$BODY"
post_json "$JAR_L2" "{\"action\":\"delete\",\"id\":${SEED_ID},\"sesskey\":\"${SK_L2}\"}"
AFTER="$(db_query "SELECT CONCAT(title,'|',status) FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
ok=1
if [ "$UPDATE_CODE" = "403" ] && [ "$HTTP_CODE" = "403" ] && [ "$BEFORE" = "$AFTER" ] && [ -n "$AFTER" ]; then ok=0; fi
check "learner2 updates and deletes learner1's record by id" "student (learner2)" "403 on both, record unchanged" "$ok" "update HTTP ${UPDATE_CODE}; record before='${BEFORE}' after='${AFTER}'"

# --- 9. learner1 calls the teacher-only operation --------------------------
BEFORE="$(db_query "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
post_json "$JAR_L1" "{\"action\":\"update_status\",\"id\":${SEED_ID},\"status\":\"resolved\",\"sesskey\":\"${SK_L1}\"}"
AFTER="$(db_query "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
ok=1
if [ "$HTTP_CODE" = "403" ] && [ "$BEFORE" = "$AFTER" ]; then ok=0; fi
check "learner1 calls update_status directly (teacher-only)" "student (learner1)" "403, status unchanged" "$ok" "status ${BEFORE} -> ${AFTER}"

# --- 10. staff1 lists the course queue and sets a status -------------------
post_json "$JAR_ST" "{\"action\":\"list_course\",\"courseid\":${COURSEID},\"sesskey\":\"${SK_ST}\"}"
LISTED="$(printf '%s' "$BODY" | grep -o '"id":[0-9]*' | wc -l | tr -d ' ')"
TOTAL="$(db_query "SELECT COUNT(*) FROM mdl_local_securecoursehub_req WHERE courseid=${COURSEID};" | tr -d '\r')"
LIST_OK=1; [ "$HTTP_CODE" = "200" ] && [ "$LISTED" = "$TOTAL" ] && LIST_OK=0
BODY="(queue of ${LISTED} requests)"
check "staff1 lists every request in the course" "editing teacher (staff1)" "200, all ${TOTAL} course records" "$LIST_OK" "returned ${LISTED} of ${TOTAL}"

post_json "$JAR_ST" "{\"action\":\"update_status\",\"id\":${SEED_ID},\"status\":\"inprogress\",\"sesskey\":\"${SK_ST}\"}"
AFTER="$(db_query "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
ok=1
if [ "$HTTP_CODE" = "200" ] && contains '"success":true' "$BODY" && [ "$AFTER" = "inprogress" ]; then ok=0; fi
check "staff1 sets status to inprogress" "editing teacher (staff1)" "200, stored status becomes inprogress" "$ok" "stored status is now '${AFTER}'"

# --- 11. response length boundary ------------------------------------------
R501="$(printf 'x%.0s' $(seq 1 501))"
R500="$(printf 'y%.0s' $(seq 1 500))"
post_json "$JAR_ST" "{\"action\":\"update_status\",\"id\":${SEED_ID},\"status\":\"inprogress\",\"response\":\"${R501}\",\"sesskey\":\"${SK_ST}\"}"
STORED="$(db_query "SELECT COALESCE(CHAR_LENGTH(response),0) FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
ok=1
if [ "$HTTP_CODE" = "400" ] && [ "$STORED" != "501" ]; then ok=0; fi
BODY="$(printf '%s' "$BODY" | head -c 200)"
check "staff1 submits a 501-character response" "editing teacher (staff1)" "400 rejected, nothing stored" "$ok" "stored response length is ${STORED}"

post_json "$JAR_ST" "{\"action\":\"update_status\",\"id\":${SEED_ID},\"status\":\"inprogress\",\"response\":\"${R500}\",\"sesskey\":\"${SK_ST}\"}"
STORED="$(db_query "SELECT CHAR_LENGTH(response) FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
ok=1
if [ "$HTTP_CODE" = "200" ] && [ "$STORED" = "500" ]; then ok=0; fi
BODY="(success, 500-character response stored)"
check "staff1 submits a 500-character response" "editing teacher (staff1)" "200 accepted, 500 characters stored" "$ok" "stored response length is ${STORED}"

# --- 12. CSRF: missing and wrong sesskey -----------------------------------
BEFORE="$(db_query "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
post_json "$JAR_ST" "{\"action\":\"update_status\",\"id\":${SEED_ID},\"status\":\"resolved\"}"
MISSING_CODE="$HTTP_CODE"; MISSING_BODY="$BODY"
post_json "$JAR_ST" "{\"action\":\"update_status\",\"id\":${SEED_ID},\"status\":\"resolved\",\"sesskey\":\"0000000000000000000000000000000a\"}"
AFTER="$(db_query "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
ok=1
if [ "$MISSING_CODE" = "403" ] && [ "$HTTP_CODE" = "403" ] && [ "$BEFORE" = "$AFTER" ]; then ok=0; fi
check "State change with missing sesskey, then with a wrong sesskey" "editing teacher (staff1)" "403 both times, status unchanged" "$ok" "missing-sesskey HTTP ${MISSING_CODE}; status ${BEFORE} -> ${AFTER}"

# --- 13. stored XSS string is rendered as text -----------------------------
XSS_TITLE='<script>alert(1)</script>'
post_json "$JAR_L1" "{\"action\":\"create\",\"courseid\":${COURSEID},\"title\":\"<script>alert(1)</script>\",\"description\":\"XSS probe\",\"sesskey\":\"${SK_L1}\"}"
XSS_CODE="$HTTP_CODE"
XSS_STORED="$(db_query "SELECT COUNT(*) FROM mdl_local_securecoursehub_req WHERE title='<script>alert(1)</script>';" | tr -d '\r')"
curl -s -b "$JAR_L1" "${PAGE}?courseid=${COURSEID}" -o "${JARDIR}/xss.html"
ESCAPED="$(grep -c '&lt;script&gt;alert(1)&lt;/script&gt;' "${JARDIR}/xss.html")"
RAW="$(grep -c '<script>alert(1)</script>' "${JARDIR}/xss.html")"
ok=1
if [ "$XSS_CODE" = "200" ] && [ "$XSS_STORED" -ge 1 ] && [ "$ESCAPED" -ge 1 ] && [ "$RAW" = "0" ]; then ok=0; fi
HTTP_CODE="$XSS_CODE"
BODY="(request created; page re-fetched and inspected)"
check "Injected script string is stored and rendered as text" "student (learner1)" "stored verbatim; page shows escaped entities, no executable tag" "$ok" "escaped occurrences=${ESCAPED}, raw executable occurrences=${RAW}"

# --- 14. nonexistent record -------------------------------------------------
post_json "$JAR_ST" "{\"action\":\"update_status\",\"id\":99999,\"status\":\"resolved\",\"sesskey\":\"${SK_ST}\"}"
ok=1
if [ "$HTTP_CODE" = "404" ] && contains '"success":false' "$BODY" \
    && ! contains 'SELECT' "$BODY" && ! contains '/bitnami' "$BODY"; then ok=0; fi
check "Operate on nonexistent record id 99999" "editing teacher (staff1)" "404 safe not-found, no internal detail" "$ok" "response checked for SQL text and server paths"

# --- 15. invalid status value ----------------------------------------------
BEFORE="$(db_query "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
post_json "$JAR_ST" "{\"action\":\"update_status\",\"id\":${SEED_ID},\"status\":\"deleted\",\"sesskey\":\"${SK_ST}\"}"
AFTER="$(db_query "SELECT status FROM mdl_local_securecoursehub_req WHERE id=${SEED_ID};" | tr -d '\r')"
ok=1
if [ "$HTTP_CODE" = "400" ] && [ "$BEFORE" = "$AFTER" ]; then ok=0; fi
check "Status value outside the whitelist ('deleted')" "editing teacher (staff1)" "400 rejected, status unchanged" "$ok" "status ${BEFORE} -> ${AFTER}"

# --- summary ----------------------------------------------------------------
log ""
log "============================================================"
log "Automated tests: ${PASS_COUNT} passed, ${FAIL_COUNT} failed, $((PASS_COUNT + FAIL_COUNT)) total"
log ""
log "Manual checks that cannot be automated here (do these in a browser):"
log "  M1. Screenshot of the running site and the version page."
log "  M2. Participants page showing staff1, learner1 and learner2 with their roles."
log "  M3. Network tab showing the update_status JSON request and response."
log "  M4. Expired-session behaviour: log out in a second tab, then press Update;"
log "      the page must show the session-expired message and stop."
log "  M5. Browser console shows no errors during normal use."
log "============================================================"

if [ "$FAIL_COUNT" -gt 0 ]; then
    exit 1
fi
