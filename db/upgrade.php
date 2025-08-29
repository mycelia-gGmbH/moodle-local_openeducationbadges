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
 * See https://docs.moodle.org/dev/Upgrade_API for details.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
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

        upgrade_plugin_savepoint(true, 2024112700, 'local', 'openeducationbadges');
    }

    if ($oldversion < 2025042500) {
        $coursebadgetable = new xmldb_table('local_oeb_course_badge');
        $activityidfield = new xmldb_field('activityid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($coursebadgetable, $activityidfield)) {
            $dbman->add_field($coursebadgetable, $activityidfield);
        }

        upgrade_plugin_savepoint(true, 2025042500, 'local', 'openeducationbadges');
    }

    if ($oldversion < 2025050500) {
        $oauthtable = new xmldb_table('local_oeb_oauth2');
        $statusfield = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        if (!$dbman->field_exists($oauthtable, $statusfield)) {
            $dbman->add_field($oauthtable, $statusfield);
        }

        upgrade_plugin_savepoint(true, 2025050500, 'local', 'openeducationbadges');
    }

    if ($oldversion < 2025080500) {
        $queuetable = new xmldb_table('local_oeb_badge_queue');
        $queuetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $queuetable->add_field('badgeid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $queuetable->add_field('user_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        $queuetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($queuetable)) {
            $dbman->create_table($queuetable);
        }

        upgrade_plugin_savepoint(true, 2025080500, 'local', 'openeducationbadges');
    }

    return true;
}
