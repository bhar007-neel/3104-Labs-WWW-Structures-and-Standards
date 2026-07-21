# Idempotent setup for the CSI 3140 Lab 5 local environment (PowerShell).
#
# Windows equivalent of setup.sh. Waits for Moodle, copies the plugin source into
# the Moodle tree, installs or upgrades it through the Moodle CLI, seeds the
# synthetic accounts/course/data and purges caches. Safe to re-run after any edit.
#
# Usage:  powershell -ExecutionPolicy Bypass -File setup.ps1

$ErrorActionPreference = 'Stop'

Set-Location -Path $PSScriptRoot

$MoodleService = 'moodle'
$MoodleDir     = '/bitnami/moodle'
$PluginDir     = "$MoodleDir/local/securecoursehub"
$WebUser       = 'daemon'
$SiteUrl       = 'http://127.0.0.1:8080'

function Write-Step($message) {
    Write-Host ''
    Write-Host "==> $message" -ForegroundColor Cyan
}

Write-Step 'Starting containers'
docker compose up -d
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

Write-Step 'Waiting for Moodle to answer on 127.0.0.1:8080'
$ready = $false
foreach ($attempt in 1..120) {
    try {
        $response = Invoke-WebRequest -Uri "$SiteUrl/login/index.php" -UseBasicParsing -TimeoutSec 10
        if ($response.StatusCode -eq 200) {
            Write-Host "    Moodle is up (HTTP 200) after $attempt attempt(s)"
            $ready = $true
            break
        }
    } catch {
        Start-Sleep -Seconds 5
    }
}
if (-not $ready) { throw 'Moodle did not become ready in time. Check: docker compose logs moodle' }

Write-Step 'Copying the plugin into the Moodle tree'
# The read-only bind mount at /plugin-src is the source of truth; re-copying
# picks up every local edit. Ownership goes to the web-server user so Moodle can
# read the files and manage its caches.
$copyScript = @"
set -e
rm -rf '$PluginDir'
mkdir -p '$PluginDir'
cp -R /plugin-src/. '$PluginDir/'
chown -R ${WebUser}:${WebUser} '$PluginDir'
find '$PluginDir' -type d -exec chmod 755 {} +
find '$PluginDir' -type f -exec chmod 644 {} +
"@
docker compose exec -T $MoodleService bash -c $copyScript
if ($LASTEXITCODE -ne 0) { throw 'Copying the plugin failed' }

Write-Step 'Installing / upgrading the plugin (Moodle CLI, run as the web-server user)'
docker compose exec -T -u $WebUser $MoodleService php "$MoodleDir/admin/cli/upgrade.php" --non-interactive --allow-unstable
if ($LASTEXITCODE -ne 0) { throw 'Plugin upgrade failed' }

Write-Step 'Seeding synthetic accounts, course, enrolments and sample requests'
docker compose cp setup/seed_testdata.php "${MoodleService}:/tmp/lab5_seed.php"
docker compose exec -T $MoodleService chmod 644 /tmp/lab5_seed.php
docker compose exec -T -u $WebUser $MoodleService php /tmp/lab5_seed.php
if ($LASTEXITCODE -ne 0) { throw 'Seeding failed' }

Write-Step 'Purging caches'
docker compose exec -T -u $WebUser $MoodleService php "$MoodleDir/admin/cli/purge_caches.php"

Write-Step 'Environment ready'
Write-Host @"
  Site:        $SiteUrl          (always 127.0.0.1, never localhost)
  Plugin page: $SiteUrl/local/securecoursehub/index.php?courseid=2

  Accounts (synthetic, local only):
    admin    / Admin#1234   site administrator
    staff1   / Test1234!    editing teacher in the demo course
    learner1 / Test1234!    student, owns the two seeded requests
    learner2 / Test1234!    student, owns nothing

  Next: bash ./tests/run_security_tests.sh
"@
