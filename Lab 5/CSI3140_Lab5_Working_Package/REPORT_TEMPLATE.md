# CSI 3140 Laboratory 5 Report
## Secure Course Hub

**Team members:** `[NAMES]`  
**Student numbers:** `[NUMBERS]`  
**Course:** CSI 3140 - WWW Structures, Techniques and Standards  
**Laboratory:** 5  
**Date:** `[DATE]`

## 1. Application overview and user stories

Secure Course Hub is a local Moodle plugin that lets enrolled students create course-related help requests. A student can view, edit, and delete only their own open requests. An editing teacher with the course-management capability can view the authorized course queue, filter requests, change status, and add a response.

Implemented user stories:

- As an enrolled student, I can create a request for my course.
- As a student, I can see only my own requests.
- As a student, I can edit or delete only my own request while it is open.
- As a teacher, I can manage requests only in a course where Moodle grants the capability.
- As a teacher, I can update status and response dynamically through JSON/fetch.

## 2. Local environment

| Component | Exact version/configuration |
|---|---|
| Operating system | `[REPLACE]` |
| Local platform | `[Docker/XAMPP/WAMP/MAMP]` |
| Moodle | `[REPLACE]` |
| PHP | `[REPLACE]` |
| MariaDB/MySQL | `[REPLACE]` |
| Browser | `[REPLACE]` |

Explain repeatable startup and installation steps. Add a screenshot of the running Moodle home/administration page.

## 3. Architecture

Insert `architecture_diagram.svg` or a redrawn equivalent.

Flow:

1. The browser loads the Moodle plugin page.
2. Moodle authenticates the session and supplies `$USER`.
3. The plugin checks course-context capabilities and record ownership.
4. `index.php` and `ajax.php` call `request_service.php`.
5. The service validates business rules and uses Moodle `$DB` methods.
6. MariaDB/MySQL stores the request records.
7. Teacher updates return JSON and JavaScript changes the visible page without a full reload.

## 4. Plugin structure

| File/folder | Purpose |
|---|---|
| `version.php` | Plugin component, version, Moodle requirement, maturity, release. |
| `db/access.php` | Course-context capability definitions and default role archetypes. |
| `db/install.xml` | XMLDB schema for the custom request table. |
| `lang/en/local_securecoursehub.php` | Interface, capability, error, and privacy strings. |
| `classes/local/request_service.php` | Validation, ownership, course association, state rules, and CRUD. |
| `index.php` | Login, context, capabilities, student forms, teacher queue, safe rendering. |
| `ajax.php` | Authenticated JSON endpoint with sesskey and teacher capability checks. |
| `amd/src/dashboard.js` | Source for fetch-based teacher update. |
| `amd/build/dashboard.min.js` | Browser-loaded AMD build. |
| `lib.php` | Adds the plugin link to authorized course navigation. |
| `styles.css` | Local presentation rules. |
| `README.md` | Installation, permissions, execution, security, testing, and limitations. |

## 5. Data model

Custom table: `{local_securecoursehub}`

| Field | Purpose |
|---|---|
| `id` | Primary key. |
| `courseid` | Course that owns the request. |
| `userid` | Authenticated creator. |
| `title` | Required request title, maximum 255 characters. |
| `description` | Required request details, maximum 2000 characters. |
| `status` | `open`, `inprogress`, or `resolved`. |
| `response` | Optional teacher response, maximum 500 characters. |
| `timecreated` | Creation timestamp. |
| `timemodified` | Last-change timestamp. |

The schema indexes course, user, and course/status searches.

## 6. Authentication and session workflow

Moodle owns authentication. Each protected entry point loads `config.php` and calls `require_login($course)`. The application uses `$USER->id`; it does not accept a browser-supplied creator ID. The browser's session cookie identifies the session, while the authenticated session state is stored and controlled by Moodle on the server. Logging out or expiring the session removes the authenticated identity needed by the endpoint.

No complete session cookie or sesskey is shown in screenshots or submitted files.

## 7. Authorization, capabilities, and contexts

| Operation | Student | Editing teacher | Manager | Enforcement |
|---|---:|---:|---:|---|
| Open plugin/view own requests | Yes | Yes | Yes | `viewown`, course context |
| Create request | Yes | Yes | Yes | `createrequest`, enrolment/course access |
| Edit/delete own open request | Yes | N/A | N/A | `viewown` + owner + course + `open` |
| View course queue | No | Yes | Yes | `managecourserequests`, course context |
| Update status/response | No | Yes | Yes | `managecourserequests` + matching course |

