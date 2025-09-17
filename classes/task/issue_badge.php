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
 * An adhoc task to issue badge.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_openeducationbadges\task;

use local_openeducationbadges\client;

/**
 * An adhoc task class to issue a badge.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issue_badge extends \core\task\adhoc_task {

    /**
     * Factory method for issue_badge task.
     *
     * @param int $userid The user id.
     * @param int $badgeid The badge id.
     * @return adhoc_task
     */
    public static function instance(int $userid, int $badgeid): self {
        $task = new self();
        $task->set_custom_data((object) [
            'userid' => $userid,
            'badgeid' => $badgeid,
        ]);

        return $task;
    }

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:issue_badge', 'local_openeducationbadges');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();

        try {
            $client = client::get_instance();
            $client->issue_badge($data->userid, $data->badgeid);
        } catch (\Exception $e) {
            $DB->insert_record(
                'local_oeb_badge_queue',
                [
                    'user_id' => $data->userid,
                    'badgeid' => $data->badgeid,
                ]
            );
        }
    }
}
