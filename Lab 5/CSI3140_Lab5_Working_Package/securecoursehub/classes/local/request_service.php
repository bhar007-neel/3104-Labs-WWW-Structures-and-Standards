<?php
// Business and database operations for Secure Course Hub.

namespace local_securecoursehub\local;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_text;
use moodle_exception;
use stdClass;

class request_service {
    public const STATUS_OPEN = 'open';
    public const STATUS_INPROGRESS = 'inprogress';
    public const STATUS_RESOLVED = 'resolved';
    public const MAX_TITLE_LENGTH = 255;
    public const MAX_DESCRIPTION_LENGTH = 2000;
    public const MAX_RESPONSE_LENGTH = 500;

    /** @return array<string, string> */
    public static function status_options(): array {
        return [
            self::STATUS_OPEN => get_string('statusopen', 'local_securecoursehub'),
            self::STATUS_INPROGRESS => get_string('statusinprogress', 'local_securecoursehub'),
            self::STATUS_RESOLVED => get_string('statusresolved', 'local_securecoursehub'),
        ];
    }

    public static function create_request(int $courseid, int $userid, string $title, string $description): int {
        global $DB;

        self::require_course_access($courseid, $userid);
        [$title, $description] = self::validate_student_fields($title, $description);

        $now = time();
        $record = (object) [
            'courseid' => $courseid,
            'userid' => $userid,
            'title' => $title,
            'description' => $description,
            'status' => self::STATUS_OPEN,
            'response' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        return (int) $DB->insert_record('local_securecoursehub', $record);
    }

    /** @return array<int, stdClass> */
    public static function get_own_requests(int $courseid, int $userid): array {
        global $DB;

        return $DB->get_records(
            'local_securecoursehub',
            ['courseid' => $courseid, 'userid' => $userid],
            'timecreated DESC'
        );
    }

    /** @return array<int, stdClass> */
    public static function get_course_requests(int $courseid, string $status = ''): array {
        global $DB;

        $conditions = ['courseid' => $courseid];
        if ($status !== '') {
            self::validate_status($status);
            $conditions['status'] = $status;
        }

        return $DB->get_records('local_securecoursehub', $conditions, 'timecreated DESC');
    }

    public static function get_request(int $id): stdClass {
        global $DB;

        return $DB->get_record('local_securecoursehub', ['id' => $id], '*', MUST_EXIST);
    }

    public static function get_student_editable_request(int $id, int $courseid, int $userid): stdClass {
        $record = self::get_request($id);
        self::require_same_course($record, $courseid);
        self::require_owner($record, $userid);
        self::require_open($record);
        return $record;
    }

    public static function update_student_request(
        int $id,
        int $courseid,
        int $userid,
        string $title,
        string $description
    ): void {
        global $DB;

        $record = self::get_student_editable_request($id, $courseid, $userid);
        [$title, $description] = self::validate_student_fields($title, $description);

        $record->title = $title;
        $record->description = $description;
        $record->timemodified = time();
        $DB->update_record('local_securecoursehub', $record);
    }

    public static function delete_student_request(int $id, int $courseid, int $userid): void {
        global $DB;

        $record = self::get_student_editable_request($id, $courseid, $userid);
        $DB->delete_records('local_securecoursehub', ['id' => $record->id]);
    }

    public static function update_teacher_request(
        int $id,
        int $courseid,
        string $status,
        string $response
    ): stdClass {
        global $DB;

        self::validate_status($status);
        $response = trim($response);
        if (core_text::strlen($response) > self::MAX_RESPONSE_LENGTH) {
            throw new moodle_exception('responseinvalid', 'local_securecoursehub');
        }

        $record = self::get_request($id);
        self::require_same_course($record, $courseid);

        $record->status = $status;
        $record->response = $response === '' ? null : $response;
        $record->timemodified = time();
        $DB->update_record('local_securecoursehub', $record);

        return $record;
    }

    private static function require_course_access(int $courseid, int $userid): void {
        $context = context_course::instance($courseid);
        $ismanager = has_capability('local/securecoursehub:managecourserequests', $context, $userid);
        if (!is_enrolled($context, $userid, '', true) && !$ismanager) {
            throw new moodle_exception('notenrolled', 'local_securecoursehub');
        }
    }

    /** @return array{0: string, 1: string} */
    private static function validate_student_fields(string $title, string $description): array {
        $title = trim($title);
        $description = trim($description);

        if ($title === '' || core_text::strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new moodle_exception('titleinvalid', 'local_securecoursehub');
        }
        if ($description === '' || core_text::strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            throw new moodle_exception('descriptioninvalid', 'local_securecoursehub');
        }

        return [$title, $description];
    }

    private static function validate_status(string $status): void {
        if (!array_key_exists($status, self::status_options())) {
            throw new moodle_exception('statusinvalid', 'local_securecoursehub');
        }
    }

    private static function require_same_course(stdClass $record, int $courseid): void {
        if ((int) $record->courseid !== $courseid) {
            throw new moodle_exception('courseismatch', 'local_securecoursehub');
        }
    }

    private static function require_owner(stdClass $record, int $userid): void {
        if ((int) $record->userid !== $userid) {
            throw new moodle_exception('notowner', 'local_securecoursehub');
        }
    }

    private static function require_open(stdClass $record): void {
        if ($record->status !== self::STATUS_OPEN) {
            throw new moodle_exception('requestnotopen', 'local_securecoursehub');
        }
    }
}
