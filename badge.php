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
 * Page for displaying content closely related to badges.
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use classes\openeducation_badge;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/badge.php');

$courseid = optional_param('courseid', null, PARAM_INT);
$context = empty($courseid) ? context_system::instance() : context_course::instance($courseid);

$url = new moodle_url('/local/openeducationbadges/badge.php');

// Site context.
if (empty($courseid)) {
	require_login();
} else { // Course context.
	$url->param('courseid', $courseid);
	require_login($courseid);
}

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout(empty($courseid) ? 'admin' : 'course');
$PAGE->set_title(get_string('oeb', 'local_openeducationbadges'));
$PAGE->add_body_class('local-openeducationbadges');

$content = '';

try {
	$badges = openeducation_badge::get_badges();
	$content .= $PAGE->get_renderer('local_openeducationbadges')->render_badgelist($badges, $context);
} catch (Exception $e) {
	$content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
