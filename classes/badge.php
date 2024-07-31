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

namespace classes;

use classes\openeducation_client;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/client.php');

/**
 * Class for a Open Education Badge.
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openeducation_badge {

	/**
    * Completion type associated to courses
    */
   const COMPLETION_TYPE_COURSE = 1;

	/**
    * @var array $badgecompletiontype Completion type text associating array.
    */
   private static $badgecompletiontype = array(
		self::COMPLETION_TYPE_COURSE => 'course',
	);

	/**
    * @var datetime The badge creation time as a datetime
    */
   private $created_at = null;

	/**
    * @var string The badge creator as a string
    */
	private $created_by = '';

	/**
    * @var int The id of the badge
    */
   private $id = null;

	/**
    * @var string The name of the badge
    */
	private $name = '';

	/**
    * @var string The badge image url
    */
   private $image = null;

	/**
    * @var string The badge slug
    */
	private $slug = null;

	/**
    * @var string The text of badge criteria.
    */
   private $criteria_text = '';

   /**
    * @var string The URL of the badge criteria.
    */
   private $criteria_url = null;

	/**
    * @var int The recipient count of the badge
    */
	private $recipient_count = null;

	/**
    * @var string The badge description
    */
   private $description = '';

	/**
	 * @var array The alignment of the badge.
	 */
	private $alignment = array();

	/**
    * @var string[] The tags of the badge.
    */
	private $tags = array();

	/**
    * @var array Badge expiration time as an array with int amount and string duration
    */
	private $expires = array();

	/**
    * @var string The URL of the badge source.
    */
	private $source_url = null;

	/**
    * @var string The badge issuer name
    */
	private $issuer_name = '';

	/**
    * @var string The badge issuer
    */
	private $issuer = null;

	/**
    * @var string The URL of the badge.
    */
	private $badge_url = null;


	/**
	 * Get text representation of the completion type.
	 *
	 * @param int $type Completion type
	 * @return string Text representation
	 */
	public static function get_completion_type_text($type) {
		return self::$badgecompletiontype[$type];
	}

	/**
	 * @param int $course_id The course id.
	 * @return int Return completion method else false.
	 */
	public function is_course_awarding(int $course_id) {
		global $DB;

		$record = $DB->get_record(
			'local_openeducationbadges_course_badge',
			array(
				'courseid' => $course_id,
				'badgeid' => $this->get_id()
			),
			'completion_method'
		);

		if ($record) {
			return $record;
		} else {
			return 0;
		}
	}

	/**
	 * @param int $course_id The course id.
	 * @return int Return completion method else false.
	 */
	public function set_course_awarding(int $course_id, int $completion_method) {
		global $DB;

		return $DB->insert_record(
			'local_openeducationbadges_course_badge',
			array(
				'courseid' => $course_id,
				'badgeid' => $this->get_id(),
				'completion_method' => $completion_method
			)
		);
	}

	/**
    * Gets and returns the Open Education badges.
    *
    * @return openeducation_badge[] The badges.
	 * @throws moodle_exception
    */
	public static function get_badges() {
		$badges = [];

		try {
			$client = openeducation_client::get_instance();
			$badges_data = $client->get_badges_all();
			foreach ($badges_data as $badge_data) {
				$badge = self::get_instance_from_array($badge_data);
				$badges[$badge->get_id()] = $badge;
			}
		} catch (Exception $e) {}

		usort($badges, fn($a, $b) => $a->get_name() <=> $b->get_name());

		return $badges;
	}

	/**
    * Gets and returns the earned Open Education badges by user.
    *
	 * @param stdClass $user
    * @return openeducation_badge[] The badges.
    */
	 public static function get_earned_badges($user) {
		global $DB;

		$badges = [];

		try {
			$client = openeducation_client::get_instance();
			$badges_data = $client->get_badges_earned_all($user);
			foreach ($badges_data as $badge_data) {
				$badge = self::get_instance_from_array($badge_data);
				$badges[$badge->get_id()] = $badge;
			}
		} catch (Exception $e) {}

		usort($badges, fn($a, $b) => $a->get_name() <=> $b->get_name());

		return $badges;
	}

	/**
    * Creates a new instance of the class from an array.
    *
    * @param array $arr The badge data as an associative array
    * @return openeducation_badge The badge.
    */
	public static function get_instance_from_array($arr) {
		$obj = new self();

		// These should always exist.
		$obj->set_id($arr['id']);
		$obj->set_name($arr['name']);
		$obj->set_description($arr['description']);
		$obj->set_criteria_text($arr['criteria_text']);
		$obj->set_image($arr['image']);
		$obj->set_slug($arr['slug']);
		$obj->set_created_by($arr['created_by']);
		$obj->set_created_at($arr['created_at']);
		$obj->set_badge_url($arr['json']['id']);

		if (isset($arr['criteria_url'])) {
			$obj->set_criteria_url($arr['criteria_url']);
		}
		if (isset($arr['recipient_count'])) {
			$obj->set_recipient_count($arr['recipient_count']);
		}
		if (isset($arr['alignment'])) {
			$obj->set_alignment($arr['alignment']);
		}
		if (isset($arr['tags'])) {
			$obj->set_tags($arr['tags']);
		}
		if (isset($arr['expires'])) {
			$obj->set_expires($arr['expires']);
		}
		if (isset($arr['source_url'])) {
			$obj->set_source_url($arr['source_url']);
		}
		if (isset($arr['issuerName'])) {
			$obj->set_issuer_name($arr['issuerName']);
		}
		if (isset($arr['issuer'])) {
			$obj->set_issuer($arr['issuer']);
		}

		return $obj;
	}

	/**
    * Get creation time.
    *
    * @return datetime Creation time as a datetime
    */
	public function get_created_at() {
		return $this->created_at;
	}

	/**
    * Set creation time.
    *
    * @param datetime $created_at Creation time as a datetime
    */
   public function set_created_at($created_at) {
		$this->created_at = $created_at;
		return $this;
	}

	/**
    * Get creator.
    *
    * @return string Creator as a string
    */
	public function get_created_by() {
		return $this->created_by;
	}

	/**
    * Set creator.
    *
    * @param datetime $created_by Creator as a string
    */
   public function set_created_by($created_by) {
		$this->created_by = $created_by;
		return $this;
	}

	/**
    * Get id.
    *
    * @return int
    */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set id.
	 *
	 * @param int $id The id of the badge
	 */
	public function set_id($id) {
		$this->id = $id;
		return $this;
	}

	/**
    * Get name.
    *
    * @return string Name
    */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Set name.
	 *
	 * @param string $name
	 */
	public function set_name($name) {
		$this->name = $name;
		return $this;
	}

	/**
    * Get image.
    *
    * @return string Image url.
    */
	public function get_image() {
		return $this->image;
	}

	/**
	 * Set image.
	 *
	 * @param string $image
	 */
	public function set_image($image) {
		$this->image = $image;
		return $this;
	}

	/**
    * Get slug.
    *
    * @return string Badge slug.
    */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Set slug.
	 *
	 * @param string $slug
	 */
	public function set_slug($slug) {
		$this->slug = $slug;
		return $this;
	}

	/**
    * Get criteria url.
    *
    * @return string
    */
	public function get_criteria_url() {
		return $this->criteria_url;
	}

	/**
	 * Set criteria url.
	 *
	 * @param string $criteria_url
	 */
	public function set_criteria_url($criteria_url) {
		$this->criteria_url = $criteria_url;
		return $this;
	}

	/**
    * Get criteria text.
    *
    * @return string
    */
	 public function get_criteria_text() {
		return $this->criteria_text;
	}

	/**
	 * Set criteria text.
	 *
	 * @param string $criteria_text
	 */
	public function set_criteria_text($criteria_text) {
		$this->criteria_text = $criteria_text;
		return $this;
	}

	/**
    * Get recipient count.
    *
    * @return int
    */
	 public function get_recipient_count() {
		return $this->recipient_count;
	}

	/**
	 * Set recipient count.
	 *
	 * @param int $recipient_count The recipient count of the badge
	 */
	public function set_recipient_count($recipient_count) {
		$this->recipient_count = $recipient_count;
		return $this;
	}

	/**
    * Get description.
    *
    * @return string
    */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Set description.
	 *
	 * @param string $description Description
	 */
	public function set_description($description) {
		$this->description = $description;
		return $this;
	}

	/**
    * Get alignment
    *
    * @return array
    */
	public function get_alignment() {
		return $this->alignment;
	}

	/**
	 * Set alignment.
	 *
	 * @param array $alignment
	 */
	public function set_alignment($alignment) {
		$this->alignment = $alignment;
		return $this;
	}

	/**
    * Get tags
    *
    * @return string[]
    */
	 public function get_tags() {
		return $this->tags;
	}

	/**
	 * Set tags.
	 *
	 * @param string[] $tags
	 */
	public function set_tags($tags) {
		$this->tags = $tags;
		return $this;
	}

	/**
    * Get expires.
    *
    * @return array Expires by time as an array with int amount and string duration
    */
	public function get_expires() {
		return $this->expires;
	}

	/**
	 * Set expires.
	 *
	 * @param array $expires Expires by time as an array with int amount and string duration
	 */
	public function set_expires($expires) {
		$this->expires = $expires;
		return $this;
	}

	/**
    * Get source url.
    *
    * @return string
    */
	 public function get_source_url() {
		return $this->source_url;
	}

	/**
	 * Set source url.
	 *
	 * @param string $source_url
	 */
	public function set_source_url($source_url) {
		$this->source_url = $source_url;
		return $this;
	}

	/**
    * Get issuer name.
    *
    * @return string
    */
	 public function get_issuer_name() {
		return $this->issuer_name;
	}

	/**
	 * Set issuer name.
	 *
	 * @param string $issuer-Name
	 */
	public function set_issuer_name($issuer_name) {
		$this->issuer_name = $issuer_name;
		return $this;
	}

	/**
    * Get issuer url.
    *
    * @return string
    */
	 public function get_issuer() {
		return $this->issuer;
	}

	/**
	 * Set issuer url.
	 *
	 * @param string $issuer
	 */
	public function set_issuer($issuer) {
		$this->issuer = $issuer;
		return $this;
	}

	/**
    * Get badge url.
    *
    * @return string
    */
	 public function get_badge_url() {
		return $this->badge_url;
	}

	/**
	 * Set badge url.
	 *
	 * @param string $badge_url
	 */
	public function set_badge_url($badge_url) {
		$this->badge_url = $badge_url;
		return $this;
	}
}
