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
 * Open Education Badges Client.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace classes;

use Esirion\OpenEducationBadges\OpenEducationBadgesApi;

use moodle_exception;
use context_system;
use core\message\message;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/Esirion/OpenEducationBadges/OpenEducationBadgesApi.php');

/**
 * Class for handling the communication to Open Education Badges API.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openeducation_client {
    /** @var $client Static openeducation_client singleton */
    private static $client = null;

    /** @var array The API connections. */
    private $apis = [];

    /**
     * Returns the client instance.
     *
     * @return openeducation_client The client.
     */
    public static function get_instance() {
        global $DB;

        if (is_null(self::$client)) {

            self::$client = new self();

            $apis = [];

            $clientrecords = $DB->get_records('local_oeb_oauth2', ['status' => 1]);
            foreach ($clientrecords as $clientrecord) {
                $api = new OpenEducationBadgesApi(
                    clientid: $clientrecord->client_id,
                    clientsecret: $clientrecord->client_secret,
                    storetoken: [self::$client, 'store_token'],
                    retrievetoken: [self::$client, 'retrieve_token'],
                    errorreturn: true
                );

                $res = $api->get_access_token(true);
                $api->set_error_return(false);

                if (!empty($res)) {
                    if (!empty($res['error']) && ($res['error'] == 'invalid client_id'
                            || $res['error'] == 'invalid client_secret')) {
                        $clientrecord->status = 0;
                        $DB->update_record('local_oeb_oauth2', $clientrecord);

                        self::$client->notify_connection_problem($clientrecord->client_name);
                    } else {
                        $apis[$clientrecord->id] = $api;
                    }
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

        $badgesdata = [];

        foreach ($this->apis as $clientid => $api) {
            $issuerrecords = $DB->get_records(
                'local_oeb_config_issuers',
                [
                    'client_id' => $clientid,
                ],
                '',
                'issuer_id'
            );

            if (empty($issuerrecords)) {
                $badges = $api->get_all_badges();
                if ($badges) {
                    $badgesdata = array_merge($badgesdata, $badges);
                }
            } else {
                foreach ($issuerrecords as $key => $value) {
                    $badges = $api->get_badges($key);
                    if ($badges) {
                        $badgesdata = array_merge($badgesdata, $badges);
                    }
                }
            }
        }

        return $badgesdata;
    }

    /**
     * Get all the badges from the API for all configured clients.
     *
     * @param stdClass $user
     * @return array The badges data.
     */
    public function get_badges_earned_all($user) {
        global $DB;

        $badgesdata = [];

        foreach ($this->apis as $clientid => $api) {
            $issuerrecords = $DB->get_records(
                'local_oeb_config_issuers',
                [
                    'client_id' => $clientid,
                ],
                '',
                'issuer_id'
            );

            $issuerids = [];
            foreach ($issuerrecords as $key => $value) {
                $issuerids[] = $key;
            }

            $issuers = $api->get_issuers();

            foreach ($issuers as $issuer) {
                $badgeids = [];

                if (!empty($issuerids) && !in_array($issuer['slug'], $issuerids)) {
                    continue;
                }

                $assertions = $api->get_assertions($issuer['slug']);
                foreach ($assertions as $assertion) {
                    if ($assertion['recipient_type'] == 'email' && $assertion['recipient_identifier'] == $user->email) {
                        $badge = preg_replace('(.*\/)', '', $assertion['badge_class']);
                        if (!in_array($badge, $badgeids)) {
                            $badgeids[] = $badge;
                        }
                    }
                }

                $issuerbadges = $api->get_badges($issuer['slug']);
                foreach ($issuerbadges as $badgedata) {
                    if (in_array($badgedata['slug'], $badgeids)) {
                        $badgesdata[] = $badgedata;
                    }
                }
            }
        }

        return $badgesdata;
    }

    /**
     * Issue badge to user
     *
     * @param int $userid The id of the recipient of the badge.
     * @param int $badgeid The badge id of the badge to be issued.
     * @throws moodle_exception
     */
    public function issue_badge($userid, $badgeid) {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid]);

        foreach ($this->apis as $clientid => $api) {
            $issuerrecords = $DB->get_records(
                'local_oeb_config_issuers',
                [
                    'client_id' => $clientid,
                ],
                '',
                'issuer_id'
            );

            if (empty($issuerrecords)) {
                $allbadges = $api->get_all_badges();
            } else {
                $allbadges = [];
                foreach ($issuerrecords as $key => $value) {
                    $badges = $api->get_badges($key);
                    if ($badges) {
                        $allbadges = array_merge($allbadges, $badges);
                    }
                }
            }

            foreach ($allbadges as $badge) {
                if ($badgeid == $badge['id']) {
                    $issuer = preg_replace('(.*\/)', '', $badge['issuer']);
                    $res = $api->issue_badge($issuer, $badge['slug'], $user->email);

                    if (empty($res) || !empty($res['error'])) {
                        throw new moodle_exception(get_string('issuebadgefailed', 'local_openeducationbadges'));
                    }
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
     * @param int $clientid
     * @return array The issuer data.
     */
    public function get_issuers_all($clientid) {
        global $DB;

        $api = $this->apis[$clientid];

        return $api->get_issuers();
    }

    /**
     * Get issuer data from the API.
     *
     * @param int $clientid
     * @return array The issuer data.
     */
    public function get_issuers($clientid) {
        global $DB;

        $api = $this->apis[$clientid];

        $issuersall = $api->get_issuers();

        $issuerrecords = $DB->get_records(
            'local_oeb_config_issuers',
            [
                'client_id' => $clientid,
            ],
            '',
            'issuer_id'
        );
        $issuerids = [];
        foreach ($issuerrecords as $key => $value) {
            $issuerids[] = $key;
        }

        $issuers = [];
        if (empty($issuerids)) {
            $issuers = $issuersall;
        } else {
            foreach ($issuersall as $issuer) {
                if (in_array($issuer['slug'], $issuerids)) {
                    $issuers[] = $issuer;
                }
            }
        }

        return $issuers;
    }

    /**
     * Save/remove active issuers for specific client.
     *
     * @param int $clientid
     * @param array $issuers
     */
    public function save_issuers($clientid, $issuers) {
        global $DB;

        foreach ($issuers as $key => $value) {
            if ($value) {
                $exists = $DB->record_exists(
                    'local_oeb_config_issuers',
                    [
                        'client_id' => $clientid,
                        'issuer_id' => $key,
                    ]
                );
                if (!$exists) {
                    $DB->insert_record(
                        'local_oeb_config_issuers',
                        [
                            'client_id' => $clientid,
                            'issuer_id' => $key,
                        ]
                    );
                }
            } else {
                $DB->delete_records(
                    'local_oeb_config_issuers',
                    [
                        'client_id' => $clientid,
                        'issuer_id' => $key,
                    ]
                );
            }
        }
    }

    /**
     * Save / update client. Before saving you should test the connection!
     *
     * @param object $record
     * @param bool $update Flag for update.
     * @throws moodle_exception
     */
    public function save_client($record, $update = false) {
        global $DB;

        $api = new OpenEducationBadgesApi(
            clientid: $record->client_id,
            clientsecret: $record->client_secret,
            storetoken: [$this, 'store_token'],
            retrievetoken: [$this, 'retrieve_token']
        );

        if ($update) {
            $record->status = 1;
            $DB->update_record('local_oeb_oauth2', $record);
            $apis[$record->id] = $api;
        } else {

            $exists = $DB->record_exists(
                'local_oeb_oauth2',
                [
                    'client_id' => $record->client_id,
                ]
            );

            if ($exists) {
                throw new moodle_exception(get_string('clientidexists', 'local_openeducationbadges'));
            } else {
                $id = $DB->insert_record('local_oeb_oauth2', $record);
                $apis[$id] = $api;
            }
        }
    }

    /**
     * Returns the client data.
     *
     * @param string $clientid
     * @return array Returns the client data.
     */
    public function get_client_data($clientid) {
        global $DB;

        $clientdata = $DB->get_record(
            'local_oeb_oauth2',
            [
                'id' => $clientid,
            ],
            '*',
            MUST_EXIST
        );

        return $clientdata;
    }

    /**
     * Tests the connection to Open Education Badges API.
     *
     * @param string $clientid
     * @param string $clientsecret
     * @return bool Returns false on failure and true on success.
     */
    public function test_connection($clientid, $clientsecret) {
        $api = new OpenEducationBadgesApi(
            clientid: $clientid,
            clientsecret: $clientsecret,
            storetoken: function () {
                return false;
            },
            retrievetoken: function () {
                return false;
            }
        );

        $res = $api->get_access_token();
        if (empty($res) || !empty($res['error'])) {
            return false;
        }

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
    public function retrieve_token($obj) {
        global $DB;

        $clientid = $obj->get_client_id();

        $res = $DB->get_record(
            'local_oeb_oauth2',
            [
                'client_id' => $clientid,
            ],
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
    public function store_token($token, $obj) {
        global $DB;

        $clientid = $obj->get_client_id();

        $record = $DB->get_record(
            'local_oeb_oauth2',
            [
                'client_id' => $clientid,
            ],
            'id,access_token',
            MUST_EXIST
        );

        $record->access_token = json_encode($token);

        $DB->update_record('local_oeb_oauth2', $record);
    }

    /**
     * Do severed/broken API connections currently exist?
     *
     * @return boolean Returns true if they exist and false if not
     */
    public function exist_severed_connections() {
        global $DB;

        return $DB->record_exists('local_oeb_oauth2', ['status' => 0]);
    }

    /**
     * Notify the admins of a connection problem
     *
     * @param string $name The name of the problematic connection.
     * @return null
     */
    public function notify_connection_problem($name) {
        $messageconnectionproblem = new \core\message\message();
        $messageconnectionproblem->component = 'local_openeducationbadges';
        $messageconnectionproblem->name = 'connectionproblem';
        $messageconnectionproblem->userfrom = \core_user::get_noreply_user();
        $messageconnectionproblem->subject = get_string(
            'connectionproblemsubject',
            'local_openeducationbadges',
            ['name' => $name]
        );
        $messageconnectionproblem->fullmessage = get_string(
            'connectionproblembody',
            'local_openeducationbadges',
            ['name' => $name]
        );
        $messageconnectionproblem->fullmessagehtml = get_string(
            'connectionproblembody',
            'local_openeducationbadges',
            ['name' => $name]
        );
        $messageconnectionproblem->fullmessageformat = FORMAT_MARKDOWN;
        $messageconnectionproblem->notification = 1;

        $capability = 'local/openeducationbadges:configure';
        $context = context_system::instance();

        $managerusers = get_admins();
        foreach ($managerusers as $manageruser) {
            if (has_capability($capability, $context, $manageruser)) {
                $messageconnectionproblem->userto = $manageruser;
                message_send($messageconnectionproblem);
            }
        }
    }
}
