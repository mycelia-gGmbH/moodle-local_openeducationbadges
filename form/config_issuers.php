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
 * Config form for Issuers of API authentication.
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Plugin config / Issuers form.
 *
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oeb_config_issuers_form extends moodleform {

	private $issuers = [];

	/**
	 * @param array $issuers
	 */
	public function __construct($actionurl, $issuers) {
		$this->issuers = $issuers;
		parent::__construct($actionurl);
	}

	public function definition() {

		$mform = $this->_form;

		// Add header for the client block.
		$mform->addElement('header', 'oebissuersheader', get_string('activeissuers', 'local_openeducationbadges'));

		foreach ($this->issuers as $id => $name) {
			$mform->addElement('advcheckbox', $id, $name, get_string('active', 'local_openeducationbadges'));
		}

		$submitlabel = null; // Default.
		$this->add_action_buttons(true, $submitlabel);
	}

	public function validation($data, $files) {
		$errors = parent::validation($data, $files);

		return $errors;
	}

	public function get_data() {
		$data = parent::get_data();

		return $data;
	}
}
