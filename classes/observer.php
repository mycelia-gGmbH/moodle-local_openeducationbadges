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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/gpl-3.0>.

use classes\openeducation_badge;
use classes\openeducation_client;

require_once(__DIR__ . '/badge.php');
require_once(__DIR__ . '/client.php');

/**
 * Class for event observers
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_openeducationbadges_observer
{

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

		$user = $DB->get_record('user', array('id' => $eventdata->userid));

		$records = $DB->get_records(
			'local_openeducationbadges_course_badge',
			array(
				'courseid' => $eventdata->course,
				'completion_method' => openeducation_badge::COMPLETION_TYPE_COURSE
			),
			'',
			'badgeid'
		);
		$badge_ids = [];
		foreach ($records as $record) {
			$badge_ids[] = $record->badgeid;
		}

		try {
			$client = openeducation_client::get_instance();
			$client->issue_badges($user, $badge_ids);
		} catch (Exception $e) {}
	}
}
