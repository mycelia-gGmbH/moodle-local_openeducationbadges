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
 * Badge edit page renderable
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_openeducationbadges\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use local_openeducationbadges\client;

/**
 * Class containing data for badge edit page
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge_edit_page implements renderable, templatable {
    /** @var string $badge Slug of badge to edit. */
    private $badge = '';

    /** @var client $client OEB client. */
    private $client = null;

    /** @var \context $context Context of rendering. */
    private $context = null;

    /**
     * Constructor.
     *
     * @param string $badge
     * @param client $client
     * @param \context $context
     */
    public function __construct($badge, $client, $context) {
        $this->badge = $badge;
        $this->client = $client;
        $this->context = $context;
    }

    #[\Override]
    public function export_for_template(renderer_base $output): \stdClass {
        global $CFG;

        $data = new \stdClass();

        $data->iframeurl = $this->client->get_badge_edit_iframe_url(
            $this->badge,
            $CFG->lang
        );

        return $data;
    }

    /**
     * Gets the name of the mustache template used to render the data.
     *
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'local_openeducationbadges/badge_edit_page';
    }
}
