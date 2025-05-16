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
 * Plugin configuration page.
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use classes\openeducation_client;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/form/config_oauth2.php');
require_once(__DIR__ . '/form/config_issuers.php');
require_once(__DIR__ . '/classes/client.php');

$context = context_system::instance();
$action = optional_param('action', 'list', PARAM_TEXT);
$clientid = optional_param('id', 0, PARAM_INT);
$urlparams = $action == 'list' ? array() : array('action' => $action, 'id' => $clientid);

require_login();
require_capability('local/openeducationbadges:configure', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/openeducationbadges/config.php', $urlparams));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('oeb', 'local_openeducationbadges'));

$clientsurl = new moodle_url('/local/openeducationbadges/config.php');

echo $OUTPUT->header();

switch ($action) {
	case 'edit':
		echo $OUTPUT->heading(get_string('clientadd', 'local_openeducationbadges'), 2);

		$clientrecord = new stdClass;
		$newRecord = true;

		if ($clientid) {
			try {
				$client = openeducation_client::get_instance();
				$clientrecord = $client->get_client_data($clientid);
				if (!$clientrecord->status) {
					echo $OUTPUT->notification(get_string('connectionsevered', 'local_openeducationbadges'), 'notifyproblem');
				}
			} catch (Exception $e) {}

			$newRecord = false;
		}

		$mform = new oeb_config_oauth2_form($PAGE->url);
		$mform->set_data($clientrecord);

		if ($mform->is_cancelled()) {
			redirect($clientsurl);
		} else if ($data = $mform->get_data()) {

			try {
				$client = openeducation_client::get_instance();

				if ($newRecord) {
					$client->save_client($data);
				} else {
					$data->id = $clientrecord->id;
					$client->save_client($data, true);
				}

				redirect($clientsurl, get_string('clientsaved', 'local_openeducationbadges'));
			} catch (Exception $e) {
				echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
				$mform->display();
			}
		} else {
			$mform->display();
		}

		break;
	case 'issuers':
		echo $OUTPUT->heading(get_string('issuerscustomize', 'local_openeducationbadges'), 2);

		$all_issuers = [];
		$issuers = [];
		try {
			$client = openeducation_client::get_instance();
			$clientrecord = $client->get_client_data($clientid);
			if (!$clientrecord->status) {
				echo $OUTPUT->notification(get_string('connectionsevered', 'local_openeducationbadges'), 'notifyproblem');
			}
			$all_issuers = $client->get_issuers_all($clientid);
			$issuers = $client->get_issuers($clientid);
		} catch (Exception $e) {}

		$issuers_arr = [];
		foreach ($all_issuers as $issuer) {
			$issuers_arr[$issuer['slug']] = $issuer['name'];
		}
		$mform = new oeb_config_issuers_form($PAGE->url, $issuers_arr);

		$issuers_arr = [];
		foreach ($issuers as $issuer) {
			$issuers_arr[$issuer['slug']] = 1;
		}
		$mform->set_data($issuers_arr);

		if ($mform->is_cancelled()) {
			redirect($clientsurl);
		} else if ($data = $mform->get_data()) {
			$data_arr = json_decode(json_encode($data), true);
			$index = array_search('submitbutton', array_keys($data_arr));
			array_splice($data_arr, $index, 1);

			try {
				$client = openeducation_client::get_instance();
				$client->save_issuers( $clientid, $data_arr);
			} catch (Exception $e) {}

			redirect($clientsurl, get_string('issuerssaved', 'local_openeducationbadges'));
		} else {
			$mform->display();
		}

		break;
	case 'delete':
		if (confirm_sesskey()) {
			$DB->delete_records('local_oeb_oauth2', array('id' => $clientid));
		}

		redirect($clientsurl, get_string('clientdeleted', 'local_openeducationbadges'));

		break;
	default:
		echo $OUTPUT->heading(get_string('clients', 'local_openeducationbadges'), 2);

		$table = new html_table();
		$table->id = 'oeb-clients';
		$table->attributes = array('class' => 'oeb-clients-table');
		$table->head = array(
			get_string('status', 'local_openeducationbadges'),
			get_string('clientname', 'local_openeducationbadges'),
			get_string('clientid', 'local_openeducationbadges'),
			get_string('activeissuers', 'local_openeducationbadges'),
			get_string('actions', 'moodle')
		);

		try {
			$client = openeducation_client::get_instance();
			$clientrecords = $DB->get_records('local_oeb_oauth2');
		} catch (Exception $e) {}

		if ($client->exist_severed_connections()) {
			echo $OUTPUT->notification(get_string('connectionproblemgeneral', 'local_openeducationbadges'), 'notifyproblem');
		}

		$editicon = new pix_icon('t/edit', get_string('edit'));
		$editissuersicon = new pix_icon('t/editinline', get_string('editissuers', 'local_openeducationbadges'));
		$deleteicon = new pix_icon('t/delete', get_string('delete'));
		$active = $OUTPUT->pix_icon('agreed', get_string('active', 'local_openeducationbadges'), 'tool_policy');
		$inactive = $OUTPUT->pix_icon('declined', get_string('oauth2problem', 'local_openeducationbadges'), 'tool_policy');

		foreach ($clientrecords as $record) {
			$record_id = $record->id;
			$client_id = $record->client_id;
			$client_name = $record->client_name;

			$row = new html_table_row();

			$editurl = new moodle_url('/local/openeducationbadges/config.php?action=edit&id=' . $record_id);
			$editaction = $OUTPUT->action_icon($editurl, $editicon);

			$editissuersurl = new moodle_url('/local/openeducationbadges/config.php?action=issuers&id=' . $record_id);
			$editissuersaction = $OUTPUT->action_icon($editissuersurl, $editissuersicon);

			$deleteurl = new moodle_url('/local/openeducationbadges/config.php?action=delete&id=' . $record_id . '&sesskey=' . sesskey());
			$deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('deleteclientconfirm', 'local_openeducationbadges')));

			$icons = new html_table_cell($editaction . ' ' . $editissuersaction . ' ' . $deleteaction);

			$issuers = [];
			$issuer_names = '';

			if ($record->status) {
				$status = $active;
				try {
					$issuers =  $client->get_issuers($record_id);
				} catch (Exception $e) {}
			} else {
				$status = $inactive;
				$icons = new html_table_cell($editaction . ' ' . $deleteaction);
			}

			foreach ($issuers as $issuer) {
				if(empty($issuer_names)) {
					$issuer_names .= $issuer['name'];
				} else {
					$issuer_names .= '<br>' . $issuer['name'];
				}
			}

			$row->cells = array(
				$status,
				$client_name,
				$client_id,
				$issuer_names,
				$icons
			);

			$table->data[] = $row;
		}

		echo html_writer::table($table);

		$url = $CFG->wwwroot . '/local/openeducationbadges/config.php?action=edit&id=0';
		echo '<div class="actionbuttons oeb-actions">' . $OUTPUT->single_button($url, get_string('addnew', 'local_openeducationbadges'), 'get') . '</div>';
}

echo $OUTPUT->footer();
