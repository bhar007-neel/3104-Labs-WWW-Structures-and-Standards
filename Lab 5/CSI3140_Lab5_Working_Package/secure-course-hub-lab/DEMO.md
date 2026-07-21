# Live demonstration runbook

Covers Section 16 of the lab specification. Two rules that make this
non-negotiable:

> **"The report will be considered for grading only if the demonstration has
> been completed."** — the demo gates the entire submission, not just its own
> 3 marks.
>
> **"All team members must be present and each student must be able to explain
> the submission."** — any member may be asked about any part. Splitting the
> work does not split the questioning.

Budget 15–20 minutes. Rehearse once end to end.

> ⚠️ The older `../DEMO_SCRIPT.md` template is **out of date** and contradicts
> this implementation in two places (it claims injected values are "cleaned",
> and that the JSON update sends a course id). Use this file instead.

---

## Pre-flight — 10 minutes before

```bash
cd "Lab 5/CSI3140_Lab5_Working_Package/secure-course-hub-lab"
docker compose up -d && ./setup.sh
./tests/run_security_tests.sh      # confirm 17/17 before they walk in
```

Have open and ready:

| Window | Contents |
|---|---|
| Browser tab 1 | `http://127.0.0.1:8080` logged out (for the unauthenticated demo) |
| Browser tab 2 | A second browser **profile or private window** — you need two sessions at once |
| Editor | `plugin/ajax.php`, `plugin/classes/local/request_service.php`, `plugin/db/access.php` |
| Terminal | In the project directory, ready to run `./tests/demo_csrf.sh` |

Use **`127.0.0.1:8080`, never `localhost`** — a host mismatch with `$CFG->wwwroot`
bounces every request to the login page, which looks like a broken plugin in
front of an examiner.

Log in as: `admin`/`Admin#1234`, `staff1`/`Test1234!`, `learner1`/`Test1234!`,
`learner2`/`Test1234!`.

**Throughout: never expose a cookie or a real sesskey.** In DevTools keep the
Cookies tab and Cookie headers closed.

---

## The twelve required items

Section 16 lists these explicitly. Tick each one off as you go.

### 1. Start the environment and open the demonstration course

Show the Moodle home page loading cleanly, then the course **CSI3140-DEMO**.

> "This is a local Moodle 4.5.4 on PHP 8.1.32, running in Docker with MariaDB
> 11.4. It is bound to 127.0.0.1 and is not publicly exposed."

### 2. Show the plugin folder structure and explain the main files

Show `plugin/` in the editor.

> "Everything lives under `local/securecoursehub`. No Moodle core file is
> modified. `version.php` is the plugin identity, `db/access.php` declares the
> three capabilities, `db/install.xml` creates the request table,
> `classes/local/request_service.php` holds every business rule and every
> database call, `index.php` is the protected page, `ajax.php` is the JSON
> endpoint, and `amd/src/dashboard.js` is the fetch client."

Be ready to answer *why* the service class exists: because every write funnels
through one place, a rule cannot be forgotten when a new entry point is added.

### 3. Log in as a student and create / view an own request

As `learner1`, create a request. It appears **without a page reload**.

> "The owner is taken from `$USER->id` on the server. The browser never supplies
> a user id, and if it did we would ignore it."

Show the "My requests" table listing only learner1's records.

### 4. Attempt an unauthorized student operation and explain the denial

The strongest version of this uses the URL, not a button.

1. As `learner1`, note a record id from the queue.
2. As `learner2` (second window), visit
   `…/index.php?courseid=2&editid=<learner1's id>`.
3. The page loads (**HTTP 200**), the edit form does **not** appear, an error
   notice is shown, and none of learner1's data is rendered.

> "Nothing in the interface offered that action. I constructed the URL by hand.
> The server refuses because ownership is checked in the service layer, not in
> the page that draws the buttons."

