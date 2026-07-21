# CSI 3140 — Lab 5: Secure Course Hub — Master Requirements Checklist

**Deliverable:** One ZIP on Brightspace • **Due:** Fri July 24, 2026, 11:59 PM • **Team:** 2–3 students
**Stack (mandatory):** Moodle LMS (local), PHP, MariaDB/MySQL, HTML5/CSS3/JS, Fetch/AJAX, JSON, Moodle Access API + Database API
**Plugin:** `local_securecoursehub` under `local/securecoursehub/` — **no Moodle core modifications**

---

## 0. Critical constraints (read first)
- [ ] It must be a **real Moodle local plugin in PHP** — a Moodle install alone (or a Node app) does NOT satisfy the lab.
- [ ] **Do not modify Moodle core files.** Everything lives in the plugin.
- [ ] **Do not build a separate password store** or replace Moodle login — use Moodle's existing auth/sessions.
- [ ] Use **synthetic accounts and synthetic request content only**.
- [ ] Cite any external code/icons/libraries; **disclose AI-assisted code** per academic integrity rules.
- [ ] Do not expose the instance publicly. Developer debugging = local only.
- [ ] All team members must understand every part (code, security, DB, JSON, report).

---

## 1. Environment setup (Part A / Tasks 1–2)
- [ ] Install local Moodle: web server + PHP + MariaDB/MySQL (XAMPP/WAMP/MAMP/Docker/official package).
- [ ] Moodle home page + admin page open with **no fatal errors**.
- [ ] **Record exact Moodle version and PHP version** (put in README env section).
- [ ] Enable developer debugging (local only).
- [ ] Create **exactly 4 accounts**: 1 Administrator, 1 Teacher, Student A, Student B.
- [ ] Create **1 demonstration course**; enrol teacher + both students with correct roles.
- [ ] Participants page shows teacher + both students with expected roles.
- [ ] Screenshot of the running site.

---

## 2. The 12 required tasks (do in numerical order)
1. [ ] **Install Moodle locally** — README env section + screenshot + recorded versions.
2. [ ] **Create test users + course** — 4 accounts, 1 course, correct enrolments (synthetic data).
3. [ ] **Plugin skeleton** — `version.php`, language file, `index.php`, `README.md`; installs via Site administration; page loads for an authenticated authorized user.
4. [ ] **Database table** (`db/install.xml`) named `request` with: `id, courseid, userid, title, description, status, response, timecreated, timemodified`. Allowed statuses: `open`, `inprogress`, `resolved`. New request stores authenticated userid, courseid, status `open`, both timestamps.
5. [ ] **Capabilities** (`db/access.php` + lang strings): `viewown`, `createrequest`, `managecourserequests`. Student can create/view own but NOT manage queue; teacher can manage in the demo course.
6. [ ] **Protect every server entry point** — load `config.php`, `require_login($course)`, establish course context, `require_capability()` before any read/write. Unauthenticated → redirected/rejected; logged-in-but-unpermitted → access denied.
7. [ ] **Student operations** — create; list only own; edit own **open** request; delete own **open** request. **Resolved requests can't be edited/deleted by a student.** Student A cannot view/edit/delete Student B's record by changing an id/URL param.
8. [ ] **Teacher operations** — list course requests, filter by status, set status (open/inprogress/resolved), add/update a **response ≤ 500 chars**. Denied in a course where capability isn't granted.
9. [ ] **Moodle Database API** (`classes/local/request_service.php`) — CRUD with `$DB` methods. Validate existence, course association, ownership, and permitted state transitions before each write. **No SQL concatenation of user input.** Missing record → safe not-found. Failed validation leaves DB unchanged.
10. [ ] **JSON + fetch()** — at least one **state-changing** op via `fetch()` + JSON, updating the page **without full reload** (recommended: teacher status update). Endpoint = `ajax.php` or approved Moodle external service + JS module. Network tab shows JSON request+response; success updates page; failure shows a visible message.
11. [ ] **Security controls** — validate `sesskey` for **every** state-changing action; validate type/length/allowed values; escape untrusted output; no secrets/internal errors leaked. Invalid sesskey rejected; injected script shown as text; invalid status + overlength input rejected.
12. [ ] **Test, document, package** — run all Section-14 tests, complete the checklist, write the report, build one ZIP with **only** plugin + README + report + screenshots. ZIP installs on a clean Moodle; contains no core/moodledata/config.php/passwords/session data/credentials/caches.

---

