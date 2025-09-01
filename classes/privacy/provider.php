<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_openeducationbadges\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem class.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns mata data for given user id.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'local_oeb_badge_queue',
            [
                'id' => 'privacy:metadata:badge_queue:id',
                'badgeid' => 'privacy:metadata:badge_queue:badgeid',
                'user_id' => 'privacy:metadata:badge_queue:user_id',
            ],
            'privacy:metadata:badge_queue'
        );

        $collection->add_external_location_link(
            'oeb_client',
            [
                'useremail' => 'privacy:metadata:oeb_client:useremail',
            ],
            'privacy:metadata:oeb_client'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $contextlist->add_system_context();

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $context = \context_system::instance();
        $user = $contextlist->get_user();

        $data = $DB->get_records('local_oeb_badge_queue', ['user_id' => $user->id]);
        if (!empty($data)) {
            writer::with_context($context)->export_data(['local_oeb_badge_queue'], (object) $data);
        }

        $data = new \stdClass();
        $data->name = get_string('privacy:metadata:oeb_client', 'local_openeducationbadges');
        $data->description = get_string('privacy:metadata:oeb_client:datarights', 'local_openeducationbadges');
        writer::with_context($context)->export_data(['oeb_client'], $data);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // There is no user data in other contexts.
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('local_oeb_badge_queue');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('local_oeb_badge_queue', ['user_id' => $userid]);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $records = $DB->get_records(
                'local_oeb_badge_queue',
                null,
                '',
                'user_id'
            );

            $userids = [];
            foreach ($records as $record) {
                $userids[] = intval($record->user_id);
            }
            $userids = array_unique($userids, SORT_NUMERIC);

            $userlist->add_users($userids);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            foreach ($userids as $userid) {
                $DB->delete_records('local_oeb_badge_queue', ['user_id' => strval($userid)]);
            }
        }
    }
}
