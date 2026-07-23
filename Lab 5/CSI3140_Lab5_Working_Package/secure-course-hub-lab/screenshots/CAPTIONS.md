# Screenshot index — CSI 3140 Lab 5, Secure Course Hub

Figure numbers match Section 14 of `report/report.pdf`. No session cookie,
sesskey value, password or database credential is visible in any image.

| Figure | File | What it shows |
|---|---|---|
| 1 | `site_running_ss1.png` | The local Moodle 4.5.4 site running at `http://127.0.0.1:8080`, front page listing *CSI 3140 Demonstration Course*. No fatal errors. |
| 2 | `participants_roles.png` | Course **Participants**: Alex StudentA and Blair StudentB as *Student*, Sam Teacher as *Teacher*. Synthetic `@example.invalid` addresses. |
| 3 | `login_screen.png` | Moodle's own login form — the plugin adds no login of its own; `require_login()` redirects unauthenticated requests here before any data is produced. |
| 4 | `learner1_requests.png` | Plugin page as **learner1**: create form plus *My requests* listing only records this student owns. |
| 5 | `learner2_sees_no_req.png` | Same page, same course, as **learner2**: *"No requests were found."* Ownership is applied in the query. |
| 6 | `staff_course_req_queue_p1.png` | Plugin page as **staff1**: empty student view plus the *Course request queue* with its status filter, drawn only because the management capability is held. |
| 7 | `staff_course_req_queue_p2.png` | The full teacher queue: every course request with owner name, per-row status select, response field and **Update** button. |
| 8 | `staff_status_change.png` | The queue after **Update**: badge and select now read *In progress*, changed in place with no page reload. |
| 9 | `ajax_php_JSON_payload.png` | DevTools → Network → **Payload**: the JSON request body sent to `ajax.php`. The `sesskey` value is redacted. |
| 10 | `ajax_php_response_headers.png` | DevTools → Network → **Headers** for the same call: `POST …/ajax.php`, **200 OK**, `Content-Type: application/json; charset=utf-8`, `Cache-Control: no-store`. |
| 11 | `learner1_XSS_probe.png` | A request titled `<script>alert(1)</script>` stored verbatim and rendered as **literal text**. No dialog, no execution. |
| 12 | `csrf_forged_sesskey_rejected.png` | A console `fetch()` to `ajax.php` with a deliberately forged sesskey: **403 (Forbidden)**, "The security token was missing or invalid." Record unchanged. |
