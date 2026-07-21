<?php
// JSON endpoint for Secure Course Hub.
//
// Accepts POST requests with a JSON body only. Every request runs through the
// same security chain before any data is read or written:
//
//   1. Load config.php and require an authenticated Moodle session.
//   2. Decode and validate the JSON body.
//   3. Validate the sesskey (CSRF) for every action.
//   4. Establish the course context and check the required capability.
//   5. Check record ownership, using the context recomputed from the record's
//      own courseid rather than any value supplied by the browser.
//   6. Delegate the write to request_service.
//   7. Return a structured JSON success or error response.
//
// Responses never contain stack traces, SQL, file paths, configuration values
// or session identifiers.

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

use local_securecoursehub\local\request_service;

/**
 * Emit a JSON response and stop.
 *
 * @param array<string, mixed> $payload
 * @param int $statuscode HTTP status code.
 */
function local_securecoursehub_respond(array $payload, int $statuscode = 200) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        http_response_code($statuscode);
    }

    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Emit a structured error response.
 */
function local_securecoursehub_fail(string $message, int $statuscode) {
    local_securecoursehub_respond(['success' => false, 'error' => $message], $statuscode);
}

// Error codes this plugin raises deliberately, with the HTTP status each maps to.
// Only messages for these codes are ever shown to a client; anything else is
// replaced by a generic message so internal details cannot leak.
const LOCAL_SECURECOURSEHUB_SAFE_ERRORS = [
    'invalidjson' => 400,
    'invalidaction' => 400,
    'validationerror' => 400,
    'titleinvalid' => 400,
    'descriptioninvalid' => 400,
    'responseinvalid' => 400,
    'statusinvalid' => 400,
    'requestnotopen' => 400,
    'notowner' => 403,
    'notenrolled' => 403,
    'accessdenied' => 403,
    'notfound' => 404,
];

// Actions that are permitted, and the capability required for each. Ownership
// and state rules are applied in addition to the capability.
const LOCAL_SECURECOURSEHUB_ACTIONS = [
    'create' => 'local/securecoursehub:createrequest',
    'list_own' => 'local/securecoursehub:viewown',
    'list_course' => 'local/securecoursehub:managecourserequests',
    'update_own' => 'local/securecoursehub:viewown',
    'update_status' => 'local/securecoursehub:managecourserequests',
    'delete' => 'local/securecoursehub:viewown',
];