Be precise if asked why this is 200 rather than 403: on a rendered page a denied
`editid` is handled as a safe access-denied *notice* — the page still exists and
the user is entitled to see their own hub, they simply do not get that record.
The JSON endpoint, which has no page to render, returns a true **403** (shown
next). Verified: the page contains the error message and zero occurrences of
learner1's request title.

Then show it at the API level, which is more convincing:

```js
// DevTools console as learner2 - type "allow pasting" first if prompted
await fetch('/local/securecoursehub/ajax.php', {
  method: 'POST', credentials: 'same-origin',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({action:'update_own', id:1, title:'hijacked',
                        description:'hijacked', sesskey: M.cfg.sesskey})
}).then(r => r.json().then(j => console.log(r.status, j)));
```

Expect `403` and *"You cannot access or modify a request that belongs to another
user."* Note this request carried a **valid** sesskey — it fails on ownership,
which shows the two controls are independent.

### 5. Log in as a teacher and perform an authorized management operation

As `staff1`: show the course queue, use the status filter, then change a status
and add a response. The row updates in place.

> "The teacher panel is only rendered when `has_capability` returns true — but
> that is a usability decision. The server re-checks the capability on every
> request regardless, which is what item 4 just proved."

### 6. Show the capability and ownership checks in the code

Three places, in this order:

| File | What to show |
|---|---|
| `db/access.php` | The three capabilities, all at `CONTEXT_COURSE` |
| `ajax.php` ~line 121 | The record-scoped branch — **the most important code in the project** |
| `request_service.php` ~line 313 | `require_owner()` and `require_open()` |

For `ajax.php`, say this:

> "For any action targeting a record we load the record first, then derive the
> course and the context from `$record->courseid` — never from the course id the
> browser sent. If we trusted the posted course id, a teacher could pass a course
> they genuinely teach while targeting a record in a course they do not, and the
> capability check would wrongly succeed."

### 7. Show the JSON/fetch interaction in developer tools

DevTools → Network, then change a status. Select the `ajax.php` request.

- **Payload**: `{action, id, status, response}` — plus the sesskey, so
  either collapse that field or use the forged-token request from item 8.
- **Response**: `{success: true, message, request: {...}}`
- Point out the row updating with no page reload.

> "The session cookie is attached automatically because this is same-origin. No
> authentication secret is copied into JavaScript — the sesskey comes from
> `M.cfg.sesskey`, which Moodle already places in the page."

### 8. Demonstrate CSRF protection safely

```bash
./tests/demo_csrf.sh
```

Two authenticated attempts — one with the sesskey omitted, one forged — both
`403`, database unchanged. The script never reads or prints the real token.

> "The session cookie was valid in both attempts, so the server knew exactly who
> I was. Authentication alone was not enough. A cross-site attacker can make your
> browser send a request, and the cookie rides along automatically — but they
> cannot read our page, so they cannot learn the sesskey. That is what separates
> a request carrying your cookie from a request you intended to make."

Point at `ajax.php` line 104 (`confirm_sesskey`, before any read or write) and
`index.php` line 60 (`require_sesskey` on every form POST).

### 9. Show input validation and safe XSS handling

Create a request titled `<script>alert(1)</script>`. No alert fires; the title
displays as literal text.

**Say this accurately — it is where the stale template is wrong:**

> "We do **not** strip the tags. The value is stored in the database exactly as
> typed, and escaped at every output site — `s()` in PHP, `textContent` in
> JavaScript. Stripping at input destroys legitimate user text and only defends
> the contexts you thought of at write time. Escaping at output is correct in
> every context, and it makes the property demonstrable: you can see the string
> is really stored, and still see it render harmlessly."

Then show validation rejecting bad input — a 501-character response, or an
invalid status via the console:

