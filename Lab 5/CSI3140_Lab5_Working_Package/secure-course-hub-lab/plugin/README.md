# Secure Course Hub (`local_securecoursehub`)

A Moodle **local plugin** for CSI 3140 Laboratory 5. 
Students raise course help
requests; teaching staff triage them. 
Everything is built on Moodle's own
authentication, session, capability and database APIs — no Moodle core file is
modified and no separate password store exists.

---

## 1. Environment

Verified on the environment below. Both versions were read from the running
instance (`$CFG->release` and `PHP_VERSION`), not copied from documentation.

| Component | Version |
|---|---|
| Moodle | **4.5.4 (Build: 20250414)** — version string `2024100704` |
| PHP | **8.1.32** |
| Database | MariaDB 11.4 (`utf8mb4` / `utf8mb4_unicode_ci`) |
| Web server | Apache 2.4.63 (Bitnami image) |
| Host platform | Docker Desktop, `bitnamilegacy/moodle:4.5` + `bitnamilegacy/mariadb:11.4` |
| Site URL | `http://127.0.0.1:8080` |

The site must be reached at `127.0.0.1`, never `localhost` — Moodle compares
the request host to `$CFG->wwwroot` and a mismatch bounces every request back to
the login page.

Developer debugging is enabled (`debug = DEVELOPER`) but debug display is
turned off (`debugdisplay = 0`). 
Diagnostics go to the server log where the
developer can read them, and never into an HTTP response where they could leak
paths, SQL or configuration values.

---

## 2. Installation

### 2.1 Docker (the repeatable path)

From the `secure-course-hub-lab/` directory:

```bash
docker compose up -d      # start Moodle 4.5 + MariaDB 11.4
./setup.sh                # or: powershell -ExecutionPolicy Bypass -File setup.ps1
```

`setup.sh` / `setup.ps1` are **idempotent** — re-run either after any plugin edit.
Each run waits for Moodle, re-copies the plugin from the read-only `/plugin-src`
mount into `<moodle>/local/securecoursehub`, runs the Moodle CLI upgrade as the
web-server user (`daemon`, so cache ownership stays correct), seeds the synthetic
accounts, course, enrolments and sample requests, and purges caches.

Then open: <http://127.0.0.1:8080/local/securecoursehub/index.php?courseid=2>

### 2.2 Manual install on any existing Moodle 4.5

1. Copy the plugin folder to `<moodle>/local/securecoursehub`.
2. Log in as an administrator and visit **Site administration → Notifications**,
   or run `php admin/cli/upgrade.php` as the web-server user.
3. Moodle reports *Secure Course Hub* as installed and creates the
   `local_securecoursehub_req` table.
4. Reach the plugin at `/local/securecoursehub/index.php?courseid=<id>` for a
   course in which you are enrolled.

### 2.3 Upgrading

Bump `$plugin->version` in `version.php` whenever `db/install.xml` changes, then
re-run the upgrade. Back up before schema changes.

---

## 3. Test accounts

Synthetic accounts, created by the setup script, valid only in this throwaway
local container. 

| Account | Password | Role | Notes |
|---|---|---|---|
| `admin` | `Admin#1234` | Site administrator | Created by the container's install variables |
| `staff1` | `Test1234!` | Editing teacher | Enrolled in the demo course |
| `learner1` | `Test1234!` | Student | Enrolled; owns the two seeded requests |
| `learner2` | `Test1234!` | Student | Enrolled; owns nothing |

These map 1:1 to the lab specification's *administrator / teacher / Student A /
Student B*. The demonstration course is **CSI3140-DEMO** (course id 2).

---

## 4. Permissions and capabilities

All three capabilities are defined at `CONTEXT_COURSE` in `db/access.php`.

| Capability | Type | Default archetypes | Purpose |
|---|---|---|---|
| `local/securecoursehub:viewown` | read | student, editingteacher, manager | Open the page and see your own requests |
| `local/securecoursehub:createrequest` | write | student, editingteacher, manager | Raise a request in an accessible course |
| `local/securecoursehub:managecourserequests` | write, `RISK_PERSONAL` | editingteacher, manager | See and manage every request in that course |

Defining a capability — each one is enforced server side on every
entry point:

- `require_capability()` where access is mandatory (page entry, teacher actions).
- `has_capability()` only to decide which interface to draw. Hiding a button is
  a usability choice; the server repeats the check regardless.
- Capability checks for record operations use the context recomputed from the
  **record's own `courseid`**, never a course id supplied by the browser.

### Ownership rules (resource-level authorisation)

| Operation | Rule |
|---|---|
| View own | `userid = $USER->id` filter in the query itself |
| View course queue | `managecourserequests` in that course's context |
| Edit | Owner only, **and** only while `status = 'open'` |
| Delete | Owner **and** `status = 'open'`; **or** a user holding `managecourserequests` in the record's course |
| Set status / response | `managecourserequests` in the record's course only |

**Documented delete rule:** a student may delete only their own request while it
is still open. Once staff move a request to `inprogress` or `resolved`, the
student can no longer edit or delete it, which preserves the record of a handled
request. A teacher or manager may delete any request in a course where they hold
`managecourserequests`.

---

## 5. Running it

1. Sign in as `learner1`, open the plugin page for course 2.
2. Create a request — the row appears immediately via `fetch()`, with no reload.
3. Edit or delete it while it is open.
4. Sign in as `staff1`: the course queue appears with a status filter. Change a
   status and press **Update** — a JSON round trip updates the row in place.
5. Sign in as `learner2`: the page shows no requests, and any attempt to reach
   learner1's records by id is refused by the server.

---

## 6. Running the tests

```bash
./tests/run_security_tests.sh
```

17 automated checks log in as each account through the real Moodle login form and
then call `ajax.php` directly, bypassing the interface entirely — every rule must
hold against a hostile client. Results are written to `tests/evidence.txt` with
role, action, expected result, actual HTTP code and body, and PASS/FAIL.

**Session cookies and sesskey values are redacted as `<redacted>`; no secret is
ever written to the evidence file.** The script also lists the manual browser
checks it cannot perform (screenshots, Network tab, expired-session behaviour,
console-error check).

---

## 7. Security controls implemented

| Control | Where |
|---|---|
| Authentication | `require_login()` on `index.php` and `ajax.php`; the user is always `$USER`, never a browser-supplied id |
| Authorization | `require_capability()` in the correct course context on every entry point |
| Ownership | `request_service` compares `(int)$record->userid === (int)$USER->id`; teachers pass only via capability in the record's own course |
| CSRF | `confirm_sesskey()` on every `ajax.php` action; `require_sesskey()` on every form POST; GET never changes state |
| XSS | `s()`, `format_string()` and `html_writer` on output; `textContent`/`createElement` in JS — `innerHTML` is never used with server data |
| Input validation | Presence, type (`PARAM_*`), length (title ≤ 80, description ≤ 2000, response ≤ 500) and a status whitelist, all server side |
| Injection | Moodle `$DB` API with placeholder parameters only; no user input is concatenated into SQL |
| Safe errors | Only this plugin's own language strings are returned; everything else becomes a generic message and is logged locally |
| Session safety | Sesskeys and cookies are never printed, logged or written to any shipped file |

---

AI was used to faciliate the creation of report and readme's
