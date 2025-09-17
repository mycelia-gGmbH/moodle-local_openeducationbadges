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
 * Config form for OAuth2 API authentication.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_openeducationbadges\client;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Plugin config / Authentication form.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oeb_config_oauth2_form extends moodleform {
    /** @var string JSON with the token information */
    private $token = null;

    /**
     * Add elements to form.
     */
    public function definition() {

        $mform = $this->_form;

        // Add header for the client block.
        $mform->addElement('header', 'oebclientheader', get_string('client', 'local_openeducationbadges'));

        // Add fields for client.
        $mform->addElement('text', 'client_name', get_string('clientname', 'local_openeducationbadges'), ['size' => 60]);
        $mform->setType('client_name', PARAM_NOTAGS);
        $mform->addRule('client_name', null, 'required');

        $mform->addElement('text', 'client_id', get_string('clientid', 'local_openeducationbadges'), ['size' => 60]);
        $mform->setType('client_id', PARAM_NOTAGS);
        $mform->addRule('client_id', null, 'required');
        $mform->addHelpButton('client_id', 'clientid', 'local_openeducationbadges');

        $mform->addElement('text', 'client_secret', get_string('clientsecret', 'local_openeducationbadges'), ['size' => 60]);
        $mform->setType('client_secret', PARAM_NOTAGS);
        $mform->addRule('client_secret', null, 'required');
        $mform->addHelpButton('client_secret', 'clientsecret', 'local_openeducationbadges');

        $submitlabel = null; // Default.
        $this->add_action_buttons(true, $submitlabel);
    }

    /**
     * Validate submitted data
     *
     * @param array $data Array of submitted data
     * @param array $files Array of uploaded files
     * @return array errors
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($errors)) {
            try {
                $client = client::get_instance();
                $token = $client->test_connection($data['client_id'], $data['client_secret']);

                if (empty($token)) {
                    $errors['client_id'] = get_string('invalidclientsecret', 'local_openeducationbadges');
                    $errors['client_secret'] = get_string('invalidclientsecret', 'local_openeducationbadges');
                } else {
                    $this->token = json_encode($token);
                }
            } catch (Exception $e) {
                $errors['client_id'] = get_string('invalidclientsecret', 'local_openeducationbadges');
                $errors['client_secret'] = get_string('invalidclientsecret', 'local_openeducationbadges');
            }
        }

        return $errors;
    }

    /**
     * Return submitted data or NULL
     *
     * @return stdClass|null
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            $data->access_token = $this->token;
        }

        return $data;
    }
}
