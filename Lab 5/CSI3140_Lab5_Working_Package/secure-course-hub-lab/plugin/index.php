<?php
// Secure Course Hub main page.
//
// Security chain, in order, before anything is read or rendered:
//   config.php -> required_param(courseid) -> get_course -> require_login($course)
//   -> context_course::instance -> require_capability(viewown) -> $PAGE setup -> render.
//
// All business logic lives in request_service. This file handles the request
// lifecycle, authorisation and safe rendering only.

require_once(__DIR__ . '/../../config.php');

use local_securecoursehub\local\request_service;

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/securecoursehub:viewown', $context);

// has_capability selects which interface to draw. Every operation is checked
// again on the server, so hiding a control is a usability choice, never a
// security control.
$cancreate = has_capability('local/securecoursehub:createrequest', $context);
$canmanage = has_capability('local/securecoursehub:managecourserequests', $context);

$pageurl = new moodle_url('/local/securecoursehub/index.php', ['courseid' => $courseid]);
$ajaxurl = new moodle_url('/local/securecoursehub/ajax.php');

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_securecoursehub'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css(new moodle_url('/local/securecoursehub/styles.css'));
// Client strings come from the language file; no user-facing text is hard-coded
// in JavaScript, and no secret is ever passed into the page.
$PAGE->requires->js_call_amd('local_securecoursehub/dashboard', 'init', [[
    'endpoint' => $ajaxurl->out(false),
    'courseid' => $courseid,
    'canmanage' => $canmanage,
    'strings' => [
        'network' => get_string('jsnetworkerror', 'local_securecoursehub'),
        'invalidresponse' => get_string('jsinvalidresponse', 'local_securecoursehub'),
        'generic' => get_string('jsgenericerror', 'local_securecoursehub'),
        'edit' => get_string('edit', 'local_securecoursehub'),
        'deletelabel' => get_string('delete', 'local_securecoursehub'),
        'confirmdelete' => get_string('confirmdelete', 'local_securecoursehub'),
    ],
]]);

$statusoptions = request_service::status_options();