## 3. Required plugin file structure
```
local/securecoursehub/
├── version.php            # component name, version, requires, maturity
├── index.php              # secure entry page: context, auth, capability, render
├── ajax.php               # OR approved external/AJAX service — validated JSON path
├── db/
│   ├── access.php         # capability definitions + role archetypes
│   ├── install.xml        # custom table (Moodle XMLDB format)
│   └── services.php       # only if Moodle external functions are used
├── classes/
│   ├── local/request_service.php   # business + DB logic
│   └── external/          # optional approved service classes
├── lang/en/local_securecoursehub.php   # interface + capability strings
├── amd/src/dashboard.js   # fetch()/JSON client logic (your JS adapts here)
├── styles.css             # optional
├── README.md
└── pix/                   # optional icon
report.pdf                 # separate deliverable
```

---

## 4. Authorization & ownership (Part C)
Capabilities to define + default archetypes:
- [ ] `viewown` — view plugin + own requests (student, teacher, manager)
- [ ] `createrequest` — create request for enrolled course (student, teacher, manager)
- [ ] `managecourserequests` — view/update requests in authorized course (editingteacher, manager)
- [ ] `manageall` — *optional* site-wide admin access (manager only)

Rules:
- [ ] `require_capability()` where access is mandatory; `has_capability()` for choosing UI paths.
- [ ] Check course enrolment/access where required.
- [ ] Student ops: verify `record->userid === $USER->id`.
- [ ] Teacher ops: verify capability **and** the record's course.
- [ ] Never trust hidden fields, JS vars, URL params, or claimed roles.
- [ ] Safe access-denied response for authenticated-but-unpermitted users.

---

## 5. CRUD & database rules (Part D)
- [ ] Create: use authenticated userid; validate course access/title/description/initial status; set timestamps.
- [ ] Read own: only current user's records (unless management capability applies).
- [ ] Read course: requires course-management capability in the record's course context.
- [ ] Update: students → own **open** only; teachers → status/response for authorized course records.
- [ ] Delete: documented rule only (owner deletes open request, or manager deletes authorized record).
- [ ] Not found: clear 404-style / Moodle exception, no unrelated records revealed.
- [ ] Invalid input: clear validation error, no partial/unsafe writes.
- [ ] Use `required_param()`/`optional_param()`/validated JSON with correct `PARAM_*` types.
- [ ] Use `$DB` methods (no raw unparameterized queries); keep logic in service class; use `time()` for timestamps.
- [ ] Never expose DB errors, stack traces, hashes, config values, or server paths.

---

## 6. JSON / dynamic interface (Part E)
- [ ] At least one state-changing op via `fetch()` + JSON response, no full reload.
- [ ] Same-origin credentials only; never copy auth secrets into JS.
- [ ] Server validates auth + capability + context + ownership + CSRF before processing.
- [ ] Structured success/error JSON responses.
- [ ] Handle: network failure, invalid JSON, unauthenticated/expired session, forbidden op, missing record, validation error.
- [ ] Every client restriction is duplicated by a server-side check.

---

## 7. Mandatory security & privacy controls (Part F)
- [ ] **Authentication** — protected pages require Moodle login, use platform user.
- [ ] **Authorization** — capabilities checked server-side in correct context.
- [ ] **Ownership** — can't reach another student's record by changing an id.
- [ ] **CSRF** — every state-changing request carries + validates `sesskey`.
- [ ] **XSS** — escape/`$OUTPUT` for untrusted values; avoid `innerHTML` for untrusted text.
- [ ] **Input validation** — type, presence, length, allowed values, business rules (server-side).
- [ ] **Injection prevention** — Moodle DB API / parameterized only; no SQL concatenation.
- [ ] **Safe errors** — useful but non-sensitive; no internal details.
- [ ] **Session safety** — never print/store/screenshot session ids or sesskeys.
- [ ] **Privacy** — collect only required data; document access + retention in report.
- [ ] **Secrets** — no real passwords/tokens/DB credentials/config.php/moodledata submitted.
- [ ] **Logging** — may log security failures locally (no passwords/cookies/sesskeys/sensitive text).
- [ ] Report **maps ≥ 4 implemented controls** to risks (broken access control, auth failures, injection, misconfig, XSS, CSRF, logging/monitoring).

---

## 8. Mandatory acceptance tests (Part G) — record role + expected + actual + pass/fail
- [ ] Unauthenticated user opens plugin page → login required/denied before data shown.
- [ ] Student creates valid request → record created for that student + course.
- [ ] Student submits missing/invalid fields → safe validation rejection.
- [ ] Student views own records → only their records returned.
- [ ] Student changes another student's record id → denied, nothing exposed/modified.
- [ ] Student calls teacher-only op directly → server denies even if UI button hidden.
- [ ] Teacher views authorized course records → shown.
- [ ] Teacher attempts unauthorized course → denied per capability/context.
- [ ] Missing/invalid sesskey on state change → rejected, data unchanged.
- [ ] Injected HTML/JS text → displayed safely, does not execute.
- [ ] Missing record → clear not-found, no unrelated data revealed.
- [ ] Expired/logout session during AJAX → client shows auth/session error, stops.
- [ ] Browser + server diagnostics → no unexplained console/PHP/DB errors.

