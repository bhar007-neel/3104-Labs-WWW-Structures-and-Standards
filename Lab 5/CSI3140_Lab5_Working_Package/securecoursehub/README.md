# Secure Course Hub

A local Moodle plugin for CSI 3140 Laboratory 5. Students create and manage their own open course requests. Editing teachers manage the authorized course queue, update statuses, and add a response through a JSON/fetch interaction.

## Environment record

Replace these values before submission:

- Moodle version: `[REPLACE WITH Site administration > Notifications version]`
- PHP version: `[REPLACE WITH php -v OR Site administration > Server > Environment]`
- Database: `[MariaDB/MySQL version]`
- Web server / local setup: `[Docker, XAMPP, WAMP, MAMP, etc.]`
- Operating system: `[Windows/macOS/Linux]`

The current `version.php` requires Moodle `2024100700` (Moodle 4.5). Change `$plugin->requires` only when your installed version requires a different value.

## Install

1. Stop Moodle before copying files if your local setup locks mounted folders.
2. Copy the folder `securecoursehub` to:
   ```text
   <moodle-root>/local/securecoursehub
   ```
3. Start Moodle.
4. Sign in as the site administrator.
5. Open **Site administration > Notifications**.
6. Confirm the plugin name and complete the database upgrade.
7. Purge caches from **Site administration > Development > Purge caches**.

## Required test accounts and course

Use synthetic names only.

1. Keep/create one site administrator account.
2. Create one teacher account.
3. Create Student A.
4. Create Student B.
5. Create one demonstration course.
6. Enrol the teacher as **Teacher** and both students as **Student**.
7. Open:
   ```text
   http://localhost/.../local/securecoursehub/index.php?courseid=COURSE_ID
   ```
   The plugin also adds a course navigation link when the signed-in role has permission.

## Capabilities

| Capability | Student | Editing teacher | Manager |
|---|---:|---:|---:|
| `local/securecoursehub:viewown` | Allow | Allow | Allow |
| `local/securecoursehub:createrequest` | Allow | Allow | Allow |
| `local/securecoursehub:managecourserequests` | No | Allow | Allow |

If permissions do not appear after upgrading, verify them under **Course > More > Permissions** and purge caches.

## Functional behaviour

### Student

- Must be signed in and enrolled.
- Creates a request using the authenticated Moodle `$USER->id`.
- Sees only requests whose `userid` matches the signed-in user.
- Edits or deletes only their own request while status is `open`.
- Cannot manage the course queue or change another student's record by editing an ID.

### Teacher

- Must have `local/securecoursehub:managecourserequests` in the course context.
- Views only requests connected to that authorized course.
- Filters by `open`, `inprogress`, or `resolved`.
- Updates status and a response of at most 500 characters through `fetch()` and JSON.
- The page updates without a full reload.

## Security controls

- `require_login($course)` protects the page and AJAX endpoint.
- Moodle capabilities are checked in the course context.
- Student ownership and record `courseid` are checked server-side.
- Every state-changing operation validates `sesskey`.
- `PARAM_*` type handling, length checks, and allowed-status validation run server-side.
- Moodle `$DB` methods are used, with no SQL string concatenation.
- Output uses Moodle formatting/escaping helpers and JavaScript uses `textContent`.
- AJAX errors expose safe messages instead of database errors, paths, configuration, or stack traces.
- No passwords, cookies, session IDs, sesskeys, database credentials, `config.php`, or `moodledata` belong in the submission.

## Developer debugging

Enable only on the local development site:

1. **Site administration > Development > Debugging**
2. Debug messages: **DEVELOPER**
3. Display debug messages: **Yes**

Turn off or hide sensitive diagnostics before taking submission screenshots.

## Clean reinstall after schema changes

During development, a changed `db/install.xml` does not automatically rebuild an already-installed table. Use one method:

1. Uninstall the plugin from **Site administration > Plugins > Plugins overview**, delete the old plugin table if Moodle does not remove it, then reinstall; or
2. Add a proper `db/upgrade.php` step and increment `$plugin->version`.

For this lab, the cleanest early-development method is usually uninstall/reinstall. Back up first, because humans apparently enjoy learning this lesson only after deleting their database.

## Mandatory testing

Use the included `TEST_RESULTS_TEMPLATE.md`. Record role, action, expected result, actual result, and Pass/Fail. Screenshots must hide passwords, cookies, sesskeys, database credentials, and private information.

## Known limitations

- The plugin uses a simple request workflow and has no file attachments.
- Teacher status transitions are allowed among all three approved statuses.
- Record retention is manual; the team should document a test-data deletion policy in the report.
- This is a local laboratory implementation and must not be publicly exposed.
