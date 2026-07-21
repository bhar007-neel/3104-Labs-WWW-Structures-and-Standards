# Audit guide — CSI 3140 Lab 5, Secure Course Hub

Work through this in order. Every step is either a command with an expected
result, or a file and line to read. Nothing here asks you to take the
implementation on trust — each claim has a check attached.

Budget roughly 60–90 minutes for a full pass, plus the browser walkthrough.

---

## Phase 0 — Bring the environment up (5 min + first-boot wait)

```bash
cd "Lab 5/CSI3140_Lab5_Working_Package/secure-course-hub-lab"
docker compose up -d      # first boot takes 3-8 min while Moodle installs
./setup.sh                # idempotent: safe to run repeatedly
```

**Expected tail of `setup.sh`:**

```
MOODLE_RELEASE=4.5.4 (Build: 20250414)
MOODLE_VERSION=2024100704
PHP_VERSION=8.1.32
DEMO_COURSE_ID=2
```

If `docker compose up` fails to pull the images, note that Bitnami moved their
community images to the `bitnamilegacy` namespace in 2025 — that is why
`docker-compose.yml` uses `bitnamilegacy/moodle:4.5`, not `bitnami/moodle:4.5`.

> **Always use `127.0.0.1`, never `localhost`.** Moodle compares the request host
> against `$CFG->wwwroot`; a mismatch silently redirects everything to the login
> page and looks like a broken plugin.

---

## Phase 1 — Audit against the 12 required tasks

### Task 1 — Moodle installed locally, versions recorded

```bash
docker compose exec -T -u daemon moodle php -r \
  'define("CLI_SCRIPT",true); require("/bitnami/moodle/config.php"); echo $CFG->release, "\n";'
docker compose exec -T moodle php -r 'echo PHP_VERSION, "\n";'
```

Confirm both values match what `plugin/README.md` §1 and `report/report.md` §2
claim. **If you rebuild on a different machine and get different versions, both
documents must be updated** — this is graded, and a stale version string is an
easy mark to lose.

### Task 2 — Four accounts, one course, correct enrolments