---

## 9. Integration & security checklist (Section 15) — complete in report
Site runs + version documented • plugin installs without core mods • course + accounts configured • every protected page verifies login • capabilities in `db/access.php` • auth checks use correct context • student ownership checked • teacher access limited to authorized course • uses Moodle DB API • server-side input validation • sesskey/CSRF on state changes • output escaped / scripts don't execute • ≥1 JSON/fetch interaction works dynamically • client restrictions duplicated server-side • unauthenticated + forbidden cases tested • invalid input + missing records tested • no secrets in submission • README complete • report complete.

---

## 10. Deliverables — the ZIP (Section 17)
- [ ] Plugin folder **only** (not full Moodle install).
- [ ] All PHP, XML, lang, JS, CSS, image files the plugin needs.
- [ ] `README.md` — installation, upgrade, test accounts, permissions, run instructions, known limitations.
- [ ] `report.pdf` — satisfying Section 18 (below).
- [ ] `screenshots/` — install, functionality, security, JSON, testing evidence (no secrets visible).
- [ ] Optional DB export (synthetic plugin data only) *if the TA requests it*.
- [ ] Any modified instructor starter files, with modifications clearly identified.
- [ ] ZIP **excludes**: Moodle core, moodledata, config.php, DB credentials, real passwords, browser profiles, session cookies, sesskeys, caches, secret-bearing logs, unrelated personal info.
- [ ] Verify ZIP installs on a clean local Moodle instance.

---

## 11. Report (report.pdf) — Section 18
- [ ] Course code, lab number, team members + student numbers.
- [ ] Short description of Secure Course Hub + implemented user stories.
- [ ] Local environment + exact Moodle version.
- [ ] **Architecture diagram**: browser → Moodle/PHP → auth/session → authz/capabilities → plugin logic → database.
- [ ] Plugin file structure + purpose of major files.
- [ ] Data model + custom table description.
- [ ] Authentication + session workflow.
- [ ] Roles, capabilities, contexts + **access-control matrix**.
- [ ] Ownership / resource-level authorization rules.
- [ ] JSON/fetch interaction with example request + response.
- [ ] Input validation, error handling, XSS, CSRF, DB safety.
- [ ] **Privacy analysis**: data collected, who can access, retention assumptions, data minimization.
- [ ] Test table (successful + denied cases).
- [ ] Selected screenshots with captions, no exposed secrets.
- [ ] Completed checklist.
- [ ] Reflection: problems, solutions, limitations.
- [ ] References (Moodle docs + any external code).

---

## 12. Live demonstration (Section 16) — required for grading
All team members present; each can explain the submission.
- [ ] Start local Moodle + open demo course.
- [ ] Show plugin folder structure + explain main files.
- [ ] Log in as student → create/view own request.
- [ ] Attempt unauthorized student op → explain why server denies.
- [ ] Log in as teacher → authorized course-management op.
- [ ] Show capability + ownership checks in code.
- [ ] Show JSON/fetch in browser dev tools.
- [ ] Demonstrate/explain CSRF (sesskey) safely.
- [ ] Show input validation + safe XSS-string handling.
- [ ] Explain authN vs authZ vs role/capability vs ownership.
- [ ] Explain one privacy decision + one known limitation.

---

## 13. Grading rubric (/100)
| Component | Pts |
|---|---|
| Local Moodle deployment, course/accounts, repeatable setup | 8 |
| Plugin structure, install, lang strings, README, maintainability | 10 |
| Authentication + session integration (Moodle mechanisms) | 10 |
| Capabilities, RBAC, contexts, server-side authorization | 15 |
| Ownership + course-level resource access controls | 10 |
| Server-side processing, DB API, CRUD rules, data integrity | 12 |
| JSON/fetch integration + dynamic client behavior | 10 |
| Security: CSRF, XSS, validation, safe errors, injection, secrets | 12 |
| Testing evidence, negative tests, debugging, results | 6 |
| Demonstration (all members present) | 3 |
| Report quality, privacy analysis, architecture, checklist, reflection | 4 |
| **Total** | **100** |

Highest-weighted areas: **authorization/RBAC (15)**, **DB/CRUD (12)** and **security (12)** — prioritize these.
