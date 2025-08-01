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
 * Open Education Badges API Interface.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Esirion\OpenEducationBadges;

use local_openeducationbadges\event\apirequest_called;
use local_openeducationbadges\event\apirequest_answered;
use local_openeducationbadges\event\apirequest_failed;

/**
 * Class for Open Education Badges API Interface.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OpenEducationBadgesApi {
    /** @var string Base URL of Open Educational Badges API */
    private $apibase = "https://api.openbadges.education/";

    /** @var string Client id */
    private $clientid = "";
    /** @var string Client secret */
    private $clientsecret = "";

    /** @var string Client username */
    private $username = "";
    /** @var string Client passwort */
    private $password = "";

    /** @var callable Function to store the received token */
    private $storetoken;
    /** @var callable Function to retrieve the stored token */
    private $retrievetoken;

    /** @var bool Flag to determine if an error should be returned */
    private $errorreturn = false;

    /**
     * Constructor.
     *
     * @param string $clientid
     * @param string $clientsecret
     * @param string $username
     * @param string $password
     * @param callable|null $storetoken
     * @param callable|null $retrievetoken
     * @param bool $errorreturn
     */
    public function __construct(
            string $clientid = "",
            string $clientsecret = "",
            string $username = "",
            string $password = "",
            callable|null $storetoken = null,
            callable|null $retrievetoken = null,
            bool $errorreturn = false
        ) {

        $this->clientid = $clientid;
        $this->clientsecret = $clientsecret;
        $this->username = $username;
        $this->password = $password;
        $this->storetoken = $storetoken ?? [$this, 'store_token_default'];
        $this->retrievetoken = $retrievetoken ?? [$this, 'retrieve_token_default'];
        $this->errorreturn = $errorreturn;
    }

    /**
     * Logs a message with a specific event type
     *
     * @param string $msg Message to log
     * @param string $type event type
     */
    public static function log($msg, $type = 'failed') {
        global $CFG;

        $eventdata = [];
        $eventdata['other'] = [];
        $eventdata['other']['info'] = $msg;

        if ($type == 'called' && $CFG->debug >= DEBUG_ALL) {
            $event = apirequest_called::create($eventdata);
            $event->trigger();
        } else if ($type == 'answered' && $CFG->debug >= DEBUG_ALL) {
            $event = apirequest_answered::create($eventdata);
            $event->trigger();
        } else if ($type == 'failed' && $CFG->debug >= DEBUG_MINIMAL) {
            $event = apirequest_failed::create($eventdata);
            $event->trigger();
        }
    }

    /**
     * Default function for storing access token
     *
     * @param string $token Information to store
     */
    private function store_token_default($token) {
        file_put_contents(
            'access_token.json',
            json_encode($token)
        );
    }

    /**
     * Default function for retrieving access token
     *
     * @return array access token
     */
    private function retrieve_token_default() {
        return json_decode(
            file_get_contents('access_token.json'),
            JSON_OBJECT_AS_ARRAY
        );
    }

    /**
     * Get access token and the corresponding expiration timestamp.
     *
     * @param bool $fresh Flag for new/fresh token
     * @return array access token and expiration timestamp
     */
    public function get_access_token(bool $fresh = false) {

        $token = call_user_func($this->retrievetoken, $this);
        if (!empty($token)) {
            // Unset token if expired.
            if ($token['token_retrieved'] + $token['token_expires'] <= time()) {
                unset($token);
            }
        }
        if (empty($token) || $fresh) {
            $token = $this->request_access_token();
        }
        return $token;
    }

    /**
     * API request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return mixed
     */
    public function api_request(string $method, string $endpoint, array $params) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headerparams = [];
        $headerparams['Accept'] = 'application/json';

        $isauth = false;
        // Authorization.
        if (strpos($endpoint, 'o/token/') === 0) {
            // Auth using client id.
            if (!empty($this->clientid) && !empty($this->clientsecret)) {
                $bearer = base64_encode($this->clientid . ":" . $this->clientsecret);
                $headerparams['Authorization'] = 'Basic ' . $bearer;
                $isauth = true;
            } else if (!empty($this->username) && !empty($this->password)) { // Auth using username and password.
                $isauth = true;
            }
        } else { // Everything else.
            $token = $this->get_access_token();
            $headerparams['Authorization'] = 'Bearer ' . $token['access_token'];
        }

        $url = $this->apibase . $endpoint;

        $payload = http_build_query($params, "", "&");

        if ($method === 'get') {

            $url .= '?'.$payload;

        } else if ($method === 'post' || $method == 'put') {

            if (!empty($params)) {
                if ($isauth) {
                    $headerparams['Content-Type'] = 'application/x-www-form-urlencoded';
                } else {
                    $headerparams['Content-Type'] = 'application/json';
                    $payload = json_encode($params);
                }
            }
            if ($method == 'post') {
                curl_setopt($ch, CURLOPT_POST, true);
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        } else if ($method === 'delete') {

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

        } else {
            return false;
        }

        // Set URL.
        curl_setopt($ch, CURLOPT_URL, $url);

        // Set Headers.
        $headers = [];
        foreach ($headerparams as $key => $val) {
            $headers[] = "$key: $val";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        self::log(
            json_encode([
                'url' => $url,
                'payload' => $payload,
                'headers' => $headers,
            ]),
            'called'
        );

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::log($response, 'answered');

        // Decode response available.
        if (!empty($response)) {
            $response = json_decode($response, true);
        }

        if ($httpcode >= 200 && $httpcode <= 300) {

            return $response;

        } else {
            self::log(
                json_encode([
                    'response' => $response,
                    'httpcode' => $httpcode,
                ]),
                'failed'
            );

            if ($this->errorreturn) {
                return $response;
            }
        }

        return false;
    }

    /**
     * API request with get method
     *
     * @param string $endpoint
     * @param array $params
     * @return mixed request response
     */
    private function get($endpoint, $params) {
        return $this->api_request('get', $endpoint, $params);
    }
    /**
     * API request with post method
     *
     * @param string $endpoint
     * @param array $params
     * @return mixed request response
     */
    private function post($endpoint, $params) {
        return $this->api_request('post', $endpoint, $params);
    }
    /**
     * API request with put method
     *
     * @param string $endpoint
     * @param array $params
     * @return mixed request response
     */
    private function put($endpoint, $params) {
        return $this->api_request('put', $endpoint, $params);
    }

    /**
     * API request to get access token
     *
     * @return mixed requested token or response
     * @throws Exception
     */
    private function request_access_token() {

        $retrievedtime = time();

        if (!empty($this->clientid) && !empty($this->clientsecret)) {
            $response = $this->post('o/token/', [
                'grant_type' => 'client_credentials',
                'scope' => 'rw:profile rw:issuer rw:backpack',
                'client_id' => $this->clientid,
                'client_secret' => $this->clientsecret,
            ]);
        } else if (!empty($this->username) && !empty($this->password)) {
            $response = $this->post('o/token/', [
                'grant_type' => 'password',
                'scope' => 'rw:profile rw:issuer rw:backpack',
                'client_id' => 'public',
                'username' => $this->username,
                'password' => $this->password,
            ]);
        } else {
            throw new \Exception('No API credentials given');
        }

        if (!empty($response)) {
            if (empty($response['error'])) {
                $token = [
                    'access_token' => $response['access_token'],
                    'token_expires' => $response['expires_in'],
                    'token_retrieved' => $retrievedtime,
                ];

                call_user_func($this->storetoken, $token, $this);

                return $token;
            } else if ($this->errorreturn) {
                return $response;
            }
        }
    }

    /**
     * API request to get all badges
     *
     * @return mixed request response
     */
    public function get_all_badges() {
        $response = $this->get("v1/issuer/all-badges", []);
        return $response;
    }

    /**
     * API request to get all issuers
     *
     * @return mixed request response
     */
    public function get_issuers() {
        $response = $this->get("v1/issuer/issuers", []);
        return $response;
    }

    /**
     * API request to get all badges of this issuer
     *
     * @param string $issuer
     * @return mixed request response
     */
    public function get_badges($issuer) {
        $response = $this->get("v1/issuer/issuers/$issuer/badges", []);
        return $response;
    }

    /**
     * API request to issue the badge from this issuer to the recipient
     *
     * @param string $issuer
     * @param string $badge
     * @param string $recipient
     * @return mixed request response
     */
    public function issue_badge($issuer, $badge, $recipient) {

        $response = $this->post("v1/issuer/issuers/$issuer/badges/$badge/assertions", [
            "badge_class" => $badge,
            "create_notification" => true,
            "evidence_items" => [],
            "issuer" => $issuer,
            "narrative" => "",
            "recipient_identifier" => $recipient,
            "recipient_type" => "email",
        ]);

        return $response;
    }

    /**
     * API request to get assertions of this issuer
     *
     * @param string $issuer
     * @return mixed request response
     */
    public function get_assertions($issuer) {
        $response = $this->get("v1/issuer/issuers/$issuer/assertions", []);
        return $response;
    }

    /**
     * Get client id
     *
     * @return string client id
     */
    public function get_client_id() {
        return $this->clientid;
    }

    /**
     * Sets if errors should be returned
     *
     * @param bool $errorreturn New setting
     */
    public function set_error_return($errorreturn) {
        $this->errorreturn = $errorreturn;
    }
}
