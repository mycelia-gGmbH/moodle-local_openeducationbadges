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
 * Open Education Badges plugin event observers.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use classes\openeducation_badge;
use classes\openeducation_client;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/badge.php');
require_once(__DIR__ . '/client.php');

/**
 * Class for event observers
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_openeducationbadges_observer {

    /**
     * Course completed observer
     *
     * @param \core\event\course_completed $event
     * @return boolean Returns true if everything went ok.
     */
    public static function course_completed(\core\event\course_completed $event) {
        $eventdata = new stdClass();
        $eventdata->userid = $event->relateduserid;
        $eventdata->course = $event->courseid;
        return self::course_user_completion_award($eventdata);
    }

    /**
     * Issues badges when a course is completed.
     *
     * @param stdClass $eventdata
     */
    private static function course_user_completion_award(stdClass $eventdata) {
        global $DB;

        $user = $DB->get_record('user', ['id' => $eventdata->userid]);

        $records = $DB->get_records(
            'local_oeb_course_badge',
            [
                'courseid' => $eventdata->course,
                'completion_method' => openeducation_badge::COMPLETION_TYPE_COURSE,
            ],
            '',
            'badgeid'
        );
        $badgeids = [];
        foreach ($records as $record) {
            $badgeids[] = $record->badgeid;
        }

        try {
            $client = openeducation_client::get_instance();
            $client->issue_badges($user, $badgeids);
        } catch (Exception $e) {
            // TODO remember and try issuing later again.
            // In the mean time do not disturb user.
        }
    }

    /**
     * Course modules completion observer
     *
     * @param \core\event\course_module_completion_updated $event
     * @return boolean Returns true if everything went ok.
     */
    public static function course_module_completed(\core\event\course_module_completion_updated $event) {
        $recordsnapshot = $event->get_record_snapshot('course_modules_completion', $event->objectid);
        $context = context_module::instance($recordsnapshot->coursemoduleid);
        if ($context && $context->get_course_context()) {
            $eventdata = new stdClass();
            $eventdata->userid = $event->relateduserid;
            $eventdata->course = $event->courseid;
            $eventdata->coursemoduleid = $recordsnapshot->coursemoduleid;
            return self::course_module_user_completion_award($eventdata);
        }
    }

    /**
     * Issues badges when an activity is completed.
     *
     * @param stdClass $eventdata
     */
    private static function course_module_user_completion_award(stdClass $eventdata) {
        global $DB;

        $user = $DB->get_record('user', ['id' => $eventdata->userid]);

        $records = $DB->get_records(
            'local_oeb_course_badge',
            [
                'courseid' => $eventdata->course,
                'activityid' => $eventdata->coursemoduleid,
                'completion_method' => openeducation_badge::COMPLETION_TYPE_ACTIVITY,
            ],
            '',
            'badgeid'
        );
        $badgeids = [];
        foreach ($records as $record) {
            $badgeids[] = $record->badgeid;
        }

        try {
            $client = openeducation_client::get_instance();
            $client->issue_badges($user, $badgeids);
        } catch (Exception $e) {
            // TODO remember and try issuing later again.
            // In the mean time do not disturb user.
        }
    }
}
