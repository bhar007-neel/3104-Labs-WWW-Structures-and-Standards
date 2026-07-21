#!/usr/bin/env bash
# Idempotent setup for the CSI 3140 Lab 5 local environment.
#
# Waits for Moodle, copies the plugin source into the Moodle tree, installs or
# upgrades it through the Moodle CLI, seeds the synthetic accounts/course/data
# and purges caches. Safe to re-run after every plugin edit.
#
# Usage:  ./setup.sh

set -euo pipefail

cd "$(dirname "$0")"

# Git Bash / MSYS on Windows rewrites arguments that look like Unix paths into
# Windows paths, which breaks in-container paths such as /bitnami/moodle.
# These are ignored on Linux and macOS.
export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL='*'

MOODLE_SERVICE=moodle
MOODLE_DIR=/bitnami/moodle
PLUGIN_DIR="${MOODLE_DIR}/local/securecoursehub"
WEB_USER=daemon
SITE_URL=http://127.0.0.1:8080

log() { printf '\n==> %s\n' "$1"; }

log 'Starting containers'
docker compose up -d

log 'Waiting for Moodle to answer on 127.0.0.1:8080'
for attempt in $(seq 1 120); do
    code="$(curl -s -o /dev/null -w '%{http_code}' "${SITE_URL}/login/index.php" || true)"
    if [ "$code" = "200" ]; then
        echo "    Moodle is up (HTTP ${code}) after ${attempt} attempt(s)"
        break
    fi
    if [ "$attempt" = "120" ]; then
        echo "    Moodle did not become ready in time. Check: docker compose logs moodle" >&2
        exit 1
    fi
    sleep 5
done

log 'Copying the plugin into the Moodle tree'
# The read-only bind mount at /plugin-src is the source of truth; re-copying
# picks up every local edit. Ownership is handed to the web-server user so that
# Moodle can read the files and manage its caches.
docker compose exec -T "$MOODLE_SERVICE" bash -c "
    set -e
    rm -rf '${PLUGIN_DIR}'
    mkdir -p '${PLUGIN_DIR}'
    cp -R /plugin-src/. '${PLUGIN_DIR}/'
    chown -R ${WEB_USER}:${WEB_USER} '${PLUGIN_DIR}'
    find '${PLUGIN_DIR}' -type d -exec chmod 755 {} +
    find '${PLUGIN_DIR}' -type f -exec chmod 644 {} +
"

log 'Installing / upgrading the plugin (Moodle CLI, run as the web-server user)'
docker compose exec -T -u "$WEB_USER" "$MOODLE_SERVICE" \
    php "${MOODLE_DIR}/admin/cli/upgrade.php" --non-interactive --allow-unstable

log 'Seeding synthetic accounts, course, enrolments and sample requests'
docker compose cp setup/seed_testdata.php "${MOODLE_SERVICE}:/tmp/lab5_seed.php"
docker compose exec -T "$MOODLE_SERVICE" chmod 644 /tmp/lab5_seed.php
docker compose exec -T -u "$WEB_USER" "$MOODLE_SERVICE" php /tmp/lab5_seed.php | tee /tmp/lab5_seed_output.txt

log 'Purging caches'
docker compose exec -T -u "$WEB_USER" "$MOODLE_SERVICE" \
    php "${MOODLE_DIR}/admin/cli/purge_caches.php"

log 'Environment ready'
cat <<INFO
  Site:        ${SITE_URL}          (always 127.0.0.1, never localhost)
  Plugin page: ${SITE_URL}/local/securecoursehub/index.php?courseid=2

  Accounts (synthetic, local only):
    admin    / Admin#1234   site administrator
    staff1   / Test1234!    editing teacher in the demo course
    learner1 / Test1234!    student, owns the two seeded requests
    learner2 / Test1234!    student, owns nothing

  Next: ./tests/run_security_tests.sh
INFO
