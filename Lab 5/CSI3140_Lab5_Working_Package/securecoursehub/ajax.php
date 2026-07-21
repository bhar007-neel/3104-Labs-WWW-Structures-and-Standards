<?php
// JSON endpoint for teacher updates.

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

use local_securecoursehub\local\request_service;

header('Content-Type: application/json; charset=utf-8');

/**
 * Send a JSON response and stop execution.
 *
 * @param array<string, mixed> $payload
 */
function local_securecoursehub_json_response(array $payload, int $statuscode = 200): never {
    http_response_code($statuscode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $rawbody = file_get_contents('php://input');
    $data = json_decode($rawbody ?: '');
    if (!is_object($data) || json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('invalidjson', 'local_securecoursehub');
    }

    $courseid = clean_param($data->courseid ?? 0, PARAM_INT);
    $id = clean_param($data->id ?? 0, PARAM_INT);
    $action = clean_param($data->action ?? '', PARAM_ALPHAEXT);
    $status = clean_param($data->status ?? '', PARAM_ALPHA);
    $response = clean_param($data->response ?? '', PARAM_RAW_TRIMMED);
    $requestsesskey = clean_param($data->sesskey ?? '', PARAM_ALPHANUM);

    if (!$courseid || !$id) {
        throw new moodle_exception('validationerror', 'local_securecoursehub');
    }

    $course = get_course($courseid);
    require_login($course);

    if (!confirm_sesskey($requestsesskey)) {
        throw new moodle_exception('invalidsesskey');
    }

    $context = context_course::instance($courseid);
    require_capability('local/securecoursehub:managecourserequests', $context);

    if ($action !== 'update_status') {
        throw new moodle_exception('invalidaction', 'local_securecoursehub');
    }

    $record = request_service::update_teacher_request($id, $courseid, $status, $response);
    $statusoptions = request_service::status_options();

    local_securecoursehub_json_response([
        'success' => true,
        'message' => get_string('teacherupdatecomplete', 'local_securecoursehub'),
        'id' => (int) $record->id,
        'status' => $record->status,
        'statuslabel' => $statusoptions[$record->status],
        'response' => (string) ($record->response ?? ''),
        'timemodified' => userdate($record->timemodified),
    ]);
} catch (require_login_exception $exception) {
    local_securecoursehub_json_response([
        'success' => false,
        'error' => get_string('sessionexpired', 'local_securecoursehub'),
    ], 401);
} catch (required_capability_exception $exception) {
    local_securecoursehub_json_response([
        'success' => false,
        'error' => get_string('accessdenied', 'local_securecoursehub'),
    ], 403);
} catch (dml_missing_record_exception $exception) {
    local_securecoursehub_json_response([
        'success' => false,
        'error' => get_string('notfound', 'local_securecoursehub'),
    ], 404);
} catch (moodle_exception $exception) {
    $safeerrors = [
        'invalidjson', 'validationerror', 'invalidaction', 'invalidsesskey',
        'titleinvalid', 'descriptioninvalid', 'responseinvalid', 'statusinvalid',
        'requestnotopen', 'notowner', 'courseismatch', 'notenrolled',
    ];
    $message = in_array($exception->errorcode, $safeerrors, true)
        ? $exception->getMessage()
        : get_string('servererror', 'local_securecoursehub');

    local_securecoursehub_json_response([
        'success' => false,
        'error' => $message,
    ], 400);
} catch (Throwable $exception) {
    debugging('Secure Course Hub AJAX failure: ' . $exception->getMessage(), DEBUG_DEVELOPER);
    local_securecoursehub_json_response([
        'success' => false,
        'error' => get_string('servererror', 'local_securecoursehub'),
    ], 500);
}
