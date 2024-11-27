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
 * See https://docs.moodle.org/dev/Upgrade_API for details.
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The upgrade function for local_openeducationbadges.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_local_openeducationbadges_upgrade($oldversion) {
	global $DB;

	$dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

	if ($oldversion < 2024112700) {
		$oauthtable = new xmldb_table('local_openeducationbadges_oauth2');
		$dbman->rename_table($oauthtable, 'local_oeb_oauth2');

		$coursebadgetable = new xmldb_table('local_openeducationbadges_course_badge');
		$dbman->rename_table($coursebadgetable, 'local_oeb_course_badge');

		$configissuerstable = new xmldb_table('local_openeducationbadges_config_issuers');
		$dbman->rename_table($configissuerstable, 'local_oeb_config_issuers');
	}

	return true;
}