```js
await fetch('/local/securecoursehub/ajax.php', {
  method:'POST', credentials:'same-origin',
  headers:{'Content-Type':'application/json'},
  body: JSON.stringify({action:'update_status', id:1, status:'deleted',
                        sesskey: M.cfg.sesskey})
}).then(r => r.json().then(j => console.log(r.status, j)));
```

`400` — *"The selected status is invalid."* The select menu only offers three
values, and the server independently enforces the same whitelist.

### 10. Show database safety

Open `request_service.php`.

> "Every call is a Moodle `$DB` method with placeholder parameters —
> `get_records`, `insert_record`, `update_record`, `delete_records`. No user
> input is ever concatenated into SQL. The one status value that reaches a WHERE
> clause is whitelisted to three known strings first."

### 11. Explain authN vs authZ vs role/capability vs ownership

Expect to be asked this. All four are distinct and all four are necessary:

| Concept | Question it answers | Where |
|---|---|---|
| **Authentication** | Who is this? | `require_login()` — Moodle's session |
| **Authorization** | May this kind of user do this at all? | `require_capability()` |
| **Role / capability** | What does this role grant *in this course*? | `db/access.php`, evaluated per course context |
| **Ownership** | Is this specific record *theirs*? | `require_owner()` in the service |

> "A capability check cannot answer the ownership question. `learner2` genuinely
> holds `viewown` in this course — that is correct and necessary, or they could
> not use the plugin at all. It just says nothing about whether record #1 belongs
> to them. That is why both checks exist, and item 4 is what it looks like when
> only the second one stops you."

### 12. Explain one privacy decision and one known limitation

**Privacy decision** — pick one and be specific:

> "A student's `list_own` response contains no user id and no name at all. The
> JSON row builder only includes owner identity when the caller has already been
> verified to hold the management capability. A student literally cannot learn
> who else has filed a request."

Alternative: constant error messages. *"The requested record was not found"* is
returned whether or not the record exists, so the endpoint cannot be used to
enumerate which records exist or who owns them.

**Known limitation** — pick one and own it:

> "A teacher's response replaces the previous note. There is no history and no
> audit trail of who changed a status and when. For a real deployment that would
> need an audit table, because 'who resolved this and when' is exactly what you
> want during a dispute."

Others available: no site-wide `manageall`; deletion is permanent with no soft
delete; the queue loads the full course list with no paging; no retention job.

---

## Questions to expect

| Question | Answer |
|---|---|
| "Why not just hide the teacher buttons from students?" | Hiding is usability. Item 4 sends the request with no button involved and it still fails. Client restrictions are duplicated server-side. |
| "Why do you need a sesskey when you already have a session cookie?" | The cookie is attached automatically by the browser, so it proves identity but not intent. The sesskey cannot be read cross-origin. |
| "Where does the course context come from?" | For record actions, from `$record->courseid` — never the posted value. Explain the attack that prevents. |
| "What stops SQL injection?" | The `$DB` API with placeholders; no concatenation anywhere; status whitelisted before use. |
| "Is `PARAM_TEXT` used on the title?" | No — deliberately. Store raw, escape at output. Give the reasoning in item 9. **Know this one; it is the most likely challenge.** |
| "What happens if the session expires mid-request?" | 401 JSON; `dashboard.js` shows the session message and stops rather than retrying or discarding typed text. |
| "Can a student edit a resolved request?" | No — `require_open()`. Once staff engage with a request the student can no longer alter or delete it, which preserves the record. |
| "Why is developer debugging on but nothing leaks?" | `debug = DEVELOPER` with `debugdisplay = 0`. Diagnostics go to the server log, never into a response. |

---

## Do not

- Show a real sesskey or session cookie, in the browser or a terminal.
- Type a password where the screen is being recorded or projected.
- Run `docker compose down -v` to "reset" mid-demo — it destroys the data and
  costs 5+ minutes of re-install. Use `./setup.sh` to re-seed instead.
- Claim anything you have not personally run. If a check was not executed, say
  so — every member is expected to be able to justify what is claimed.
