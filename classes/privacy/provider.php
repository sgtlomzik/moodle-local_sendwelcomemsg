<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for local_sendwelcomemsg.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sendwelcomemsg\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for the pending welcome message queue.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the data held by this plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_sendwelcomemsg_queue', [
            'userid' => 'privacy:metadata:queue:userid',
            'timecreated' => 'privacy:metadata:queue:timecreated',
        ], 'privacy:metadata:queue');

        $collection->add_external_location_link('smtp', [
            'email' => 'privacy:metadata:smtp:email',
            'fullname' => 'privacy:metadata:smtp:fullname',
        ], 'privacy:metadata:smtp');

        return $collection;
    }

    /**
     * Get the contexts containing data for a user.
     *
     * @param int $userid The user to look up.
     * @return contextlist The contexts holding data for this user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {local_sendwelcomemsg_queue} q
                  JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = q.userid
                 WHERE q.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the users holding data in a given context.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $userlist->add_from_sql('userid', "SELECT userid
                                             FROM {local_sendwelcomemsg_queue}
                                            WHERE userid = :userid", ['userid' => $context->instanceid]);
    }

    /**
     * Export the queued welcome message for the given contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || $context->instanceid != $userid) {
                continue;
            }

            $records = $DB->get_records('local_sendwelcomemsg_queue', ['userid' => $userid]);
            if (empty($records)) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = (object)['timecreated' => transform::datetime($record->timecreated)];
            }

            writer::with_context($context)->export_data(
                [get_string('privacy:queuepath', 'local_sendwelcomemsg')],
                (object)['queued' => $data]
            );
        }
    }

    /**
     * Delete all queue entries in a context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_user) {
            return;
        }

        $DB->delete_records('local_sendwelcomemsg_queue', ['userid' => $context->instanceid]);
    }

    /**
     * Delete the queue entries of one user.
     *
     * @param approved_contextlist $contextlist The approved contexts and user to delete for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_user && $context->instanceid == $userid) {
                $DB->delete_records('local_sendwelcomemsg_queue', ['userid' => $userid]);
            }
        }
    }

    /**
     * Delete the queue entries of several users in one context.
     *
     * @param approved_userlist $userlist The approved context and users to delete for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_sendwelcomemsg_queue', "userid {$insql}", $params);
    }
}
