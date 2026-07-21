# PROMPT FOR CLAUDE CODE — CSI 3140 Lab 5: Secure Course Hub (Moodle local plugin)

You are building a complete, submission-ready university lab deliverable. Follow this document exactly. Where this document conflicts with any other source (lecture slides, tutorials, your training data), **this document wins** because it is distilled from the graded lab specification.

## Mission

Build, deploy locally via Docker, and test a Moodle **local plugin** named `local_securecoursehub` ("Secure Course Hub") that lets students file course help requests and teachers manage them, with full server-side authentication, capability-based authorization, ownership checks, CSRF (sesskey), XSS-safe output, validated input, and one fetch()/JSON dynamic operation. Then produce the README, a report draft, an automated test-evidence log, and a clean submission ZIP.

Deadline pressure is real (due Friday July 24, 11:59 PM). Prioritize: working plugin → security controls → tests → docs.

## Hard constraints (violating any of these fails the lab)

1. Everything lives in the plugin folder. **Never modify Moodle core files.**
2. Use Moodle's existing login/session. **No separate password store.** Never trust a browser-supplied userid — always `$USER->id` after `require_login()`.
3. All CRUD through Moodle's `$DB` API. **Never concatenate user input into SQL.**
4. Every state-changing request validates **sesskey**. GET never changes state.
5. All untrusted output escaped (`s()`, `format_string()`, `html_writer`; `textContent` in JS — never `innerHTML` with untrusted text).
6. Statuses are exactly: `open`, `inprogress`, `resolved` (NOT in_progress/closed — some reference material uses those; they are wrong for this lab).
7. Teacher response note: **max 500 characters**, enforced server-side.
8. Errors are safe: no stack traces, SQL errors, paths, config values, cookies, or sesskeys in any response, log we keep, or file we ship.
9. Synthetic data only. No real names/passwords/secrets anywhere in the repo or ZIP.

## Environment (Part A) — Docker, per the course's reference setup

Create a project folder `secure-course-hub-lab/` containing:

- `docker-compose.yml` with two services:
  - `moodle`: Bitnami Moodle **4.5** image (note: Bitnami moved images in 2025 — use the `bitnamilegacy` registry paths if the standard `bitnami/moodle` tag fails to pull), ports 8080:8080 and 8443:8443.
  - `mariadb`: Bitnami MariaDB **11.4** (same registry note).
  - The plugin source mounted read-only at `/plugin-src` in the moodle container.
- `setup.sh` and `setup.ps1` — **idempotent** scripts that: wait for Moodle to be up; copy `/plugin-src` into `<moodle>/local/securecoursehub`; run the Moodle CLI upgrade to install the plugin (run CLI as the web-server user, e.g. `daemon`, so cache permissions stay correct); create test users, course, and enrolments; seed 2 sample requests owned by learner1; purge caches. Re-running must be safe and must re-copy plugin edits.
- The plugin itself in `plugin/` (structure below).

Access rules:
- Site URL is `http://127.0.0.1:8080` — always **127.0.0.1, never localhost** (host mismatch with wwwroot bounces to login).
- Plugin page: `http://127.0.0.1:8080/local/securecoursehub/index.php?courseid=2`

Test identities to create (password `Test1234!` unless noted):
| Account | Role |
|---|---|
| `admin` / `Admin#1234` | Site administrator (created by the Bitnami install env vars) |
| `staff1` | Editing teacher, enrolled in the demo course |
| `learner1` | Student, enrolled; owns the 2 seeded requests |
| `learner2` | Student, enrolled; owns none |

(The lab spec calls these administrator / teacher / Student A / Student B — map them 1:1 in docs.)

After install, record the **exact Moodle version and PHP version** (from CLI or admin page) into README — this is graded.

## Plugin structure (required)

```
local/securecoursehub/
├── version.php
├── index.php
├── ajax.php
├── db/
│   ├── access.php
│   └── install.xml
├── classes/local/request_service.php
├── lang/en/local_securecoursehub.php
├── amd/src/dashboard.js        (plain ES module is acceptable; load with $PAGE->requires)
├── styles.css                  (optional, light styling)
├── README.md
└── pix/                        (optional)
```

`version.php`: component `local_securecoursehub`, version like `2026072100`, `requires` matching the installed Moodle 4.5 build, MATURITY_ALPHA, release 0.1.0. Bump version whenever install.xml changes.

## Database (db/install.xml, XMLDB format)

