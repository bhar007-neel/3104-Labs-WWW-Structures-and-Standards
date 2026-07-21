#!/usr/bin/env bash
# Build the CSI 3140 Lab 5 submission ZIP.
#
# Payload:
#   local/securecoursehub/   the plugin (README.md inside)
#   report/report.pdf        or report.md with an export note if the PDF is missing
#   screenshots/             images plus SCREENSHOTS_NEEDED.md
#   tests/evidence.txt       automated test results
#
# Before zipping, the payload is scanned for secrets and for Moodle core files.
# Any hit aborts the build.
#
# Usage:  ./package.sh

set -uo pipefail

cd "$(dirname "$0")"

ZIP_NAME=CSI3140_Lab5_Submission.zip
STAGE=build/payload

log() { printf '\n==> %s\n' "$1"; }
fail() { printf '\nABORTED: %s\n' "$1" >&2; exit 1; }

# --- stage -----------------------------------------------------------------

log 'Staging the submission payload'
rm -rf build
mkdir -p "${STAGE}/local/securecoursehub" "${STAGE}/report" "${STAGE}/screenshots" "${STAGE}/tests"

cp -R plugin/. "${STAGE}/local/securecoursehub/"

# Drop editor and VCS noise that must never ship.
find "$STAGE" \( -name '.DS_Store' -o -name '*.swp' -o -name '*~' -o -name '.git*' \) -print -delete

if [ -f report/report.pdf ]; then
    cp report/report.pdf "${STAGE}/report/report.pdf"
    echo "    included report/report.pdf"
else
    cp report/report.md "${STAGE}/report/report.md"
    cat > "${STAGE}/report/EXPORT_TO_PDF.md" <<'NOTE'
report.pdf has not been generated yet.

Export report.md to PDF (for example with Pandoc, VS Code "Markdown PDF", or by
printing the rendered Markdown to PDF), save it as report/report.pdf, then
re-run ./package.sh. The submission requires report.pdf.
NOTE
    echo "    report.pdf not found: included report.md plus an export note"
fi

# Static architecture diagram: the render-safe alternative to the Mermaid block
# in the report, and useful on its own as submitted evidence.
if [ -f report/architecture_diagram.svg ]; then
    cp report/architecture_diagram.svg "${STAGE}/report/architecture_diagram.svg"
fi

