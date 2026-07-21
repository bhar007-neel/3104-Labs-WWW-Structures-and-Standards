<?php
// Business rules and database access for Secure Course Hub.
//
// Every write to the plugin table goes through this class. Callers (index.php and
// ajax.php) are responsible for authentication and capability checks; this class
// owns validation, ownership rules, permitted state transitions and persistence.
//
// All queries use the Moodle Database API with placeholder parameters. No user
// input is ever concatenated into SQL.

namespace local_securecoursehub\local;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_text;
use moodle_exception;
use stdClass;

/**
 * Service layer for course help requests.
 */
class request_service {
    /** Table name. */
    public const TABLE = 'local_securecoursehub_req';

    /** Allowed statuses. Exactly these three values, nothing else. */
    public const STATUS_OPEN = 'open';
    public const STATUS_INPROGRESS = 'inprogress';
    public const STATUS_RESOLVED = 'resolved';

    /** Server-side length limits. */
    public const MAX_TITLE_LENGTH = 80;
    public const MAX_DESCRIPTION_LENGTH = 2000;
    public const MAX_RESPONSE_LENGTH = 500;

    /**
     * The status whitelist. Any status value arriving from a client is checked
     * against this list before it can reach the database.
     *
     * @return string[]
     */
    public static function statuses(): array {
        return [self::STATUS_OPEN, self::STATUS_INPROGRESS, self::STATUS_RESOLVED];
    }

    /**
     * Status values mapped to translated labels for display.
     *
     * @return array<string, string>
     */
    public static function status_options(): array {
        return [
            self::STATUS_OPEN => get_string('statusopen', 'local_securecoursehub'),
            self::STATUS_INPROGRESS => get_string('statusinprogress', 'local_securecoursehub'),
            self::STATUS_RESOLVED => get_string('statusresolved', 'local_securecoursehub'),
        ];
    }

    /**
     * Create a request owned by the authenticated user.
     *
     * The owner is always the caller-supplied $USER->id, never a browser value.
     * The initial status is forced to 'open' server side; any posted status is ignored.
     *
     * @param int $courseid Validated course id.
     * @param int $userid The authenticated user id.
     * @param string $title Untrusted title.
     * @param string $description Untrusted description.
     * @return stdClass The stored record.
     */
    public static function create(int $courseid, int $userid, string $title, string $description): stdClass {
        global $DB;

        self::require_course_access($courseid, $userid);
        [$title, $description] = self::validate_text_fields($title, $description);

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

        $record->id = (int) $DB->insert_record(self::TABLE, $record);

        return $record;
    }

    /**
     * List the requests owned by one user in one course.
     *
     * @return stdClass[]
     */
    public static function list_own(int $courseid, int $userid): array {
        global $DB;

        return $DB->get_records(
            self::TABLE,
            ['courseid' => $courseid, 'userid' => $userid],
            'timecreated DESC'
        );
    }

    /**
     * List every request in a course, optionally filtered by status.
     *
     * The caller must already hold managecourserequests in this course context.
     *
     * @param string $status Optional status filter, validated against the whitelist.
     * @return stdClass[]
     */
    public static function list_course(int $courseid, string $status = ''): array {
        global $DB;

        $conditions = ['courseid' => $courseid];
        if ($status !== '') {
            self::validate_status($status);
            $conditions['status'] = $status;
        }

        return $DB->get_records(self::TABLE, $conditions, 'timecreated DESC');
    }