One table `local_securecoursehub_req`:

| Field | Type | Notes |
|---|---|---|
| id | int(10) auto, PK | |
| courseid | int(10) not null | FK-style ref to course |
| userid | int(10) not null | record owner = authenticated creator |
| title | char(80) not null | |
| description | text | |
| status | char(20) not null default 'open' | whitelist: open, inprogress, resolved |
| response | text null | teacher note, ≤500 chars enforced in PHP |
| timecreated | int(10) not null | server time() |
| timemodified | int(10) not null | server time() |

Add indexes on courseid and userid.

## Capabilities (db/access.php + matching lang strings)

All at `CONTEXT_COURSE`:

| Capability | captype | Archetypes CAP_ALLOW |
|---|---|---|
| `local/securecoursehub:viewown` | read | student, editingteacher, manager |
| `local/securecoursehub:createrequest` | write | student, editingteacher, manager |
| `local/securecoursehub:managecourserequests` | write, riskbitmask RISK_PERSONAL | editingteacher, manager |

(`manageall` site-wide is optional — skip it, we don't need the scope.)

Every capability must actually be enforced server-side; defining it isn't enough.

## Server security chain (identical philosophy for index.php and ajax.php)

index.php: `require config.php` → `required_param('courseid', PARAM_INT)` → `get_course` → `require_login($course)` → `context_course::instance` → `require_capability(viewown)` → `$PAGE` setup → render.

ajax.php (POST, JSON body only):
1. `require config.php`; `require_login()`.
2. `json_decode(file_get_contents('php://input'))`; reject invalid JSON with 400.
3. **`confirm_sesskey($payload->sesskey)`** for every action (all our actions are state-changing or data-returning; validate sesskey on all state changes at minimum) — invalid/missing → 400/403 JSON, nothing written.
4. `clean_param` courseid → get_course → `context_course::instance` → capability check appropriate to the action.
5. Ownership check where the action targets a record: load record with MUST_EXIST inside try/catch; compare `(int)$record->userid === (int)$USER->id`; teachers pass via `has_capability(managecourserequests)` **in the record's own course context** (recompute context from `$record->courseid`, don't trust posted courseid).
6. Delegate to `request_service`; return structured JSON `{"success": true, ...}` or `{"success": false, "error": "..."}` with proper HTTP codes (400 invalid, 403 forbidden, 404 not found).

## Business rules (request_service.php — all writes go through here)

- **create** (student or teacher with createrequest): owner = `$USER->id`, course from validated courseid + enrolment via require_login($course) on the calling page / capability in context; title required, ≤80 chars, PARAM_TEXT; description PARAM_TEXT; status forced to `open` server-side (ignore any posted status); timestamps = time().
- **list_own**: filter by courseid + `$USER->id` only.
- **list_course** (teacher): requires managecourserequests in that course context; optional status filter validated against the whitelist.
- **update_own** (student): only own records AND status === 'open'. May edit title/description. Resolved (and inprogress) records: student cannot edit.
- **update_status / respond** (teacher): requires managecourserequests in record's course; status must be in whitelist; response ≤500 chars (mb_strlen), PARAM_TEXT; set timemodified.
- **delete**: student may delete **own open** records only; teacher may delete records in an authorized course. Document this rule in README/report.
- **not found**: MUST_EXIST exception mapped to safe 404-style JSON/exception; never reveal whether other records exist.
- Validation failure = no write at all (no partial writes).

## Dynamic JSON requirement (Task 10)