cp screenshots/SCREENSHOTS_NEEDED.md "${STAGE}/screenshots/"
shopt -s nullglob
for image in screenshots/*.png screenshots/*.jpg screenshots/*.jpeg; do
    cp "$image" "${STAGE}/screenshots/"
done
shopt -u nullglob

if [ -f tests/evidence.txt ]; then
    cp tests/evidence.txt "${STAGE}/tests/evidence.txt"
else
    fail 'tests/evidence.txt is missing. Run ./tests/run_security_tests.sh first.'
fi

# --- secret scan ------------------------------------------------------------

log 'Scanning the payload for secrets'
FINDINGS=0

report_hit() {
    printf '    [HIT] %s\n' "$1"
    FINDINGS=$((FINDINGS + 1))
}

# Files that must never appear in the payload at all.
while IFS= read -r found; do
    report_hit "forbidden file: ${found}"
done < <(find "$STAGE" \( -name 'config.php' -o -name '.env' -o -name '*.sql' \
    -o -name 'moodledata' -o -name '*.log' \) -print)

# Content patterns. The plugin legitimately mentions the words "sesskey" and
# "password" in code and documentation, so the scan looks for *values*: an
# assigned session id, a real-looking credential, or a captured cookie.
scan() {
    local label="$1" pattern="$2"
    local hits
    hits="$(grep -rInE "$pattern" "$STAGE" 2>/dev/null | grep -v 'SCREENSHOTS_NEEDED.md' || true)"
    if [ -n "$hits" ]; then
        printf '%s\n' "$hits" | while IFS= read -r line; do
            report_hit "${label}: ${line:0:160}"
        done
        FINDINGS=$((FINDINGS + 1))
    fi
}

scan 'MoodleSession cookie value' 'MoodleSession[a-z0-9]*=[A-Za-z0-9]{8,}'
scan 'assigned sesskey value' 'sesskey["'"'"']?\s*[:=]\s*["'"'"'][A-Za-z0-9]{8,}["'"'"']'
scan 'database credential' '(DB_PASSWORD|dbpass|dbuser)\s*[:=]\s*[^ ]'
scan 'Moodle config secret' '\$CFG->(dbpass|passwordsaltmain)'
scan 'private key block' 'BEGIN (RSA |OPENSSH |EC )?PRIVATE KEY'

# The documented synthetic test passwords are allowed in README.md only.
STRAY_PW="$(grep -rIl 'Test1234!\|Admin#1234' "$STAGE" 2>/dev/null \
    | grep -v 'local/securecoursehub/README.md' \
    | grep -v 'report/report' || true)"
if [ -n "$STRAY_PW" ]; then
    printf '%s\n' "$STRAY_PW" | while IFS= read -r file; do
        report_hit "test credential outside README/report: ${file}"
    done
    FINDINGS=$((FINDINGS + 1))
fi

if [ "$FINDINGS" -gt 0 ]; then
    fail "${FINDINGS} secret-scan finding(s) above. Nothing was packaged."
fi
echo '    no secrets found'

# --- core-file assertion ----------------------------------------------------

log 'Asserting that no Moodle core file is included'
CORE_HITS="$(find "$STAGE" \( -path '*/lib/moodlelib.php' -o -name 'version.php' -path '*/build/*' \
    -o -name 'moodle*.php' -not -path '*/local/securecoursehub/*' \) -print)"
TOP_LEVEL="$(find "${STAGE}" -mindepth 1 -maxdepth 1 -printf '%f\n' | sort | tr '\n' ' ')"
EXPECTED='local report screenshots tests '
if [ -n "$CORE_HITS" ]; then
    fail "Moodle core files detected in the payload:\n${CORE_HITS}"
fi
if [ "$TOP_LEVEL" != "$EXPECTED" ]; then
    fail "Unexpected payload contents. Found: '${TOP_LEVEL}' expected: '${EXPECTED}'"
fi
echo "    payload contains only: ${TOP_LEVEL}"

# --- zip --------------------------------------------------------------------

log 'Building the ZIP'
rm -f "$ZIP_NAME"

# Use whichever archiver this machine has: zip on Linux/macOS, Python's zipfile
# (present with Git Bash) or PowerShell's Compress-Archive on Windows.
# Windows ships a python3 "app execution alias" that is not a real interpreter,
# so candidates are probed rather than trusted.
find_python() {
    local candidate
    for candidate in python3 python py; do
        if command -v "$candidate" >/dev/null 2>&1 \
            && "$candidate" -c 'pass' >/dev/null 2>&1; then
            printf '%s' "$candidate"
            return 0
        fi
    done
    return 1
}
PY="$(find_python || true)"

if command -v zip >/dev/null 2>&1; then
    ( cd "$STAGE" && zip -q -r "../../${ZIP_NAME}" . )
    echo '    archiver: zip'
elif [ -n "$PY" ]; then
    "$PY" - "$STAGE" "$ZIP_NAME" <<'PYZIP'
import os, sys, zipfile
stage, target = sys.argv[1], sys.argv[2]
with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as archive:
    for root, _dirs, files in os.walk(stage):
        for name in sorted(files):
            path = os.path.join(root, name)
            archive.write(path, os.path.relpath(path, stage).replace(os.sep, '/'))
PYZIP
    echo '    archiver: python zipfile'
elif command -v powershell >/dev/null 2>&1; then
    powershell -NoProfile -Command \
        "Compress-Archive -Path '${STAGE}/*' -DestinationPath '${ZIP_NAME}' -Force"
    echo '    archiver: PowerShell Compress-Archive'
else
    fail 'No archiver found. Install zip, or make python or powershell available.'
fi

[ -f "$ZIP_NAME" ] || fail 'ZIP was not created.'

log 'Done'
echo "    ${ZIP_NAME} ($(du -h "$ZIP_NAME" | cut -f1))"
echo '    contents:'
if command -v unzip >/dev/null 2>&1; then
    unzip -l "$ZIP_NAME" | tail -n +4 | head -40
else
    "$PY" -c "import zipfile,sys; [print('   ', n) for n in zipfile.ZipFile(sys.argv[1]).namelist()]" "$ZIP_NAME"
fi
