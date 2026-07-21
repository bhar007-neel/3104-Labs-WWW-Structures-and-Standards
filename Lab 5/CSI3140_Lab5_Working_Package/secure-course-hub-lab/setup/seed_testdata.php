<?php
// Idempotent test-data seeding for CSI 3140 Lab 5.
//
// Runs inside the Moodle container as the web-server user. Creates the four
// synthetic accounts, the demonstration course, the enrolments and two sample
// requests owned by learner1. Re-running is safe: everything is looked up first
// and only created when missing.
//
// This file is part of the local development environment, not of the plugin, and
// is not included in the submission ZIP.

define('CLI_SCRIPT', true);

require('/bitnami/moodle/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/enrollib.php');

use local_securecoursehub\local\request_service;

// Synthetic accounts only. These passwords exist solely in this throwaway local
// container and are documented in the README as test credentials.
const LAB5_COURSE_SHORTNAME = 'CSI3140-DEMO';
const LAB5_TEST_PASSWORD = 'Test1234!';

/**
 * Find a user by username, or create it.
 */
function lab5_ensure_user(string $username, string $firstname, string $lastname): stdClass {
    global $DB, $CFG;

    $existing = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
    if ($existing) {
        cli_writeln("  user {$username} already exists (id {$existing->id})");
        return $existing;
    }

    $user = new stdClass();
    $user->username = $username;
    $user->password = LAB5_TEST_PASSWORD;
    $user->firstname = $firstname;
    $user->lastname = $lastname;
    $user->email = $username . '@example.invalid';
    $user->auth = 'manual';
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->timecreated = time();

    $userid = user_create_user($user, true, false);
    cli_writeln("  created user {$username} (id {$userid})");

    return $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
}

/**
 * Find the demonstration course, or create it.
 */
function lab5_ensure_course(): stdClass {
    global $DB;

    $existing = $DB->get_record('course', ['shortname' => LAB5_COURSE_SHORTNAME]);
    if ($existing) {
        cli_writeln("  course " . LAB5_COURSE_SHORTNAME . " already exists (id {$existing->id})");
        return $existing;
    }

    $category = $DB->get_record('course_categories', [], '*', IGNORE_MULTIPLE);

    $course = new stdClass();
    $course->fullname = 'CSI 3140 Demonstration Course';
    $course->shortname = LAB5_COURSE_SHORTNAME;
    $course->category = $category ? $category->id : 1;
    $course->summary = 'Demonstration course for the Secure Course Hub plugin.';
    $course->format = 'topics';
    $course->visible = 1;

    $created = create_course($course);
    cli_writeln("  created course " . LAB5_COURSE_SHORTNAME . " (id {$created->id})");

    return $created;
}

/**
 * Enrol a user in a course with a role, if not already enrolled.
 */
function lab5_ensure_enrolment(stdClass $course, stdClass $user, string $roleshortname): void {
    global $DB;

    $role = $DB->get_record('role', ['shortname' => $roleshortname], '*', MUST_EXIST);
    $context = context_course::instance($course->id);

    if (user_has_role_assignment($user->id, $role->id, $context->id)) {
        cli_writeln("  {$user->username} already enrolled as {$roleshortname}");
        return;
    }

    $plugin = enrol_get_plugin('manual');
    $instance = $DB->get_record('enrol', [
        'courseid' => $course->id,
        'enrol' => 'manual',
    ], '*', IGNORE_MULTIPLE);

    if (!$instance) {
        $instanceid = $plugin->add_default_instance($course);
        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
    }

    $plugin->enrol_user($instance, $user->id, $role->id);
    cli_writeln("  enrolled {$user->username} as {$roleshortname}");
}

/**
 * Seed two sample requests owned by learner1, once.
 */
function lab5_seed_requests(stdClass $course, stdClass $owner): void {
    global $DB;

    $existing = $DB->count_records(request_service::TABLE, [
        'courseid' => $course->id,
        'userid' => $owner->id,
    ]);

    if ($existing > 0) {
        cli_writeln("  {$existing} request(s) already seeded for {$owner->username}");
        return;
    }

    $samples = [
        [
            'title' => 'Cannot open week 3 lab handout',
            'description' => 'The PDF link on the week 3 page returns a not-found page for me. '
                . 'Could the file be re-uploaded?',
        ],
        [
            'title' => 'Request extension for assignment 2',
            'description' => 'I had a scheduling conflict with another course deadline and would like '
                . 'to ask about a short extension.',
        ],
    ];

    foreach ($samples as $sample) {
        $now = time();
        $record = (object) [
            'courseid' => $course->id,
            'userid' => $owner->id,
            'title' => $sample['title'],
            'description' => $sample['description'],
            'status' => request_service::STATUS_OPEN,
            'response' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $id = $DB->insert_record(request_service::TABLE, $record);
        cli_writeln("  seeded request id {$id} for {$owner->username}");
    }
}

cli_writeln('Seeding Lab 5 test data');

cli_writeln(' accounts:');
$staff1 = lab5_ensure_user('staff1', 'Sam', 'Teacher');
$learner1 = lab5_ensure_user('learner1', 'Alex', 'StudentA');
$learner2 = lab5_ensure_user('learner2', 'Blair', 'StudentB');

cli_writeln(' course:');
$course = lab5_ensure_course();

cli_writeln(' enrolments:');
lab5_ensure_enrolment($course, $staff1, 'editingteacher');
lab5_ensure_enrolment($course, $learner1, 'student');
lab5_ensure_enrolment($course, $learner2, 'student');

cli_writeln(' sample requests:');
lab5_seed_requests($course, $learner1);

// Developer debugging is enabled for the local instance, but debug *display* is
// deliberately left off so that no internal detail can reach an HTTP response.
// Messages still go to the server error log for the developer.
set_config('debug', DEBUG_DEVELOPER);
set_config('debugdisplay', 0);
cli_writeln(' debugging: developer level enabled, display disabled (errors go to the log only)');

cli_writeln('');
cli_writeln('MOODLE_RELEASE=' . $CFG->release);
cli_writeln('MOODLE_VERSION=' . $CFG->version);
cli_writeln('PHP_VERSION=' . PHP_VERSION);
cli_writeln('DEMO_COURSE_ID=' . $course->id);
cli_writeln('PLUGIN_URL=' . $CFG->wwwroot . '/local/securecoursehub/index.php?courseid=' . $course->id);
cli_writeln('Done.');
