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
 * Lib for Open Education Badges plugin.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_openeducationbadges\badge;
use local_openeducationbadges\client;
use local_openeducationbadges\output\badge_page;

/**
 * Adds the Open Education Badges links to Moodle's settings navigation.
 *
 * @param settings_navigation $navigation
 */
function local_openeducationbadges_extend_settings_navigation(settings_navigation $navigation) {
    global $COURSE;

    if (($branch = $navigation->get('courseadmin'))) {
        $branch = local_openeducationbadges_add_course_admin_container($branch);
        local_openeducationbadges_add_course_admin_link($branch);
    }
}

/**
 * Adds the Open Education Badges admin-links container.
 *
 * @param type& $branch Branch where to add the container node.
 */
function local_openeducationbadges_add_course_admin_container(&$branch) {
    global $COURSE;

    $node = navigation_node::create(
        get_string('oeb', 'local_openeducationbadges'),
        null,
        navigation_node::TYPE_CONTAINER,
        null,
        'oeb'
    );
    $coursecompletionnode = $branch->find('coursecompletion', navigation_node::TYPE_SETTING);
    return $branch->add_node($node, $coursecompletionnode != false ? 'coursecompletion' : null);
}

/**
 * Adds the Open Education Badges links to course management navigation,
 * and a drowpdown with several options on subpage.
 *
 * @param type& $branch
 */
function local_openeducationbadges_add_course_admin_link(&$branch) {
    global $COURSE;

    $capcreatecourse = has_capability(
        'moodle/course:create',
        context_course::instance($COURSE->id)
    );
    $capupdatecourse = has_capability(
        'moodle/course:update',
        context_course::instance($COURSE->id)
    );

    if ($capcreatecourse || $capupdatecourse) {
        $node = navigation_node::create(
            get_string('oeb', 'local_openeducationbadges'),
            new moodle_url(
                '/local/openeducationbadges/badge.php',
                [
                    'courseid' => $COURSE->id,
                ]
            )
        );
        $branch->add_node($node);
    }
}

/**
 * Adds Open Education Badges to profile pages.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param \moodle_course $course
 */
function local_openeducationbadges_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $PAGE, $DB, $CFG;

    $category = new core_user\output\myprofile\category(
        'local_openeducationbadges/badges',
        get_string('profilebadgelist', 'local_openeducationbadges'),
        null
    );
    $tree->add_category($category);

    local_openeducationbadges_addbadges_profile($tree, $user);
}

/**
 * Adds Open Education Badges to the profile tree.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 */
function local_openeducationbadges_addbadges_profile($tree, $user): void {
    global $PAGE, $DB, $OUTPUT;

    $category = new core_user\output\myprofile\category(
        'local_openeducationbadges/badgesplatform',
        get_string('badgesplatform', 'local_openeducationbadges'),
        null
    );
    $tree->add_category($category);

    $badges = badge::get_earned_badges($user);
    if (count($badges) === 0) {
        $content = get_string('nobadgesearned', 'local_openeducationbadges');
    } else {
        $context = context_user::instance($user->id);
        $renderable = new badge_page('', $badges, $context);
        $content = $OUTPUT->render($renderable);
    }

    $localnode = new core_user\output\myprofile\node(
        'local_openeducationbadges/badgesplatform',
        'openeducationbadges',
        '',
        null,
        null,
        $content,
        null,
        'path-local-openeducationbadges'
    );
    $tree->add_node($localnode);
}
