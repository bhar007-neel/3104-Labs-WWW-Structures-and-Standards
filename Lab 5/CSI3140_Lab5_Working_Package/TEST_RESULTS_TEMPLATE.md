# Mandatory Acceptance Test Results

Replace every `TBD` before submission.

| # | Account role | Request/action | Expected result | Actual result | Pass/Fail | Evidence |
|---:|---|---|---|---|---|---|
| 1 | Unauthenticated | Open plugin URL | Moodle requires login or denies access before plugin data appears. | TBD | TBD | Screenshot |
| 2 | Student A | Create valid request | Record uses Student A's authenticated ID, correct course, `open` status, and timestamps. | TBD | TBD | Screenshot/DB |
| 3 | Student A | Submit empty title or description | Server rejects input with a safe validation message; no record is added. | TBD | TBD | Screenshot |
| 4 | Student A | View requests | Only Student A's records appear. | TBD | TBD | Screenshot |
| 5 | Student A | Change `editid` or submitted `id` to Student B's request | Server denies operation and does not expose or modify Student B's data. | TBD | TBD | Screenshot |
| 6 | Student A | POST directly to `ajax.php` for teacher update | Server returns forbidden/access denied. | TBD | TBD | Network screenshot |
| 7 | Teacher | Open authorized demonstration course | All requests for that course appear. | TBD | TBD | Screenshot |
| 8 | Teacher | Open another course without capability | Access is denied. | TBD | TBD | Screenshot |
| 9 | Student/Teacher | Remove or change `sesskey` on a state change | Operation is rejected and database data remains unchanged. | TBD | TBD | Network screenshot |
| 10 | Student A | Submit `<script>alert(1)</script>` as title/description | Text is cleaned/displayed safely and does not execute. | TBD | TBD | Screenshot |
| 11 | Student/Teacher | Request a missing record ID | Safe not-found result; no unrelated data is exposed. | TBD | TBD | Screenshot |
| 12 | Teacher | Log out, then use an already-open page to send AJAX update | Client shows session/authentication error and update does not continue. | TBD | TBD | Network/UI screenshot |
| 13 | All roles | Review browser console and Moodle debugging | No unexplained console, PHP, or database errors remain. | TBD | TBD | Screenshot |
