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
 * Renderer for Open Education Badges plugin
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_openeducationbadges\output;

use core\output\plugin_renderer_base;
use local_openeducationbadges\badge;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../form/course_badge.php');

/**
 * HTML output renderer for Open Education Badges plugin
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Defer to template.
     *
     * @param badge_page $page
     *
     * @return string html for the page
     */
    public function render_badge_page($page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_openeducationbadges/badge_page', $data);
    }
}
