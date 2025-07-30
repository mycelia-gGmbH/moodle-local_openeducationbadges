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
 * Open Education Badges Install script. See https://docs.moodle.org/dev/Upgrade_API for details.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install function. Makes sure path for certificates exists.
 *
 * @return boolean
 **/
function xmldb_local_openeducationbadges_install() {
    global $CFG, $DB;
    $newpkidir = $CFG->dataroot . '/local_openeducationbadges/pki/';

    if (!is_dir($newpkidir)) {
        mkdir($newpkidir, $CFG->directorypermissions, true);
    }

    return true;
}