    /**
     * Load one record or raise a not-found exception.
     *
     * The exception is mapped to a safe 404 response by the callers; it never
     * discloses whether other records exist.
     *
     * @throws \dml_missing_record_exception
     */
    public static function get(int $id): stdClass {
        global $DB;

        return $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Load a record that the given student is allowed to edit or delete.
     *
     * Enforces both ownership and the "open only" state rule.
     */
    public static function get_own_editable(int $id, int $userid): stdClass {
        $record = self::get($id);
        self::require_owner($record, $userid);
        self::require_open($record);

        return $record;
    }

    /**
     * Student update: only the owner, only while the request is still open.
     *
     * Validation runs before any write, so a rejected update leaves the row untouched.
     */
    public static function update_own(int $id, int $userid, string $title, string $description): stdClass {
        global $DB;

        $record = self::get_own_editable($id, $userid);
        [$title, $description] = self::validate_text_fields($title, $description);

        $record->title = $title;
        $record->description = $description;
        $record->timemodified = time();
        $DB->update_record(self::TABLE, $record);

        return $record;
    }

    /**
     * Teacher update: set the status and optionally add or replace the response note.
     *
     * The caller must already hold managecourserequests in the context recomputed
     * from $record->courseid.
     *
     * @param string|null $response Null leaves the existing note untouched.
     */
    public static function update_status(int $id, string $status, ?string $response = null): stdClass {
        global $DB;

        self::validate_status($status);

        $record = self::get($id);

        if ($response !== null) {
            $response = trim($response);
            if (core_text::strlen($response) > self::MAX_RESPONSE_LENGTH) {
                throw new moodle_exception('responseinvalid', 'local_securecoursehub');
            }
            $record->response = $response === '' ? null : $response;
        }

        $record->status = $status;
        $record->timemodified = time();
        $DB->update_record(self::TABLE, $record);

        return $record;
    }

    /**
     * Owner deletion: a student may delete their own request only while it is open.
     */
    public static function delete_own(int $id, int $userid): stdClass {
        global $DB;

        $record = self::get_own_editable($id, $userid);
        $DB->delete_records(self::TABLE, ['id' => $record->id]);

        return $record;
    }

    /**
     * Managed deletion: a teacher or manager may delete any request in a course
     * where they hold managecourserequests. The caller checks that capability in
     * the context derived from the record itself.
     */
    public static function delete_managed(int $id): stdClass {
        global $DB;

        $record = self::get($id);
        $DB->delete_records(self::TABLE, ['id' => $record->id]);

        return $record;
    }

    /**
     * Shape a record for a JSON response.
     *
     * Only fields the client needs are returned; no internal identifiers beyond
     * the record id and no other user's personal data unless $includeowner is set
     * by a caller that has already verified the management capability.
     *
     * @return array<string, mixed>
     */
    public static function to_json_row(stdClass $record, bool $includeowner = false): array {
        $labels = self::status_options();

        $row = [
            'id' => (int) $record->id,
            'courseid' => (int) $record->courseid,
            'title' => (string) $record->title,
            'description' => (string) $record->description,
            'status' => (string) $record->status,
            'statuslabel' => $labels[$record->status] ?? (string) $record->status,
            'response' => (string) ($record->response ?? ''),
            'timecreated' => (int) $record->timecreated,
            'timemodified' => (int) $record->timemodified,
            'timemodifiedformatted' => userdate($record->timemodified),
        ];

        if ($includeowner) {
            $owner = \core_user::get_user((int) $record->userid, 'id, firstname, lastname');
            $row['userid'] = (int) $record->userid;
            $row['ownername'] = $owner ? fullname($owner) : '';
        }

        return $row;
    }

    /**
     * A user may raise a request only in a course they can actually reach.
     */
    private static function require_course_access(int $courseid, int $userid): void {
        $context = context_course::instance($courseid);
        $ismanager = has_capability('local/securecoursehub:managecourserequests', $context, $userid);

        if (!is_enrolled($context, $userid, '', true) && !$ismanager) {
            throw new moodle_exception('notenrolled', 'local_securecoursehub');
        }
    }

    /**
     * Presence and length validation for the two free-text fields.
     *
     * The text itself is stored verbatim: escaping is applied at output time
     * (s(), format_string(), textContent) so that an injected script string is
     * preserved as data and rendered harmlessly as text.
     *
     * @return array{0: string, 1: string}
     */
    private static function validate_text_fields(string $title, string $description): array {
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

    /**
     * Reject any status outside the whitelist.
     */
    private static function validate_status(string $status): void {
        if (!in_array($status, self::statuses(), true)) {
            throw new moodle_exception('statusinvalid', 'local_securecoursehub');
        }
    }

    /**
     * Resource-level authorisation: the record must belong to this user.
     */
    private static function require_owner(stdClass $record, int $userid): void {
        if ((int) $record->userid !== $userid) {
            throw new moodle_exception('notowner', 'local_securecoursehub');
        }
    }

    /**
     * State rule: students may only change requests that are still open.
     */
    private static function require_open(stdClass $record): void {
        if ($record->status !== self::STATUS_OPEN) {
            throw new moodle_exception('requestnotopen', 'local_securecoursehub');
        }
    }
}
