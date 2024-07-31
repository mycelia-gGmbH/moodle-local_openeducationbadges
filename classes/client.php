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
 * Open Education Badges Client.
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace classes;

use Esirion\OpenEducationBadges\OpenEducationBadgesApi;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/Esirion/OpenEducationBadges/OpenEducationBadgesApi.php');

/**
 * Class for handling the communication to Open Education Badges API.
 *
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openeducation_client {
	/**
	 * @var $client Static openeducation_client singleton
	 */
	private static $client = null;

	/**
	 * @var array The API connections.
	 */
	private $apis = [];

	/**
	 * Returns the client instance.
	 *
	 * @param curl|null $transport
	 * @return openeducation_client The client.
	 * @throws moodle_exception
	 */
	public static function get_instance() {
		global $DB;

		if (is_null(self::$client)) {

			self::$client = new self();

			$apis = [];

			$clientrecords = $DB->get_records('local_openeducationbadges_oauth2');
			foreach ($clientrecords as $clientrecord) {
				$api = new OpenEducationBadgesApi(
					client_id: $clientrecord->client_id,
					client_secret: $clientrecord->client_secret,
					store_token: array(self::$client, 'store_token'),
					retrieve_token: array(self::$client, 'retrieve_token')
				);

				$res = $api->get_access_token();

				if (empty($res)) {
					throw new moodle_exception(get_string('oauth2problem', 'local_openeducationbadges'));
				} else {
					$apis[$clientrecord->id] = $api;
				}
			}

			self::$client->set_apis($apis);
		}

		return self::$client;
	}

	/**
	 * Get all the badges from the API for all configured clients.
	 *
	 * @return array The badges data.
	 */
	public function get_badges_all() {
		global $DB;

		$badges_data = [];

		foreach ($this->apis as $client_id => $api) {
			$issuerrecords = $DB->get_records(
				'local_openeducationbadges_config_issuers',
				array(
					'client_id' => $client_id
				),
				'',
				'issuer_id'
			);

			if (empty($issuerrecords)) {
				$badges_data = array_merge($badges_data, $api->get_all_badges());
			} else {
				foreach ($issuerrecords as $key => $value) {
					$badges_data = array_merge($badges_data, $api->get_badges($key));
				}
			}
		}

		return $badges_data;
	}

	/**
	 * Get all the badges from the API for all configured clients.
	 *
	 * @param stdClass $user
	 * @return array The badges data.
	 */
	public function get_badges_earned_all($user) {
		global $DB;

		$badges_data = [];

		foreach ($this->apis as $client_id => $api) {
			$issuerrecords = $DB->get_records(
				'local_openeducationbadges_config_issuers',
				array(
					'client_id' => $client_id
				),
				'',
				'issuer_id'
			);

			$issuer_ids = [];
			foreach ($issuerrecords as $key => $value) {
				$issuer_ids[] = $key;
			}

			$issuers = $api->get_issuers();

			foreach ($issuers as $issuer) {
				$badge_ids = [];

				if (!empty($issuer_ids) && !in_array($issuer['slug'], $issuer_ids)) {
					continue;
				}

				$assertions = $api->get_assertions($issuer['slug']);
				foreach ($assertions as $assertion) {
					if ($assertion['recipient_type'] == 'email' && $assertion['recipient_identifier'] == $user->email) {
						$badge = preg_replace('(.*\/)', '', $assertion['badge_class']);
						if (!in_array($badge, $badge_ids)) {
							$badge_ids[] = $badge;
						}
					}
				}

				$issuer_badges = $api->get_badges($issuer['slug']);
				foreach ($issuer_badges as $badge_data) {
					if (in_array($badge_data['slug'], $badge_ids)) {
						$badges_data[] = $badge_data;
					}
				}
			}
		}

		return $badges_data;
	}

	/**
	 * Issues badges to user
	 *
	 * @param stdClass $user The recipient of the badges.
	 * @param string[] $badge_ids The badge ids of the badges to be issued.
	 */
	public function issue_badges($user, $badge_ids) {
		global $DB;

		foreach ($this->apis as $client_id => $api) {
			$issuerrecords = $DB->get_records(
				'local_openeducationbadges_config_issuers',
				array(
					'client_id' => $client_id
				),
				'',
				'issuer_id'
			);

			if (empty($issuerrecords)) {
				$all_badges = $api->get_all_badges();
			} else {
				$all_badges = [];
				foreach ($issuerrecords as $key => $value) {
					$all_badges = array_merge($all_badges, $api->get_badges($key));
				}
			}

			foreach ($all_badges as $badge) {
				$badgeid = strval($badge['id']);
				if (in_array($badgeid, $badge_ids)) {
					$issuer = preg_replace('(.*\/)', '', $badge['issuer']);
					$badgeid = $badge['slug'];
					$api->issue_badge($issuer, $badgeid, $user->email);
				}
			}
		}
	}

	/**
	 * Get current active client ids
	 *
	 * @return array
	 */
	public function get_client_ids() {
		return array_keys($this->apis);
	}


	/**
	 * Get all issuer data from the API.
	 *
	 * @param int $client_id
	 * @return array The issuer data.
	 */
	public function get_issuers_all($client_id) {
		global $DB;

		$api = $this->apis[$client_id];

		return $api->get_issuers();
	}

	/**
	 * Get issuer data from the API.
	 *
	 * @param int $client_id
	 * @return array The issuer data.
	 */
	public function get_issuers($client_id) {
		global $DB;

		$api = $this->apis[$client_id];

		$issuers_all = $api->get_issuers();

		$issuerrecords = $DB->get_records(
			'local_openeducationbadges_config_issuers',
			array(
				'client_id' => $client_id
			),
			'',
			'issuer_id'
		);
		$issuer_ids = [];
		foreach ($issuerrecords as $key => $value) {
			$issuer_ids[] = $key;
		}

		$issuers = [];
		if (empty($issuer_ids)) {
			$issuers = $issuers_all;
		} else {
			foreach ($issuers_all as $issuer) {
				if (in_array($issuer['slug'], $issuer_ids)) {
					$issuers[] = $issuer;
				}
			}
		}

		return $issuers;
	}

	/**
	 * Save/remove active issuers for specific client.
	 *
	 * @param int $client_id
	 * @param array $issuers
	 */
	public function save_issuers($client_id, $issuers) {
		global $DB;

		foreach ($issuers as $key => $value) {
			if ($value) {
				$exists = $DB->record_exists(
					'local_openeducationbadges_config_issuers',
					array(
						'client_id' => $client_id,
						'issuer_id' => $key
					)
				);
				if (!$exists) {
					$DB->insert_record(
						'local_openeducationbadges_config_issuers',
						array(
							'client_id' => $client_id,
							'issuer_id' => $key
						)
					);
				}
			} else {
				$DB->delete_records(
					'local_openeducationbadges_config_issuers',
					array(
						'client_id' => $client_id,
						'issuer_id' => $key
					)
				);
			}
		}
	}

	/**
	 * Save / update client. Before saving you should test the connection!
	 *
	 * @param object $record
	 * @param bool $update Flag for update.
	 */
	public function save_client($record, $update = false) {
		global $DB;

		$api = new OpenEducationBadgesApi(
			client_id: $record->client_id,
			client_secret: $record->client_secret,
			store_token: array($this, 'store_token'),
			retrieve_token: array($this, 'retrieve_token')
		);

		if ($update) {
			$DB->update_record('local_openeducationbadges_oauth2', $record);
			$apis[$record->id] = $api;
		} else {

			$exists = $DB->record_exists(
				'local_openeducationbadges_oauth2',
				array(
					'client_id' => $record->client_id,
				)
			);

			if ($exists) {
				throw new moodle_exception(get_string('clientidexists', 'local_openeducationbadges'));
			} else {
				$id = $DB->insert_record('local_openeducationbadges_oauth2', $record);
				$apis[$id] = $api;
			}
		}
	}

	/**
	 * Returns the client data.
	 *
	 * @param string $client_id
	 * @return array Returns the client data.
	 */
	public function get_client_data($client_id) {
		global $DB;

		$client_data = $DB->get_record(
			'local_openeducationbadges_oauth2',
			array(
				'id' => $client_id
			),
			'*',
			MUST_EXIST
		);

		return $client_data;
	}

	/**
     * Tests the connection to Open Education Badges API.
     *
	  * @param string $client_id
	  * @param string $client_secret
     * @return bool Returns false on failure and true on success.
     */
    public function test_connection($client_id, $client_secret) {
		global $DB;

		$api = new OpenEducationBadgesApi(
			client_id: $client_id,
			client_secret: $client_secret,
			store_token: function () {
				return false;
			},
			retrieve_token: function () {
				return false;
			}
		);

		$res = $api->get_access_token();

		return $res;
	}

	/**
	 * Set current active API connections
	 *
	 * @param array $apis Array of currently active API connections
	 * @return null
	 */
	public function set_apis($apis) {
		$this->apis = $apis;
	}

	/**
	 * Retrieves token and expiration from database
	 *
	 * @param object $obj
	 * @return array
	 */
	function retrieve_token($obj) {
		global $DB;

		$client_id = $obj->get_client_id();

		$res = $DB->get_record(
			'local_openeducationbadges_oauth2',
			array(
				'client_id' => $client_id
			),
			'access_token',
			MUST_EXIST
		);

		return json_decode($res->access_token, JSON_OBJECT_AS_ARRAY);
	}

	/**
	 * Stores token and expiration in database
	 *
	 * @param array $token
	 * @param object $obj
	 */
	 function store_token($token, $obj) {
		global $DB;

		$client_id = $obj->get_client_id();

		$record = $DB->get_record(
			'local_openeducationbadges_oauth2',
			array(
				'client_id' => $client_id
			),
			'id,access_token',
			MUST_EXIST
		);

		$record->access_token = json_encode($token);

		$DB->update_record('local_openeducationbadges_oauth2', $record);
	}
}