Browser check (also screenshot #2): log in as `admin` / `Admin#1234`, go to the
course **CSI3140-DEMO** → **Participants**. You must see `staff1` as *Teacher*
and `learner1` / `learner2` as *Students*.

Command-line equivalent:

```bash
docker compose exec -T mariadb mariadb -ubn_moodle bitnami_moodle -N -B -e "
SELECT u.username, r.shortname FROM mdl_role_assignments ra
JOIN mdl_user u ON u.id=ra.userid JOIN mdl_role r ON r.id=ra.roleid
JOIN mdl_context c ON c.id=ra.contextid AND c.contextlevel=50;"
```

Expect `staff1 editingteacher`, `learner1 student`, `learner2 student`.

Confirm the data is synthetic: names are *Sam Teacher*, *Alex StudentA*,
*Blair StudentB*, emails all `@example.invalid`. See `setup/seed_testdata.php`.

### Task 3 — Plugin skeleton installs without touching core

```bash
docker compose exec -T mariadb mariadb -ubn_moodle bitnami_moodle -N -B -e \
  "SELECT plugin,name,value FROM mdl_config_plugins WHERE plugin='local_securecoursehub';"
```

Expect `local_securecoursehub version 2026072101`.

**Proof that no core file was modified:** everything the plugin needs lives under
`plugin/`. Verify nothing else was written into the Moodle tree:

```bash
docker compose exec -T moodle bash -c \
  "cd /bitnami/moodle && git status --short 2>/dev/null || ls -la local/"
```

`local/` should contain only `securecoursehub/` plus Moodle's own `readme.txt`.

Read `plugin/version.php` — component, version, `requires` (2024100700 = Moodle
4.5), `MATURITY_ALPHA`, release `0.1.0`.

### Task 4 — Database table

Read `plugin/db/install.xml`. Check field by field against the spec:

```bash
docker compose exec -T mariadb mariadb -ubn_moodle bitnami_moodle -e \
  "DESCRIBE mdl_local_securecoursehub_req;"
```

Required: `id, courseid, userid, title, description, status, response,
timecreated, timemodified`. Confirm `title` is **varchar(80)** and `status`
defaults to `open`. Indexes exist on `courseid` and `userid`.

> Watch for this trap: Moodle runs `install.xml` **once**. If you change the
> schema you must bump `$plugin->version` in `version.php`, or uninstall the
> plugin first. This bit us during development — an early build silently created
> a table under the wrong name and the upgrade still reported success.

### Task 5 — Capabilities

Read `plugin/db/access.php` (39 lines). Confirm three capabilities, all at
`CONTEXT_COURSE`:

| Capability | Archetypes |
|---|---|
| `viewown` | student, editingteacher, manager |
| `createrequest` | student, editingteacher, manager |
| `managecourserequests` | editingteacher, manager (+ `RISK_PERSONAL`) |

Confirm matching language strings exist — `plugin/lang/en/local_securecoursehub.php`,
the `securecoursehub:*` keys near the bottom. A missing string shows up in the UI
as a raw `[[capabilityname]]` marker.

### Task 6 — Every entry point protected

There are exactly two entry points. Read both openings:

| File | Line | What to confirm |
|---|---|---|
| `plugin/index.php` | 17 | `require_login($course)` — before any data access |
| `plugin/index.php` | 20 | `require_capability('...:viewown', $context)` |
| `plugin/ajax.php` | 80 | POST-only; GET can never change state |
| `plugin/ajax.php` | 88 | `require_login(...)` with `$preventredirect = true` so a `fetch()` gets JSON, not an HTML login page |
| `plugin/ajax.php` | 136 | `require_capability()` for course-scoped actions |

### Task 7 — Student operations and ownership

Read `plugin/classes/local/request_service.php`:

- line 151–152 — `require_owner()` **and** `require_open()` together
- line 313 — the ownership comparison `(int) $record->userid !== $userid`
- line 322 — the state rule: students touch only `open` records
- line 103 — `list_own()` filters by `userid` **in the query**, so another
  student's row is never loaded into memory at all

### Task 8 — Teacher operations

`plugin/ajax.php` line 197 (`update_status`) requires
`managecourserequests`. Response length is capped at 500 in
`request_service.php` (`MAX_RESPONSE_LENGTH`, checked with `core_text::strlen`
so multibyte input counts correctly).

### Task 9 — Moodle Database API

```bash
grep -n '\$DB->' plugin/classes/local/request_service.php
```

Every hit must be a Moodle DML method with an array of conditions or a record
object. Now prove no SQL is built from user input:

```bash
grep -rn "SELECT\|INSERT\|UPDATE\|DELETE" plugin/ --include=*.php
```

The only raw SQL is in `plugin/classes/privacy/provider.php`, and it uses named
placeholders plus `get_in_or_equal()` — no concatenation. Confirm by reading it.

### Task 10 — JSON + fetch()

Read `plugin/amd/src/dashboard.js`:

- `postJson()` — `credentials: 'same-origin'`, sesskey from `M.cfg.sesskey`
  (never hard-coded, never invented client-side)
- `initTeacherUpdates()` — the required state-changing operation
- every DOM write uses `textContent` / `createElement`

```bash
grep -n "innerHTML" plugin/amd/src/dashboard.js   # expect: no output
```

> `amd/build/dashboard.min.js` must stay byte-identical to `amd/src/dashboard.js`.
> Moodle serves the **build** copy in production mode. After editing the source:
> `cp plugin/amd/src/dashboard.js plugin/amd/build/dashboard.min.js && ./setup.sh`
> Verify: `diff plugin/amd/src/dashboard.js plugin/amd/build/dashboard.min.js`

### Task 11 — Security controls

```bash
grep -n "confirm_sesskey\|require_sesskey" plugin/ajax.php plugin/index.php
```

Expect `ajax.php:104` and `index.php:60`. Then read `ajax.php:52` — the
`LOCAL_SECURECOURSEHUB_SAFE_ERRORS` map. **This is the core of safe error handling:**
a message is returned to the client only if its error code is in that map (all
are this plugin's own language strings). Anything else becomes a generic
"The operation could not be completed." and is logged locally.

Check output escaping:

```bash
grep -c "s(\$record->\|s((string)" plugin/index.php   # escaped output sites
```

### Task 12 — Test, document, package

Covered in Phases 2–4 below.

---

## Phase 2 — Run the automated tests

```bash
./tests/run_security_tests.sh
```

**Expected final line: `Automated tests: 17 passed, 0 failed, 17 total`.**
Full output lands in `tests/evidence.txt`.

What the harness actually does, and why it is credible evidence: it logs in
through Moodle's **real login form** (so it exercises genuine sessions), then
calls `ajax.php` **directly** — no buttons, no UI. Every rule must hold against a
client that ignores the interface entirely.

Mapping to the lab's Part G mandatory tests:

| Part G requirement | Test # |
|---|---|
| Unauthenticated user opens plugin page | 01 |
| Student creates a valid request | 03 |
| Student submits missing/invalid fields | 04, 05 |
| Student views own records | 06 |
| Student changes another student's record id | 08 |
| Student calls teacher-only operation directly | 09 |
| Teacher views authorized course records | 10 |
| Teacher denied outside authorized course | 08, 09 (context recomputation) |
| Missing/invalid sesskey on state change | 14 |
| Injected HTML/JavaScript text | 15 |
| Missing record | 16 |
| Invalid status / overlength input | 05, 12, 17 |
| Expired session during AJAX | **manual — M4 below** |
| No unexplained console/PHP/DB errors | **manual — M5 below** |

Spot-check that the tests are honest rather than self-confirming: open
`tests/evidence.txt` and confirm each entry records the **actual HTTP code and
response body**, and that the "detail" line shows a real database read
(row counts, stored status, stored response length) proving the database did or
did not change.

Confirm no secrets leaked into the evidence:

```bash
grep -n "MoodleSession" tests/evidence.txt        # expect: no output
grep -c "redacted" tests/evidence.txt             # expect: >= 1
```

---

## Phase 3 — Manual browser walkthrough (the part no script can do)

Open <http://127.0.0.1:8080/local/securecoursehub/index.php?courseid=2>.
Take the screenshots listed in `screenshots/SCREENSHOTS_NEEDED.md` as you go.

1. **As `learner1` / `Test1234!`** — create a request. The new row must appear
   **without a page reload**. Edit it, then delete it. → screenshot 3
2. **Still as `learner1`** — create one titled `<script>alert(1)</script>`.
   No alert may fire; the title must display as literal text. → screenshot 7
3. **As `learner2` / `Test1234!`** — the page shows none of learner1's requests.
   → screenshot 4
4. **As `staff1` / `Test1234!`** — the course queue appears with the status
   filter. Open DevTools → Network, change a status, press **Update**. Inspect
   the `ajax.php` request: JSON payload out, JSON response back, row updates in
   place. → screenshots 5 and 6
5. **M4 — expired session.** With the teacher page open, log out in a second tab,
   then press **Update** in the first. Expect the message *"Your session has
   expired. Sign in again and retry."* and no further action. → screenshot 9
6. **M5 — console.** Confirm the browser console shows no errors during all of
   the above. → screenshot 10
7. **Screenshot 8 — sesskey rejection.** The safe way to demonstrate CSRF without
   exposing a token: show the test-suite output for test 14 in
   `tests/evidence.txt` (HTTP 403, status unchanged) rather than tampering with a
   live token in the browser.

> **In every screenshot: no cookie, no sesskey value, no password.** In the
> Network tab keep Cookie/Set-Cookie headers collapsed and blur the `sesskey`
> field if it is visible in a payload.

---

## Phase 4 — Package and verify the submission

```bash
./package.sh
```

The script refuses to build if it finds secrets or Moodle core files. Expect:

```
no secrets found
payload contains only: local report screenshots tests
CSI3140_Lab5_Submission.zip
```

Then prove the ZIP installs on a clean Moodle — this is a graded acceptance
criterion, and it is the single most valuable check in this document:

```bash
# 1. remove the plugin completely (drops its table)
docker compose exec -T -u daemon moodle \
  php /bitnami/moodle/admin/cli/uninstall_plugins.php --plugins=local_securecoursehub --run
docker compose exec -T moodle rm -rf /bitnami/moodle/local/securecoursehub

# 2. install from the ZIP payload only
rm -rf build/zipcheck && mkdir -p build/zipcheck
python -c "import zipfile; zipfile.ZipFile('CSI3140_Lab5_Submission.zip').extractall('build/zipcheck')"
docker compose cp build/zipcheck/local/securecoursehub moodle:/tmp/zipplugin
docker compose exec -T moodle bash -c \
  "mv /tmp/zipplugin /bitnami/moodle/local/securecoursehub && chown -R daemon:daemon /bitnami/moodle/local/securecoursehub"
docker compose exec -T -u daemon moodle \
  php /bitnami/moodle/admin/cli/upgrade.php --non-interactive --allow-unstable

# 3. confirm, then restore the test data
docker compose exec -T mariadb mariadb -ubn_moodle bitnami_moodle -N -B \
  -e "SHOW TABLES LIKE 'mdl_local%';"      # expect mdl_local_securecoursehub_req
./setup.sh && ./tests/run_security_tests.sh
```

If you are on Windows and `docker compose exec` mangles `/bitnami/...` into a
Windows path, prefix the command with `MSYS_NO_PATHCONV=1`.

### Full cold-start verification

To prove the whole thing reproduces from nothing:

```bash
docker compose down -v && ./setup.sh && ./tests/run_security_tests.sh
```

Expect 17/17 again. This destroys all data, so re-run only when you mean it.

---

## Phase 5 — Documents

- `plugin/README.md` — install steps, accounts, capabilities, the documented
  delete rule, limitations. Check the versions in §1 still match your instance.
- `report/report.md` — complete except items marked `TODO(HUMAN)`:
  - §Team members — names and student numbers
  - §14 — screenshot captions once images are taken
  - §13 — the five manual check results (M1–M5)
  - §16 — the post-demonstration reflection paragraph
- Export the report to `report/report.pdf`, then **re-run `./package.sh`** so the
  ZIP carries the PDF instead of the Markdown fallback.

Verify the Section 15 checklist in report §15 line by line against what you saw
in Phases 1–3. Do not tick anything you have not personally observed.

---

## Phase 6 — Points to challenge me on

An audit is worth more if you attack the decisions. These are the judgement
calls where a reasonable person could disagree:

1. **Raw storage instead of `PARAM_TEXT` for title/description.** The spec text
   says `PARAM_TEXT`, but its own acceptance test 13 expects the injected
   `<script>` string to be **stored** and rendered escaped — and `PARAM_TEXT`
   strips tags, so those two requirements conflict. The implementation stores
   verbatim and escapes at every output site. Rationale is in report §10. If your
   TA expects literal `PARAM_TEXT`, this is the one line of defence you must be
   able to argue.
2. **`debugdisplay = 0` while `debug = DEVELOPER`.** Developer debugging is on as
   the lab requires, but display is off so nothing internal reaches an HTTP
   response. Argued in report §2.
3. **No `manageall` capability.** Optional in the spec; deliberately omitted so
   management stays course-scoped. Argued in report §1.
4. **Delete rule.** Owner may delete only while `open`; a teacher may delete any
   request in their course. Documented in README §4 — the lab requires the rule
   to be *documented*, and this is where it lives.
5. **Test independence.** Tests 03 and 15 create rows, so counts grow across
   runs. Assertions read the database before and after rather than assuming
   fixed numbers, which is why re-running stays green — check that logic in
   `tests/run_security_tests.sh` yourself rather than trusting it.

Everyone on the team must be able to explain: authentication vs authorization vs
role/capability vs ownership; why the sesskey exists when the session cookie
already identifies the user; and why `ajax.php` recomputes the course context
from the stored record instead of the posted `courseid`.