Implement **teacher status update** (the spec's recommended choice) via fetch() in `amd/src/dashboard.js`:
- POST JSON `{action:'update_status', id, status, sesskey: M.cfg.sesskey}` to ajax.php.
- On success: update that row's status text in the DOM **without page reload**, show a success message.
- On failure (including !response.ok): show a visible error message; handle network failure, invalid JSON response, 401/403 (session expired → tell user to log in again), 404.
- Also wire student "create request" via fetch for a nicer demo (optional but cheap — row appears dynamically).
- All DOM insertion of server data uses `textContent` / createElement. Never innerHTML with data.
- index.php renders the form + tables server-side (escaped with s()/format_string/html_writer), shows teacher panel only when `has_capability(managecourserequests)` — and the server re-checks regardless.

## Acceptance tests (Part G) — automate what you can, log everything

Write a script `tests/run_security_tests.sh` that uses curl with cookie jars to log in as each account (Moodle login form: get login token from the login page, then POST username/password) and exercises ajax.php directly. For each test print: role, action, expected, actual HTTP code + body, PASS/FAIL. Save output to `tests/evidence.txt`. Cover:

1. Unauthenticated GET of index.php → redirect to login (302), no data.
2. Unauthenticated POST to ajax.php → rejected before data.
3. learner1 creates valid request → 200, success, record has learner1's userid, courseid, status open, timestamps.
4. learner1 create with missing title → 400, safe message, no record.
5. learner1 create with 100-char title (overlength) → rejected.
6. learner1 list → only learner1's records.
7. learner2 list → does NOT include learner1's records.
8. learner2 attempts update/delete on learner1's record id → 403, record unchanged.
9. learner1 calls update_status (teacher-only) directly → 403.
10. staff1 lists course records → sees all; staff1 update_status to inprogress → 200, row updated.
11. staff1 response note of 501 chars → rejected; 500 chars → accepted.
12. Valid action with missing sesskey → rejected, DB unchanged. Wrong sesskey → rejected.
13. Create with title `<script>alert(1)</script>` → stored, and page/JSON renders it as text (verify the HTML response contains the escaped entity, not an executable tag).
14. Request nonexistent record id 99999 → 404-style, no info leak.
15. Invalid status value `deleted` → 400.

Manual-only tests to list for the human (cannot be automated here): browser screenshot evidence, Network-tab screenshots of the JSON exchange, expired-session behavior in the UI, "no console errors" check.

## Documentation to generate

**README.md** (in plugin): environment (Docker, exact Moodle + PHP versions — fill from the running instance), install steps (compose up + setup script + manual fallback via Site administration), test accounts table, permissions/capabilities explanation, how to run, how to run the test script, delete-rule documentation, known limitations.

**report/report.md** (to be converted to report.pdf by the student): full skeleton with every section required by the spec, with all technical sections WRITTEN (architecture description + a mermaid/ASCII architecture diagram: browser → Moodle/PHP → auth/session → capabilities → plugin service → DB; file structure; data model; authN/session workflow incl. session cookie vs server-side session vs sesskey explanation; access-control matrix; ownership rules; JSON interaction with a real example request/response captured from the test run; validation/XSS/CSRF/injection/safe-error handling; privacy analysis — data collected, who can access, retention assumption, minimization; the completed Section-15 checklist; risk mapping table linking ≥4 controls to risks: broken access control→capabilities+ownership+context, CSRF→sesskey, XSS→escaping/textContent, injection→$DB API+whitelists, information leakage→safe errors+secret scan). Leave clearly marked `TODO(HUMAN)` placeholders ONLY for: team member names + student numbers, screenshots, demo reflections.

**Test table** in the report populated from `tests/evidence.txt` results.

## Packaging (Task 12)

Script `package.sh` that builds `CSI3140_Lab5_Submission.zip` containing ONLY:
- the plugin folder `local/securecoursehub/` (with README.md inside)
- `report/report.pdf` if present, else report.md with a note to export
- `screenshots/` (create the empty folder + a SCREENSHOTS_NEEDED.md listing exactly which screenshots the human must take: running site + versions, Participants page with roles, plugin page as learner1, denied attempt as learner2, teacher panel + status change, Network tab JSON request/response, XSS text rendered safely, missing-sesskey rejection — each with the rule: no cookies/sesskeys/passwords visible)
- `tests/evidence.txt`

The script must run a **secret scan** before zipping: grep the payload for `config.php`, `moodledata`, password strings, `MoodleSession`, sesskey values, `.env`, DB credentials — abort with a report if anything matches. Assert the ZIP contains no Moodle core files.

Final verification pass (required before you claim completion): `docker compose down -v`, fresh `up -d`, run setup script, run the full test script, confirm all automated tests PASS on a clean instance, confirm the plugin folder from the ZIP installs cleanly. Print a final checklist of DONE items vs TODO(HUMAN) items.

## What NOT to do

- Don't port the student's Lab 4 Node.js code (datastore.js/Express). It's a different stack; write the PHP fresh.
- Don't use statuses in_progress/closed. Don't skip the createrequest capability. Don't put business logic in index.php.
- Don't echo unescaped values anywhere, even in "debug" output.
- Don't store or print session cookies/sesskeys in evidence files (redact tokens in evidence.txt as `<redacted>`).
- Don't mark the project complete while any automated test fails or any required file is missing.
