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
 * Cron task for issuing badges in queue.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_openeducationbadges\task;

use local_openeducationbadges\client;

/**
 * Cron task class for issuing badges in queue.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issue_badges extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:issue_badges', 'local_openeducationbadges');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $issuerecords = $DB->get_records('local_oeb_badge_queue');

        $client = client::get_instance();

        foreach ($issuerecords as $record) {
            $client->issue_badge($record->user_id, $record->badgeid);

            $DB->delete_records('local_oeb_badge_queue', ['id' => $record->id]);
        }
    }
}
