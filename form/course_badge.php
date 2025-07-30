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
 * Course completion form for badges.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use classes\openeducation_badge;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Course completion badge form.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oeb_course_badge_form extends moodleform {
    /** @var int The id of the current badge */
    private $badgeid = 0;

    /** @var int The id of the current course */
    private $courseid = 0;

    /**
     * Constructor.
     *
     * @param string $actionurl
     * @param int $badgeid
     * @param int $courseid
     */
    public function __construct($actionurl, $badgeid, $courseid) {
        $this->badgeid = $badgeid;
        $this->courseid = $courseid;
        parent::__construct($actionurl);
    }

    /**
     * Add elements to form.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement(
            'advcheckbox',
            'coursecompletion_'.strval($this->badgeid),
            get_string('coursecompletion', 'local_openeducationbadges')
        );

        $actvityoptions = $this->get_activity_options();
        if (!empty($actvityoptions)) {
            $mform->addElement(
                'static',
                'activities',
                null,
                get_string('activitycompletion', 'local_openeducationbadges')
            );

            $mform->addElement('html', '<div class="activitylist">');
            foreach ($actvityoptions as $activityid => $activityname) {
                $mform->addElement(
                    'advcheckbox',
                    'activitycompletion_'.strval($this->badgeid).'_'.strval($activityid),
                    $activityname
                );
            }
            $mform->addElement('html', '</div>');
        }

        $mform->addElement('submit', 'submit_'.strval($this->badgeid), get_string('saveawarding', 'local_openeducationbadges'));
    }

    /**
     * Get a list of all course activities with completion criterions
     *
     * @return array A list of activities
     */
    public function get_activity_options() {

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