try {
    // State changes never happen on GET.
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        local_securecoursehub_fail(get_string('postonly', 'local_securecoursehub'), 405);
    }

    // Step 1: authentication. With AJAX_SCRIPT defined this throws rather than
    // redirecting, so an unauthenticated caller is rejected before any data is read.
    // Arguments: no course yet, no guest autologin, no cm, do not remember the
    // wanted URL, and never redirect (throw instead) so the caller always gets JSON.
    require_login(null, false, null, false, true);

    // Step 2: decode the JSON body.
    $rawbody = file_get_contents('php://input');
    $data = json_decode((string) $rawbody);
    if (!is_object($data) || json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('invalidjson', 'local_securecoursehub');
    }

    $action = clean_param($data->action ?? '', PARAM_ALPHAEXT);
    if (!array_key_exists($action, LOCAL_SECURECOURSEHUB_ACTIONS)) {
        throw new moodle_exception('invalidaction', 'local_securecoursehub');
    }

    // Step 3: CSRF. Checked for every action, before anything is read or written.
    $sesskey = clean_param($data->sesskey ?? '', PARAM_RAW);
    if (!is_string($sesskey) || $sesskey === '' || !confirm_sesskey($sesskey)) {
        // Deliberately generic: never echo back or compare-report the token.
        local_securecoursehub_fail(get_string('invalidsesskeymessage', 'local_securecoursehub'), 403);
    }

    $capability = LOCAL_SECURECOURSEHUB_ACTIONS[$action];
    $recordactions = ['update_own', 'update_status', 'delete'];

    if (in_array($action, $recordactions, true)) {
        // Step 4/5 for record-scoped actions: load the record first, then derive
        // the course and context from the stored record. A posted courseid is
        // never trusted for the authorisation decision.
        $id = clean_param($data->id ?? 0, PARAM_INT);
        if ($id <= 0) {
            throw new moodle_exception('validationerror', 'local_securecoursehub');
        }

        $record = request_service::get($id);
        $course = get_course((int) $record->courseid);
        require_login($course, false, null, false, true);
        $context = context_course::instance((int) $record->courseid);
    } else {
        // Course-scoped actions take the courseid from the payload, but it is
        // validated and the user must pass require_login() for that course.
        $courseid = clean_param($data->courseid ?? 0, PARAM_INT);
        if ($courseid <= 0) {
            throw new moodle_exception('validationerror', 'local_securecoursehub');
        }

        $course = get_course($courseid);
        require_login($course, false, null, false, true);
        $context = context_course::instance($courseid);
        require_capability($capability, $context);
    }

    switch ($action) {
        case 'create':
            $title = clean_param($data->title ?? '', PARAM_RAW_TRIMMED);
            $description = clean_param($data->description ?? '', PARAM_RAW_TRIMMED);

            // The owner is the authenticated user and the status is forced to
            // 'open'; any userid or status in the payload is ignored entirely.
            $record = request_service::create((int) $course->id, (int) $USER->id, $title, $description);

            local_securecoursehub_respond([
                'success' => true,
                'message' => get_string('requestcreated', 'local_securecoursehub'),
                'request' => request_service::to_json_row($record),
            ]);
            break;

        case 'list_own':
            $records = request_service::list_own((int) $course->id, (int) $USER->id);
            $rows = array_map(static function ($record) {
                return request_service::to_json_row($record);
            }, array_values($records));

            local_securecoursehub_respond(['success' => true, 'requests' => $rows]);
            break;

        case 'list_course':
            $statusfilter = clean_param($data->status ?? '', PARAM_ALPHA);
            if ($statusfilter !== '' && !in_array($statusfilter, request_service::statuses(), true)) {
                throw new moodle_exception('statusinvalid', 'local_securecoursehub');
            }

            $records = request_service::list_course((int) $course->id, $statusfilter);
            $rows = array_map(static function ($record) {
                return request_service::to_json_row($record, true);
            }, array_values($records));

            local_securecoursehub_respond(['success' => true, 'requests' => $rows]);
            break;

        case 'update_own':
            // Students edit their own open requests. viewown is the entry
            // capability; ownership and the open-state rule are enforced by the
            // service and are what actually protect the record.
            require_capability($capability, $context);

            $title = clean_param($data->title ?? '', PARAM_RAW_TRIMMED);
            $description = clean_param($data->description ?? '', PARAM_RAW_TRIMMED);
            $updated = request_service::update_own((int) $record->id, (int) $USER->id, $title, $description);

            local_securecoursehub_respond([
                'success' => true,
                'message' => get_string('requestupdated', 'local_securecoursehub'),
                'request' => request_service::to_json_row($updated),
            ]);
            break;

        case 'update_status':
            // Teacher-only, checked in the record's own course context.
            require_capability($capability, $context);

            $status = clean_param($data->status ?? '', PARAM_ALPHA);
            $response = property_exists($data, 'response')
                ? clean_param($data->response, PARAM_RAW_TRIMMED)
                : null;

            $updated = request_service::update_status((int) $record->id, $status, $response);

            local_securecoursehub_respond([
                'success' => true,
                'message' => get_string('statusupdated', 'local_securecoursehub'),
                'request' => request_service::to_json_row($updated, true),
            ]);
            break;

        case 'delete':
            // Documented rule: the owner may delete their own request while it is
            // still open; a user holding managecourserequests in the record's
            // course may delete any request in that course.
            if (has_capability('local/securecoursehub:managecourserequests', $context)) {
                $deleted = request_service::delete_managed((int) $record->id);
            } else {
                require_capability($capability, $context);
                $deleted = request_service::delete_own((int) $record->id, (int) $USER->id);
            }

            local_securecoursehub_respond([
                'success' => true,
                'message' => get_string('requestdeleted', 'local_securecoursehub'),
                'id' => (int) $deleted->id,
            ]);
            break;
    }

    throw new moodle_exception('invalidaction', 'local_securecoursehub');
} catch (required_capability_exception $exception) {
    local_securecoursehub_fail(get_string('accessdenied', 'local_securecoursehub'), 403);
} catch (require_login_exception $exception) {
    // An authenticated user who cannot reach the course is forbidden; anyone
    // else has no usable session.
    if (isloggedin() && !isguestuser()) {
        local_securecoursehub_fail(get_string('accessdenied', 'local_securecoursehub'), 403);
    }
    local_securecoursehub_fail(get_string('sessionexpired', 'local_securecoursehub'), 401);
} catch (dml_missing_record_exception $exception) {
    // Covers both a missing request and a missing course. The message is the
    // same in either case so it cannot be used to probe for existing records.
    local_securecoursehub_fail(get_string('notfound', 'local_securecoursehub'), 404);
} catch (moodle_exception $exception) {
    $code = $exception->errorcode;
    if (array_key_exists($code, LOCAL_SECURECOURSEHUB_SAFE_ERRORS)) {
        local_securecoursehub_fail($exception->getMessage(), LOCAL_SECURECOURSEHUB_SAFE_ERRORS[$code]);
    }

    // Any other Moodle exception may carry internal detail: log locally, return
    // a generic message.
    debugging('local_securecoursehub: unexpected moodle_exception ' . $code, DEBUG_DEVELOPER);
    local_securecoursehub_fail(get_string('servererror', 'local_securecoursehub'), 500);
} catch (Throwable $exception) {
    debugging('local_securecoursehub: unexpected throwable', DEBUG_DEVELOPER);
    local_securecoursehub_fail(get_string('servererror', 'local_securecoursehub'), 500);
}
