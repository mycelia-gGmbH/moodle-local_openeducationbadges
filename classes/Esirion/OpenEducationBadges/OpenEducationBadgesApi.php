<?php


namespace Esirion\OpenEducationBadges;

class OpenEducationBadgesApi {

	private $api_base = "https://api.openbadges.education/";

	private $client_id = "";
	private $client_secret = "";

	private $username = "";
	private $password = "";

	private $store_token;
	private $retrieve_token;

	public function __construct(
			string $client_id = "",
			string $client_secret = "",
			string $username = "",
			string $password = "",
			callable $store_token = null,
			callable $retrieve_token = null
		) {

		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->username = $username;
		$this->password = $password;
		$this->store_token = $store_token ?? [$this, 'store_token_default'];
		$this->retrieve_token = $retrieve_token ?? [$this, 'retrieve_token_default'];
	}

	public static function log($msg, $level = 'error') {
		// if (empty(self::$logger)) {
		// 	self::$logger = wc_get_logger();
		// }

		// self::$logger->log(
		// 	$level,
		// 	$msg,
		// 	[
		// 		'source' => $source
		// 	]
		// );

		// var_export(['log', $level, $msg]);
	}

	private function store_token_default($token) {
		file_put_contents(
			'access_token.json',
			json_encode($token)
		);
	}

	private function retrieve_token_default() {
		return json_decode(
			file_get_contents('access_token.json'),
			JSON_OBJECT_AS_ARRAY
		);
	}

	/**
	 * Get access token and the corresponding expiration timestamp.
	 *
	 * @return array access token and expiration timestamp
	 */
	public function get_access_token() {

		$token = call_user_func($this->retrieve_token, $this);
		if (!empty($token)) {
			// unset token if expired
			if ($token['token_retrieved'] + $token['token_expires'] <= time()) {
				unset($token);
			}
		}
		if (empty($token)) {
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
		curl_setopt($ch, CURLOPT_HEADER, False);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);

		$headerParams = [];
		$headerParams['Accept'] = 'application/json';


		$is_auth = false;
		// authorization
		if (strpos($endpoint, 'o/token/') === 0) {
			// auth using client_id
			if (!empty($this->client_id) && !empty($this->client_secret)) {
				$bearer = base64_encode($this->client_id . ":" . $this->client_secret);
				$headerParams['Authorization'] = 'Basic ' . $bearer;
				$is_auth = true;
			// auth using username and password
			} else if (!empty($this->username) && !empty($this->password)) {
				$is_auth = true;
			}
		// everything else
		} else {
			$token = $this->get_access_token();
			$headerParams['Authorization'] = 'Bearer ' . $token['access_token'];
		}


		$url = $this->api_base . $endpoint;

		$payload = http_build_query($params, "", "&");

		if ($method === 'get') {

			$url .= '?'.$payload;

		} else if ($method === 'post' || $method == 'put') {

			if (!empty($params)) {
				if ($is_auth) {
					$headerParams['Content-Type'] = 'application/x-www-form-urlencoded';
				} else {
					$headerParams['Content-Type'] = 'application/json';
					$payload = json_encode($params);
				}
			}
			if ($method == 'post') {
				curl_setopt($ch, CURLOPT_POST, True);
			} else {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			}

			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

		} else if ($method === 'delete') {

			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

		} else {
			return false;
		}

		// set URL
		curl_setopt($ch, CURLOPT_URL, $url);

		// set Headers
		$headers = [];
		foreach ($headerParams as $key => $val) {
			$headers[] = "$key: $val";
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		self::log(
			'Request: ' . var_export(
				[
					'url'=>$url,
					'payload'=>$payload,
					'headers'=>$headers
				],
				true
			),
			'info'
		);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		self::log('Response: ' . var_export(
			$response,
			'info'
		));

		// decode response available
		if (!empty($response)) {
			$response = json_decode($response, true);
		}

		if ($http_code >= 200 && $http_code <= 300) {

			return $response;

		} else {
			self::log(
				'API Error: ' . var_export(
					[
						'response' => $response,
						'http_code' => $http_code
					],
					true
				),
				'error'
			);
		}

		return false;
	}
	private function get($endpoint, $params) {
		return $this->api_request('get', $endpoint, $params);
	}
	private function post($endpoint, $params) {
		return $this->api_request('post', $endpoint, $params);
	}
	private function put($endpoint, $params) {
		return $this->api_request('put', $endpoint, $params);
	}

	private function request_access_token() {

		$retrieved_time = time();

		if (!empty($this->client_id) && !empty($this->client_secret)) {
			$response = $this->post('o/token/', [
				'grant_type' => 'client_credentials',
				'scope' => 'rw:profile rw:issuer rw:backpack',
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
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

			$token = array(
				'access_token' => $response['access_token'],
				'token_expires' => $response['expires_in'],
				'token_retrieved' => $retrieved_time,
			);

			call_user_func($this->store_token, $token, $this);

			return $token;
		}
	}

	public function get_all_badges() {
		$response = $this->get("v1/issuer/all-badges", []);
		return $response;
	}

	public function get_issuers() {
		$response = $this->get("v1/issuer/issuers", []);
		return $response;
	}

	public function get_badges($issuer) {
		$response = $this->get("v1/issuer/issuers/$issuer/badges", []);
		return $response;
	}

	public function issue_badge($issuer, $badge, $recipient) {

		$response = $this->post("v1/issuer/issuers/$issuer/badges/$badge/assertions", [
			"badge_class" => $badge,
			"create_notification" => true,
			"evidence_items" => [],
			"issuer" => $issuer,
			"narrative" => "",
			"recipient_identifier" => $recipient,
			"recipient_type" => "email"
		]);

		return $response;
	}

	public function get_assertions($issuer) {
		$response = $this->get("v1/issuer/issuers/$issuer/assertions", []);
		return $response;
	}

	public function get_client_id() {
		return $this->client_id;
	}
}
