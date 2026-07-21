<?php
// Privacy API implementation for Secure Course Hub.
//
// The plugin stores personal data (the request text a user wrote and the staff
// response about them), so it declares that data and supports export and
// deletion through Moodle's privacy subsystem.

namespace local_securecoursehub\privacy;

defined('MOODLE_INTERNAL') || die();

use context;
use context_course;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_securecoursehub\local\request_service;

/**
 * Privacy provider for local_securecoursehub.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the personal data this plugin stores.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            request_service::TABLE,
            [
                'courseid' => 'privacy:metadata:local_securecoursehub_req:courseid',
                'userid' => 'privacy:metadata:local_securecoursehub_req:userid',
                'title' => 'privacy:metadata:local_securecoursehub_req:title',
                'description' => 'privacy:metadata:local_securecoursehub_req:description',
                'status' => 'privacy:metadata:local_securecoursehub_req:status',
                'response' => 'privacy:metadata:local_securecoursehub_req:response',
                'timecreated' => 'privacy:metadata:local_securecoursehub_req:timecreated',
                'timemodified' => 'privacy:metadata:local_securecoursehub_req:timemodified',
            ],
            'privacy:metadata:local_securecoursehub_req'
        );

        return $collection;
    }

    /**
     * Course contexts in which a user has requests.
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT ctx.id
                  FROM {" . request_service::TABLE . "} r
                  JOIN {context} ctx ON ctx.instanceid = r.courseid AND ctx.contextlevel = :contextlevel
                 WHERE r.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Users who have requests in a given course context.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof context_course) {
            return;
        }

        $userlist->add_from_sql(
            'userid',
            "SELECT userid FROM {" . request_service::TABLE . "} WHERE courseid = :courseid",
            ['courseid' => $context->instanceid]
        );
    }

    /**
     * Export every request owned by the approved user.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_course) {
                continue;
            }

            $records = $DB->get_records(request_service::TABLE, [
                'courseid' => $context->instanceid,
                'userid' => $userid,
            ], 'timecreated ASC');

            if (!$records) {
                continue;
            }

            $data = array_map(static function ($record) {
                return (object) [
                    'title' => $record->title,
                    'description' => $record->description,
                    'status' => $record->status,
                    'response' => $record->response,
                    'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                    'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                ];
            }, array_values($records));

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_securecoursehub')],
                (object) ['requests' => $data]
            );
        }
    }

    /**
     * Delete every request in a course context.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if (!$context instanceof context_course) {
            return;
        }

        $DB->delete_records(request_service::TABLE, ['courseid' => $context->instanceid]);
    }

    /**
     * Delete one user's requests in the approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_course) {
                continue;
            }

            $DB->delete_records(request_service::TABLE, [
                'courseid' => $context->instanceid,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Delete the requests of an approved list of users in one context.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_course) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['courseid'] = $context->instanceid;

        $DB->delete_records_select(
            request_service::TABLE,
            "courseid = :courseid AND userid {$insql}",
            $params
        );
    }
}
