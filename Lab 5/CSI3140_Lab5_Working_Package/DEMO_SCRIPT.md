# TA Demonstration Script

## 1. Environment

> This is our local Moodle development site. We used `[ENVIRONMENT]`, Moodle `[VERSION]`, PHP `[VERSION]`, and `[DATABASE]`. The site is not publicly exposed.

Show the Moodle home page and Site administration page.

## 2. Course and users

> We created one administrator, one teacher, Student A, and Student B using synthetic data. This Participants page shows the teacher and both students enrolled with the correct roles.

## 3. Plugin structure

> The plugin is installed at `local/securecoursehub` without changing Moodle core. `version.php` contains metadata, `db/access.php` defines capabilities, `db/install.xml` creates the request table, `request_service.php` contains business and database rules, `index.php` renders the secure interface, and `ajax.php` handles the JSON update.

## 4. Authentication

Log out and open the plugin URL.

> `require_login($course)` runs before protected data is returned, so an unauthenticated visitor must sign in. The browser has a session cookie, while the authenticated user identity and session data are maintained server-side. We never print or submit the cookie or sesskey.

## 5. Student operations

Log in as Student A.

> Moodle supplies the current user through `$USER`. The plugin does not trust a browser-submitted user ID.

Create a request, view it, edit it while open, and show the delete option.

> Student queries include the authenticated user ID. Before edit or delete, the server verifies the record's course, owner, and `open` status.

Change the URL `editid` to Student B's record.

> Hiding a button is only a usability feature. The service class repeats the ownership check server-side, so changing an ID does not grant access.

## 6. Teacher operations and JSON

Log in as the teacher. Filter the queue. Change a status and response.

Open browser Developer Tools > Network and select the `ajax.php` request.

> The browser sends JSON containing the action, record ID, course ID, approved status, response, and Moodle sesskey. The session cookie is sent automatically to the same origin. The server checks login, sesskey, course context, teacher capability, record course association, status, and response length before using the Moodle Database API. The JSON response updates the page without a full reload.

Do not expose the full cookie or sesskey while showing Developer Tools.

## 7. CSRF and validation

Use Developer Tools or an API client to send the update with a missing/invalid sesskey.

> The server rejects the state change and leaves the database unchanged. This protects against cross-site request forgery.

Submit an over-500-character response or an invalid status.

> Server-side validation rejects the value even if client-side restrictions are bypassed.

## 8. XSS and database safety

Submit `<script>alert(1)</script>` in a student field.

> The value is cleaned and safely rendered. JavaScript uses `textContent`, and PHP uses Moodle escaping/formatting helpers, so script markup does not execute.

Show `request_service.php`.

> CRUD uses Moodle `$DB` methods. User input is never concatenated into SQL.

## 9. Access-control explanation

> Authentication answers who the user is. Authorization answers what that user may do. Moodle role capabilities provide course-level permission, while ownership checks protect an individual student's record. All four are necessary.

## 10. Privacy and limitation

> We collect only course ID, creator ID, title, description, workflow status, teacher response, and timestamps. Students see only their own records; authorized teachers see their course queue. Our test-data retention rule is `[RETENTION RULE]`. A known limitation is that the plugin has no attachment workflow and uses a simple three-status process.
