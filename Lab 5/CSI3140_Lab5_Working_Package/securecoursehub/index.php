<?php
// Main page for Secure Course Hub.

require_once(__DIR__ . '/../../config.php');

use local_securecoursehub\local\request_service;

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
$canview = has_capability('local/securecoursehub:viewown', $context);
$cancreate = has_capability('local/securecoursehub:createrequest', $context);
$canmanage = has_capability('local/securecoursehub:managecourserequests', $context);

if (!$canview && !$canmanage) {
    require_capability('local/securecoursehub:viewown', $context);
}

$pageurl = new moodle_url('/local/securecoursehub/index.php', ['courseid' => $courseid]);
$ajaxurl = new moodle_url('/local/securecoursehub/ajax.php');

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_securecoursehub'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css(new moodle_url('/local/securecoursehub/styles.css'));

if ($canmanage) {
    $PAGE->requires->js_call_amd('local_securecoursehub/dashboard', 'init', [$ajaxurl->out(false)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);

    try {
        if ($action === 'create') {
            require_capability('local/securecoursehub:createrequest', $context);
            $title = required_param('title', PARAM_RAW_TRIMMED);
            $description = required_param('description', PARAM_RAW_TRIMMED);
            request_service::create_request($courseid, (int) $USER->id, $title, $description);
            redirect($pageurl, get_string('requestcreated', 'local_securecoursehub'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }

        if ($action === 'edit') {
            require_capability('local/securecoursehub:viewown', $context);
            $id = required_param('id', PARAM_INT);
            $title = required_param('title', PARAM_RAW_TRIMMED);
            $description = required_param('description', PARAM_RAW_TRIMMED);
            request_service::update_student_request($id, $courseid, (int) $USER->id, $title, $description);
            redirect($pageurl, get_string('requestupdated', 'local_securecoursehub'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }

        if ($action === 'delete') {
            require_capability('local/securecoursehub:viewown', $context);
            $id = required_param('id', PARAM_INT);
            request_service::delete_student_request($id, $courseid, (int) $USER->id);
            redirect($pageurl, get_string('requestdeleted', 'local_securecoursehub'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }

        throw new moodle_exception('invalidaction', 'local_securecoursehub');
    } catch (moodle_exception $exception) {
        redirect($pageurl, s($exception->getMessage()), null, \core\output\notification::NOTIFY_ERROR);
    }
}

$editid = optional_param('editid', 0, PARAM_INT);
$editrecord = null;
if ($editid > 0 && !$canmanage) {
    try {
        $editrecord = request_service::get_student_editable_request($editid, $courseid, (int) $USER->id);
    } catch (moodle_exception $exception) {
        \core\notification::error(s($exception->getMessage()));
    }
}

$statusfilter = optional_param('status', '', PARAM_ALPHA);
$statusoptions = request_service::status_options();
if ($statusfilter !== '' && !array_key_exists($statusfilter, $statusoptions)) {
    $statusfilter = '';
}

$records = $canmanage
    ? request_service::get_course_requests($courseid, $statusfilter)
    : request_service::get_own_requests($courseid, (int) $USER->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_securecoursehub'));
echo html_writer::div(
    get_string('currentuser', 'local_securecoursehub', fullname($USER)),
    'securecoursehub-current-user'
);

if ($cancreate && !$canmanage) {
    $formtitle = $editrecord
        ? get_string('editrequestheading', 'local_securecoursehub')
        : get_string('createrequestheading', 'local_securecoursehub');
    echo $OUTPUT->heading($formtitle, 3);

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'securecoursehub-form',
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
        echo html_writer::link($pageurl, get_string('cancel', 'local_securecoursehub'), ['class' => 'btn btn-secondary']);
    }
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
}

if ($canmanage) {
    echo $OUTPUT->heading(get_string('courserequests', 'local_securecoursehub'), 3);

    echo html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false),
        'class' => 'securecoursehub-filter']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
    echo html_writer::select(
        ['' => get_string('allstatuses', 'local_securecoursehub')] + $statusoptions,
        'status',
        $statusfilter,
        false,
        ['class' => 'custom-select']
    );
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('filter', 'local_securecoursehub'),
        'class' => 'btn btn-secondary',
    ]);
    echo html_writer::end_tag('form');
    echo html_writer::div('', 'securecoursehub-message', ['id' => 'securecoursehub-message', 'role' => 'status']);
} else {
    echo $OUTPUT->heading(get_string('myrequests', 'local_securecoursehub'), 3);
}

if (!$records) {
    echo $OUTPUT->notification(get_string('norequests', 'local_securecoursehub'),
        \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable securecoursehub-table';

if ($canmanage) {
    $table->head = [
        get_string('title', 'local_securecoursehub'),
        get_string('student', 'local_securecoursehub'),
        get_string('description', 'local_securecoursehub'),
        get_string('status', 'local_securecoursehub'),
        get_string('response', 'local_securecoursehub'),
        get_string('modified', 'local_securecoursehub'),
        get_string('actions', 'local_securecoursehub'),
    ];

    foreach ($records as $record) {
        $student = \core_user::get_user((int) $record->userid, 'id,firstname,lastname', MUST_EXIST);
        $statusid = 'securecoursehub-status-' . $record->id;
        $responseid = 'securecoursehub-response-' . $record->id;
        $responsepreviewid = 'securecoursehub-response-preview-' . $record->id;

        $statusselect = html_writer::select(
            $statusoptions,
            'status_' . $record->id,
            $record->status,
            false,
            ['id' => $statusid, 'class' => 'custom-select securecoursehub-status-select']
        );
        $responseinput = html_writer::tag('textarea', s((string) ($record->response ?? '')), [
            'id' => $responseid,
            'rows' => 3,
            'maxlength' => request_service::MAX_RESPONSE_LENGTH,
            'class' => 'form-control securecoursehub-response-input',
            'aria-label' => get_string('response', 'local_securecoursehub'),
        ]);
        $responsepreview = html_writer::div(
            s((string) ($record->response ?? '')),
            'securecoursehub-response-preview',
            ['id' => $responsepreviewid]
        );
        $button = html_writer::tag('button', get_string('update', 'local_securecoursehub'), [
            'type' => 'button',
            'class' => 'btn btn-primary securecoursehub-update-button',
            'data-request-id' => $record->id,
            'data-course-id' => $courseid,
        ]);

        $table->data[] = [
            s($record->title),
            fullname($student),
            nl2br(s($record->description)),
            $statusselect,
            $responseinput . $responsepreview,
            userdate($record->timemodified),
            $button,
        ];
    }
} else {
    $table->head = [
        get_string('title', 'local_securecoursehub'),
        get_string('description', 'local_securecoursehub'),
        get_string('status', 'local_securecoursehub'),
        get_string('response', 'local_securecoursehub'),
        get_string('modified', 'local_securecoursehub'),
        get_string('actions', 'local_securecoursehub'),
    ];

    foreach ($records as $record) {
        $actions = '';
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
                'data-confirm-message' => get_string('confirmdelete', 'local_securecoursehub'),
            ]);
            $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
            $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'delete']);
            $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $record->id]);
            $actions .= html_writer::tag('button', get_string('delete', 'local_securecoursehub'), [
                'type' => 'submit',
                'class' => 'btn btn-danger btn-sm',
                'onclick' => 'return confirm(this.form.dataset.confirmMessage);',
            ]);
            $actions .= html_writer::end_tag('form');
        }

        $table->data[] = [
            s($record->title),
            nl2br(s($record->description)),
            $statusoptions[$record->status] ?? s($record->status),
            $record->response ? nl2br(s($record->response)) : '',
            userdate($record->timemodified),
            $actions,
        ];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();
