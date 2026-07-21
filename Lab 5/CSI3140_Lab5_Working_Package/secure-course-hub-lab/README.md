# CSI 3140 Lab 5 — Secure Course Hub (team working repo)

Everything needed to reproduce the development environment on a teammate's
machine. This file is for **us**; it is not part of the submission ZIP (that
contains only `plugin/`, the report, the screenshots and the test evidence).

- Plugin documentation for the grader → [`plugin/README.md`](plugin/README.md)
- Step-by-step audit procedure → [`AUDIT.md`](AUDIT.md)
- The report → [`report/report.md`](report/report.md)

---

## 1. Prerequisites

| Requirement | Notes |
|---|---|
| Docker Desktop | Running, with ~4 GB free for images and volumes |
| Ports 8080 and 8443 free | Nothing else may be bound to them |
| A bash shell | Git Bash on Windows, or any shell on macOS/Linux. PowerShell users can run `setup.ps1` instead, but the test script needs bash |
| Python *or* `zip` | Only for `package.sh`; Git Bash ships Python |

No PHP, Apache or MariaDB installation is needed on the host — everything runs
in containers.

---

## 2. First-time setup

```bash
git clone <this repo>
cd "Lab 5/CSI3140_Lab5_Working_Package/secure-course-hub-lab"

docker compose up -d      # first boot takes 3-8 minutes while Moodle installs
./setup.sh                # Windows alternative: powershell -ExecutionPolicy Bypass -File setup.ps1
```

`docker compose up -d` returns almost immediately, but Moodle keeps installing in
the background. `setup.sh` waits for it, so just run them back to back. Watch
progress with `docker compose logs -f moodle` if you are impatient.

**Expected tail of `setup.sh`:**

```
MOODLE_RELEASE=4.5.4 (Build: 20250414)
PHP_VERSION=8.1.32
DEMO_COURSE_ID=2
PLUGIN_URL=http://127.0.0.1:8080/local/securecoursehub/index.php?courseid=2
```

Then open <http://127.0.0.1:8080/local/securecoursehub/index.php?courseid=2>.

### Accounts

Synthetic, local-container-only. Safe to keep in the repo — they exist nowhere
else and are needed to reproduce the environment.

| Account | Password | Role |
|---|---|---|
| `admin` | `Admin#1234` | Site administrator |
| `staff1` | `Test1234!` | Editing teacher |
| `learner1` | `Test1234!` | Student — owns the seeded requests |
| `learner2` | `Test1234!` | Student — owns nothing (the "attacker" in tests) |

---

## 3. Daily workflow

`setup.sh` is **idempotent**: it re-copies the plugin, re-runs the Moodle
upgrade, re-seeds anything missing and purges caches. Run it after every change.

```bash
# edit anything under plugin/ ...
./setup.sh                      # push the changes into the running Moodle
./tests/run_security_tests.sh   # expect: 17 passed, 0 failed
```

### Editing the JavaScript

Moodle serves `amd/build/dashboard.min.js` in production mode, **not** the source
file. After editing the source you must sync the build copy or your change will
appear to do nothing:

```bash
cp plugin/amd/src/dashboard.js plugin/amd/build/dashboard.min.js
./setup.sh
diff plugin/amd/src/dashboard.js plugin/amd/build/dashboard.min.js   # must be identical
```

### Changing the database schema

Moodle runs `db/install.xml` **once**, at install time. Editing it on its own
does nothing. Bump `$plugin->version` in `plugin/version.php`, then re-run
`./setup.sh`. If the table is already wrong, uninstall first:

```bash
docker compose exec -T -u daemon moodle \
  php /bitnami/moodle/admin/cli/uninstall_plugins.php --plugins=local_securecoursehub --run
./setup.sh
```

---

## 4. Testing

```bash
./tests/run_security_tests.sh
```

17 automated checks. Results are written to `tests/evidence.txt`, which is
committed as graded evidence. The harness logs in through Moodle's real login
form, then calls `ajax.php` directly with no interface involved. Sesskeys and
cookies are redacted from the output.

Full cold-start verification — destroys all data, proves the whole thing
reproduces from nothing:

```bash
docker compose down -v && ./setup.sh && ./tests/run_security_tests.sh
```

---

## 5. Packaging the submission

```bash
./package.sh      # builds CSI3140_Lab5_Submission.zip
```

Refuses to build if it finds secrets or Moodle core files in the payload.

> **The scanner reads text only.** It cannot inspect PNGs, so *you* must confirm
> no screenshot shows a cookie, a sesskey value or a password. Check every image
> in `screenshots/` by eye before packaging.

Export `report/report.md` to `report/report.pdf` first, or the ZIP falls back to
shipping the Markdown with an export note.

---

## 6. Repository layout

```
secure-course-hub-lab/
├── docker-compose.yml      Moodle 4.5 + MariaDB 11.4, plugin mounted read-only at /plugin-src
├── setup.sh / setup.ps1    idempotent environment setup
├── setup/seed_testdata.php CLI script run inside the container to seed accounts and data
├── plugin/                 THE DELIVERABLE - everything here ships in the ZIP
├── tests/                  test harness + committed evidence
├── report/                 report source
├── screenshots/            evidence images + the list of required shots
├── AUDIT.md                ordered verification procedure
└── package.sh              builds the submission ZIP
```

Not committed (see `.gitignore`), none of it secret — all regenerated on demand:
`build/` (packaging staging area — never edit files there), `tests/.tmp/`
(cookie jars), and the ZIP itself.

---

## 7. Troubleshooting

| Symptom | Cause and fix |
|---|---|
| Every page bounces back to login | You used `localhost`. Moodle compares the host to `$CFG->wwwroot` — always use **`127.0.0.1:8080`** |
| `Could not open input file: C:/Program Files/Git/bitnami/...` | Git Bash rewrote a container path. Prefix the command with `MSYS_NO_PATHCONV=1` |
| JS changes have no effect | `amd/build/dashboard.min.js` is stale — see §3 |
| Schema change ignored | `$plugin->version` not bumped — see §3 |
| Port 8080 already in use | Stop the other service, or change the mapping in `docker-compose.yml` (and `MOODLE_HOST` to match) |
| Images fail to pull | Bitnami moved community images to the `bitnamilegacy` namespace in 2025; the compose file already uses those paths |
| Test suite reports mass failures | Usually the harness, not the plugin. Confirm the site answers: `curl -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8080/login/index.php` |
| Containers healthy but Moodle 500s | `docker compose logs moodle \| tail -50`. Debug display is off by design, so errors go to the log, not the browser |

---

## 8. Before submitting

1. Every screenshot in `screenshots/` checked by eye for cookies/sesskeys/passwords.
2. `./tests/run_security_tests.sh` → 17/17 on a **cold-started** instance.
3. `TODO(HUMAN)` items in `report/report.md` filled in: names, student numbers,
   screenshot captions, manual checks M1–M5, reflection.
4. `report/report.pdf` exported, then `./package.sh` re-run.
5. ZIP verified to install on a clean Moodle — procedure in [`AUDIT.md`](AUDIT.md) Phase 4.
