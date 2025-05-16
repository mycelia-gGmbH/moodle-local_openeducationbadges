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
// along with Moodle. If not, see <https://www.gnu.org/licenses/gpl-3.0>.

/**
 * Course completion form for badges.
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use classes\openeducation_badge;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Course completion badge form.
 *
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oeb_course_badge_form extends moodleform {

	private $badgeid = 0;
	private $courseid = 0;

	public function __construct($actionurl, $badgeid, $courseid) {
		$this->badgeid = $badgeid;
		$this->courseid = $courseid;
		parent::__construct($actionurl);
	}

	public function definition() {
		$mform = $this->_form;

		$mform->addElement('advcheckbox', 'coursecompletion_'.strval($this->badgeid), get_string('coursecompletion', 'local_openeducationbadges'));

		$actvityOptions = $this->getActivityOptions();
		if (!empty($actvityOptions)) {
			$mform->addElement('static', 'activities',
				NULL,
   			get_string('activitycompletion', 'local_openeducationbadges')
			);

			$mform->addElement('html', '<div class="activitylist">');
			foreach ($actvityOptions as $activityid => $activityname) {
				$mform->addElement('advcheckbox', 'activitycompletion_'.strval($this->badgeid).'_'.strval($activityid), $activityname);
			}
			$mform->addElement('html', '</div>');
		}

		$mform->addElement('submit', 'submit_'.strval($this->badgeid), get_string('saveawarding', 'local_openeducationbadges'));
	}

	public function get_data() {
		$data = parent::get_data();

		return $data;
	}

	public function getActivityOptions() {

		$activities = [];

		$modinfo = get_fast_modinfo($this->courseid);
		$cms = $modinfo->get_cms();

		foreach ($cms as $cm) {
			if ($cm->completion) {
				$activities[$cm->id] = $cm->get_formatted_name();
			}
		}

		return $activities;
	}
}
