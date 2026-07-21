# Screenshots to capture before submitting

Save each image in this folder using the given filename. These are the only
deliverables the automated tooling cannot produce.

**Rule for every screenshot: no session cookie, no sesskey value, no password,
and no database credential may be visible.** In the browser Network tab, expand
the JSON body but keep the Cookie/Set-Cookie headers collapsed, and blur or crop
the `sesskey` field if it appears in a request payload.

| # | Filename | What it must show |
|---|---|---|
| 1 | `01-site-running.png` | The Moodle home page at `http://127.0.0.1:8080` loading without errors, plus **Site administration → Notifications** (or the environment page) showing the exact Moodle version **4.5.4 (Build: 20250414)** and PHP **8.1.32**. |
| 2 | `02-participants-roles.png` | The demo course **Participants** page listing `staff1` as Teacher and `learner1` / `learner2` as Students with those roles visible. |
| 3 | `03-plugin-page-learner1.png` | The plugin page as **learner1**, showing the create form and only learner1's own requests. |
| 4 | `04-denied-learner2.png` | The plugin page as **learner2** showing no requests, plus the JSON error response from a denied attempt on learner1's record id (HTTP 403, "You cannot access or modify a request that belongs to another user."). |
| 5 | `05-teacher-panel-status-change.png` | The plugin page as **staff1** with the course request queue, the status filter, and a status visibly changed (for example to *In progress*) **without a page reload**, with the success message shown. |
| 6 | `06-network-json.png` | Browser DevTools → Network, showing the `ajax.php` POST: the JSON request payload (`action`, `id`, `status`) and the JSON response (`success: true`, updated `status`/`statuslabel`). Keep cookie headers collapsed and hide the sesskey value. |
| 7 | `07-xss-rendered-as-text.png` | A request whose title is `<script>alert(1)</script>` displayed in the table **as literal text**, with no alert dialog and no script execution. |
| 8 | `08-sesskey-rejected.png` | A state-changing request sent without a valid sesskey being rejected (HTTP 403 and the message "The security token was missing or invalid…"), together with evidence that the record did not change. Do **not** reveal a real sesskey value in the shot. |

Optional but useful for the demonstration:

| # | Filename | What it shows |
|---|---|---|
| 9 | `09-session-expired.png` | Log out in a second tab, then press **Update**: the page shows the session-expired message and stops. |
| 10 | `10-console-clean.png` | The browser console with no errors during normal use. |