// ---------------------------------------------------------------------------
// Non-JavaScript fallback: form posts. State changes only ever happen on POST,
// and every POST must carry a valid sesskey.
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_sesskey();

    $action = required_param('action', PARAM_ALPHA);

    try {
        switch ($action) {
            case 'create':
                require_capability('local/securecoursehub:createrequest', $context);
                request_service::create(
                    $courseid,
                    (int) $USER->id,
                    required_param('title', PARAM_RAW_TRIMMED),
                    required_param('description', PARAM_RAW_TRIMMED)
                );
                redirect($pageurl, get_string('requestcreated', 'local_securecoursehub'), null,
                    \core\output\notification::NOTIFY_SUCCESS);
                break;

            case 'edit':
                // Ownership and the open-state rule are enforced in the service.
                request_service::update_own(
                    required_param('id', PARAM_INT),
                    (int) $USER->id,
                    required_param('title', PARAM_RAW_TRIMMED),
                    required_param('description', PARAM_RAW_TRIMMED)
                );
                redirect($pageurl, get_string('requestupdated', 'local_securecoursehub'), null,
                    \core\output\notification::NOTIFY_SUCCESS);
                break;

            case 'delete':
                $id = required_param('id', PARAM_INT);
                // Documented delete rule: owner deletes an open request, or a
                // user with managecourserequests deletes any request in the course.
                if ($canmanage) {
                    request_service::delete_managed($id);
                } else {
                    request_service::delete_own($id, (int) $USER->id);
                }
                redirect($pageurl, get_string('requestdeleted', 'local_securecoursehub'), null,
                    \core\output\notification::NOTIFY_SUCCESS);
                break;

            default:
                throw new moodle_exception('invalidaction', 'local_securecoursehub');
        }
    } catch (dml_missing_record_exception $exception) {
        redirect($pageurl, get_string('notfound', 'local_securecoursehub'), null,
            \core\output\notification::NOTIFY_ERROR);
    } catch (moodle_exception $exception) {
        // getMessage() here is one of this plugin's own language strings.
        redirect($pageurl, $exception->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// ---------------------------------------------------------------------------
// Read phase.
// ---------------------------------------------------------------------------
$editid = optional_param('editid', 0, PARAM_INT);
$editrecord = null;
if ($editid > 0) {
    try {
        $editrecord = request_service::get_own_editable($editid, (int) $USER->id);
        if ((int) $editrecord->courseid !== $courseid) {
            $editrecord = null;
            throw new moodle_exception('notfound', 'local_securecoursehub');
        }
    } catch (dml_missing_record_exception $exception) {
        \core\notification::error(get_string('notfound', 'local_securecoursehub'));
    } catch (moodle_exception $exception) {
        \core\notification::error($exception->getMessage());
    }
}

$statusfilter = optional_param('status', '', PARAM_ALPHA);
if ($statusfilter !== '' && !in_array($statusfilter, request_service::statuses(), true)) {
    $statusfilter = '';
}

$ownrecords = request_service::list_own($courseid, (int) $USER->id);
$courserecords = $canmanage ? request_service::list_course($courseid, $statusfilter) : [];

// ---------------------------------------------------------------------------
// Render. Every untrusted value goes through s(), format_string() or an
// html_writer attribute, all of which escape.
// ---------------------------------------------------------------------------
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_securecoursehub'));

echo html_writer::div(
    get_string('currentuser', 'local_securecoursehub', fullname($USER)),
    'securecoursehub-current-user'
);

// Live region used by the fetch() paths for success and error feedback.
echo html_writer::div('', 'securecoursehub-message', [
    'id' => 'securecoursehub-message',
    'role' => 'status',
    'aria-live' => 'polite',
]);

if ($cancreate) {
    $heading = $editrecord
        ? get_string('editrequestheading', 'local_securecoursehub')
        : get_string('createrequestheading', 'local_securecoursehub');
    echo $OUTPUT->heading($heading, 3);

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'securecoursehub-form',
        'id' => 'securecoursehub-request-form',
        'data-mode' => $editrecord ? 'edit' : 'create',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => $editrecord ? 'edit' : 'create',
    ]);
    if ($editrecord) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $editrecord->id]);
    }

    echo html_writer::label(get_string('title', 'local_securecoursehub'), 'securecoursehub-title');
    echo html_writer::empty_tag('input', [
        'id' => 'securecoursehub-title',
        'name' => 'title',
        'type' => 'text',
        'required' => 'required',
        'maxlength' => request_service::MAX_TITLE_LENGTH,
        'value' => $editrecord ? $editrecord->title : '',
        'class' => 'form-control',
    ]);

    echo html_writer::label(get_string('description', 'local_securecoursehub'), 'securecoursehub-description');
    echo html_writer::tag('textarea', $editrecord ? s($editrecord->description) : '', [
        'id' => 'securecoursehub-description',
        'name' => 'description',
        'required' => 'required',
        'maxlength' => request_service::MAX_DESCRIPTION_LENGTH,
        'rows' => 5,
        'class' => 'form-control',
    ]);

    echo html_writer::start_div('securecoursehub-form-actions');
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => $editrecord
            ? get_string('savechanges', 'local_securecoursehub')
            : get_string('create', 'local_securecoursehub'),
        'class' => 'btn btn-primary',
    ]);
    if ($editrecord) {
        echo html_writer::link($pageurl, get_string('cancel', 'local_securecoursehub'),
            ['class' => 'btn btn-secondary']);
    }
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
}

// --- The signed-in user's own requests. ------------------------------------
echo $OUTPUT->heading(get_string('myrequests', 'local_securecoursehub'), 3);

if (!$ownrecords) {
    echo html_writer::div(
        get_string('norequests', 'local_securecoursehub'),
        'alert alert-info',
        ['id' => 'securecoursehub-own-empty']
    );
} else {
    echo html_writer::div('', '', ['id' => 'securecoursehub-own-empty', 'hidden' => 'hidden']);
}

$owntable = new html_table();
$owntable->id = 'securecoursehub-own-table';
$owntable->attributes['class'] = 'generaltable securecoursehub-table';
$owntable->head = [
    get_string('title', 'local_securecoursehub'),
    get_string('description', 'local_securecoursehub'),
    get_string('status', 'local_securecoursehub'),
    get_string('response', 'local_securecoursehub'),
    get_string('modified', 'local_securecoursehub'),
    get_string('actions', 'local_securecoursehub'),
];