A role label alone is not trusted. Moodle resolves the capability in the exact course context. The interface hides unauthorized actions, but the PHP endpoint repeats all checks.

## 8. Ownership and resource-level authorization

For student updates and deletes, the service loads the record and verifies:

1. The record exists.
2. `record.courseid` equals the requested course.
3. `record.userid` equals `$USER->id`.
4. The record status is `open`.

This prevents Student A from changing Student B's record by editing a URL, hidden field, JavaScript value, or request body.

Teacher updates require the management capability in the course context and verify that the record belongs to that same course.

## 9. Server-side CRUD and data integrity

All CRUD operations use Moodle's Database API: `insert_record`, `get_record(s)`, `update_record`, and `delete_records`. Input is not concatenated into SQL. Required fields, lengths, allowed statuses, ownership, course association, and state rules are validated before a write. A failed validation leaves the database unchanged.

## 10. JSON/fetch interaction

Teacher operation: update status and response.

Example request, with the actual sesskey removed:

```json
{
  "action": "update_status",
  "id": 12,
  "courseid": 3,
  "status": "inprogress",
  "response": "I am reviewing this request.",
  "sesskey": "[REDACTED]"
}
```

Example success response:

```json
{
  "success": true,
  "message": "The request status and response were updated.",
  "id": 12,
  "status": "inprogress",
  "statuslabel": "In progress",
  "response": "I am reviewing this request."
}
```

The JavaScript uses same-origin credentials, parses JSON, checks both HTTP status and `success`, updates trusted elements with `textContent`, and displays a visible success or error message.

Add a Network-panel screenshot with cookie and sesskey values hidden.

## 11. Security controls and risk mapping

| Implemented control | Code/evidence | Risk reduced |
|---|---|---|
| `require_login()` and Moodle session | `index.php`, `ajax.php` | Identification/authentication failure |
| Course-context capabilities | `db/access.php`, PHP checks | Broken access control |
| Owner/course/status checks | `request_service.php` | Insecure direct object reference / broken access control |
| Sesskey validation | POST handlers and AJAX endpoint | CSRF |
| Server input validation | `PARAM_*`, lengths, allowed statuses | Injection and invalid business data |
| `$DB` API | Service CRUD | SQL injection |
| Escaping and `textContent` | Rendered table and JS | XSS |
| Safe JSON errors | `ajax.php` catches | Information disclosure / security misconfiguration |
| Secret exclusion | README and ZIP review | Credential/session exposure |

## 12. Privacy analysis

Data collected: course ID, creator ID, title, description, status, teacher response, and timestamps. No separate password, email copy, session identifier, or unnecessary profile field is stored.

Access:

- The creator sees their own records.
- An authorized editing teacher/manager sees records for the permitted course.
- Guests and unauthorized users see no plugin records.

Retention assumption: `[Example: synthetic lab records are deleted within 30 days after final grading.]`

Data minimization decision: the table stores `userid` rather than copying the user's name or email. Current names are retrieved from Moodle only when an authorized teacher views the queue.

## 13. Test results

Paste the completed `TEST_RESULTS_TEMPLATE.md` table and selected evidence. Include successful and denied cases.

## 14. Completed checklist

Paste the completed `SUBMISSION_CHECKLIST.md` items or reproduce the official lab checklist with check marks.

## 15. Problems, solutions, and limitations

Example reflection topics to replace with the team's real experience:

- Capability changes required a cache purge before Moodle displayed updated permissions.
- XMLDB schema changes required a version increment and clean reinstall/upgrade.
- AJAX session expiry initially returned a non-JSON response; the endpoint was made AJAX-aware and the client added safe parse handling.
- The final plugin has no attachments, notifications, automated retention job, or advanced transition policy.

## 16. References

- Moodle Developer Documentation: Local plugins.
- Moodle Developer Documentation: Access API, roles, capabilities, and contexts.
- Moodle Developer Documentation: Database API and XMLDB.
- Moodle Security Guidelines: CSRF, XSS, input validation, output escaping.
- Moodle Developer Documentation: JavaScript modules and AJAX.
- CSI 3140 Laboratory 5 handout.
- List and cite any additional tutorials, templates, code, icons, or AI assistance according to course rules.
