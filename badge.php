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
 * Page for displaying content closely related to badges.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_openeducationbadges\badge;
use local_openeducationbadges\client;
use local_openeducationbadges\output\badge_page;
use local_openeducationbadges\output\badge_edit_page;
use local_openeducationbadges\output\badge_create_page;

require(__DIR__ . '/../../config.php');

$courseid = optional_param('courseid', null, PARAM_INT);
$context = empty($courseid) ? context_system::instance() : context_course::instance($courseid);

$action = optional_param('action', 'list', PARAM_TEXT);
$badgeslug = optional_param('badge', null, PARAM_TEXT);
$issuerslug = optional_param('issuer', null, PARAM_TEXT);
$clientid = optional_param('clientid', null, PARAM_INT);

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

$content = '';

try {
    $client = client::get_instance();
    if ($client->exist_severed_connections()) {
        $content .= $OUTPUT->notification(get_string('connectionproblemgeneral', 'local_openeducationbadges'), 'notifyproblem');
    }
} catch (Exception $e) {
    $content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
}

switch ($action) {
    case 'edit':
        if ($badgeslug) {
            try {
                $renderable = new badge_edit_page($badgeslug, $client, $context);
                $content .= $OUTPUT->render($renderable);
            } catch (Exception $e) {
                $content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
            }
        } else {
            $content .= $OUTPUT->notification(get_string('nobadgegiven', 'local_openeducationbadges'), 'notifyproblem');
        }

        break;
    case 'create':
        if ($issuerslug) {
            try {
                $renderable = new badge_create_page($issuerslug, $clientid, $client, $context);
                $content .= $OUTPUT->render($renderable);
            } catch (Exception $e) {
                $content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
            }
        } else {
            $content .= $OUTPUT->notification(get_string('noissuergiven', 'local_openeducationbadges'), 'notifyproblem');
        }

        break;
    default:
        try {
            $badges = badge::get_badges();
            if (count($badges) === 0) {
                $content .= $OUTPUT->notification(get_string('nobadges', 'local_openeducationbadges'), 'notifynotice');
            }

            $renderable = new badge_page(get_string('badgelisttitle', 'local_openeducationbadges'), $badges, $context);
            $content .= $OUTPUT->render($renderable);
        } catch (Exception $e) {
            $content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
        }
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