foreach ($ownrecords as $record) {
    $actions = '';

    // Students may only edit or delete their own request while it is open.
    if ($record->status === request_service::STATUS_OPEN) {
        $editurl = new moodle_url('/local/securecoursehub/index.php', [
            'courseid' => $courseid,
            'editid' => $record->id,
        ]);
        $actions .= html_writer::link($editurl, get_string('edit', 'local_securecoursehub'),
            ['class' => 'btn btn-secondary btn-sm']);

        $actions .= html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'securecoursehub-inline-form',
        ]);
        $actions .= html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
        $actions .= html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'action', 'value' => 'delete']);
        $actions .= html_writer::empty_tag('input',
            ['type' => 'hidden', 'name' => 'id', 'value' => $record->id]);
        $actions .= html_writer::tag('button', get_string('delete', 'local_securecoursehub'), [
            'type' => 'submit',
            'class' => 'btn btn-danger btn-sm securecoursehub-delete-button',
            'data-confirm' => get_string('confirmdelete', 'local_securecoursehub'),
        ]);
        $actions .= html_writer::end_tag('form');
    }

    $row = new html_table_row([
        s($record->title),
        nl2br(s($record->description)),
        s($statusoptions[$record->status] ?? $record->status),
        $record->response ? nl2br(s($record->response)) : '',
        userdate($record->timemodified),
        $actions,
    ]);
    $row->attributes['data-request-id'] = $record->id;
    $owntable->data[] = $row;
}

echo html_writer::table($owntable);

// --- Teacher panel. Rendered only with the management capability, and every
// --- action it offers is re-checked server side in ajax.php. ----------------
if ($canmanage) {
    echo $OUTPUT->heading(get_string('courserequests', 'local_securecoursehub'), 3);

    echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => $pageurl->out(false),
        'class' => 'securecoursehub-filter',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
    echo html_writer::label(get_string('filterbystatus', 'local_securecoursehub'), 'securecoursehub-filter-status',
        true, ['class' => 'mr-2']);
    echo html_writer::select(
        ['' => get_string('allstatuses', 'local_securecoursehub')] + $statusoptions,
        'status',
        $statusfilter,
        false,
        ['id' => 'securecoursehub-filter-status', 'class' => 'custom-select']
    );
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('filter', 'local_securecoursehub'),
        'class' => 'btn btn-secondary',
    ]);
    echo html_writer::end_tag('form');

    if (!$courserecords) {
        echo $OUTPUT->notification(get_string('norequests', 'local_securecoursehub'),
            \core\output\notification::NOTIFY_INFO);
    } else {
        $queue = new html_table();
        $queue->id = 'securecoursehub-course-table';
        $queue->attributes['class'] = 'generaltable securecoursehub-table';
        $queue->head = [
            get_string('title', 'local_securecoursehub'),
            get_string('student', 'local_securecoursehub'),
            get_string('description', 'local_securecoursehub'),
            get_string('status', 'local_securecoursehub'),
            get_string('response', 'local_securecoursehub'),
            get_string('modified', 'local_securecoursehub'),
            get_string('actions', 'local_securecoursehub'),
        ];

        foreach ($courserecords as $record) {
            $owner = \core_user::get_user((int) $record->userid, 'id, firstname, lastname');
            $ownername = $owner ? fullname($owner) : get_string('unknownuser', 'local_securecoursehub');

            $statusselect = html_writer::select(
                $statusoptions,
                'status_' . $record->id,
                $record->status,
                false,
                [
                    'id' => 'securecoursehub-status-' . $record->id,
                    'class' => 'custom-select securecoursehub-status-select',
                ]
            );

            // Current status as text. dashboard.js rewrites this node with
            // textContent after a successful fetch(), with no page reload.
            $statuslabel = html_writer::div(
                s($statusoptions[$record->status] ?? $record->status),
                'securecoursehub-status-label badge badge-info',
                ['id' => 'securecoursehub-statuslabel-' . $record->id]
            );

            $responseinput = html_writer::tag('textarea', s((string) ($record->response ?? '')), [
                'id' => 'securecoursehub-response-' . $record->id,
                'rows' => 3,
                'maxlength' => request_service::MAX_RESPONSE_LENGTH,
                'class' => 'form-control securecoursehub-response-input',
                'aria-label' => get_string('response', 'local_securecoursehub'),
            ]);

            $button = html_writer::tag('button', get_string('update', 'local_securecoursehub'), [
                'type' => 'button',
                'class' => 'btn btn-primary securecoursehub-update-button',
                'data-request-id' => $record->id,
            ]);

            $row = new html_table_row([
                s($record->title),
                s($ownername),
                nl2br(s($record->description)),
                $statuslabel . $statusselect,
                $responseinput,
                userdate($record->timemodified),
                $button,
            ]);
            $row->attributes['data-request-id'] = $record->id;
            // Cell 3 is the rendered status label updated in place by dashboard.js.
            $queue->data[] = $row;
        }

        echo html_writer::table($queue);
    }
}

echo $OUTPUT->footer();
