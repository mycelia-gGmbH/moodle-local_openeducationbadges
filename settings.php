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

/**
 * settings page and settings navigation definitions
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (has_capability('local/openeducationbadges:configure', context_system::instance())) {

	// site admin category
	$openeducationbadges = new admin_category('openeducationbadges', get_string('pluginname', 'local_openeducationbadges'));

	// clients page
	$clients = new admin_externalpage(
		'openeducationbadgesconfig',
		get_string('clients', 'local_openeducationbadges'),
		new moodle_url('/local/openeducationbadges/config.php'),
		'local/openeducationbadges:configure'
	);

	// badges page
	$badgelist = new admin_externalpage(
		'openeducationbadgesbadgelist',
		get_string('badgelist', 'local_openeducationbadges'),
		new moodle_url('/local/openeducationbadges/badge.php'),
		'local/openeducationbadges:configure'
	);

	// Add pages to navigation.
	$ADMIN->add('root', $openeducationbadges, 'location');
	$ADMIN->add('openeducationbadges', $clients);
	$ADMIN->add('openeducationbadges', $badgelist);
}
