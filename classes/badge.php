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
 * Open Education Badge class.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace classes;

use classes\openeducation_client;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/client.php');

/**
 * Class for a Open Education Badge.
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openeducation_badge {

    /**
     * Completion type associated to courses
     */
    const COMPLETION_TYPE_COURSE = 1;

    /**
     * Completion type associated to activities
     */
    const COMPLETION_TYPE_ACTIVITY = 2;

    /** @var array Completion type text associating array. */
    private static $badgecompletiontype = [
        self::COMPLETION_TYPE_COURSE => 'course',
        self::COMPLETION_TYPE_ACTIVITY => 'activity',
    ];

    /** @var datetime The badge creation time as a datetime */
    private $createdat = null;

    /** @var string The badge creator as a string */
    private $createdby = '';

    /** @var int The id of the badge */
    private $id = null;

    /** @var string The name of the badge */
    private $name = '';

    /** @var string The badge image url */
    private $image = null;

    /** @var string The badge slug */
    private $slug = null;

    /** @var string The text of badge criteria. */
    private $criteriatext = '';

    /**@var string The URL of the badge criteria. */
    private $criteriaurl = null;

    /** @var int The recipient count of the badge */
    private $recipientcount = null;

    /** @var string The badge description */
    private $description = '';

    /** @var array The alignment of the badge. */
    private $alignment = [];

    /** @var string[] The tags of the badge. */
    private $tags = [];

    /** @var array Badge expiration time as an array with int amount and string duration */
    private $expires = [];

    /** @var string The URL of the badge source. */
    private $sourceurl = null;

    /** @var string The badge issuer name */
    private $issuername = '';

    /** @var string The badge issuer */
    private $issuer = null;

    /**@var string The URL of the badge. */
    private $badgeurl = null;


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
     * Returns the completion method for this badge in this given course.
     *
     * @param int $courseid The course id.
     * @return int Return completion method else false.
     */
    public function is_course_awarding(int $courseid) {
        global $DB;

        $record = $DB->get_record(
            'local_oeb_course_badge',
            [
                'courseid' => $courseid,
                'badgeid' => $this->get_id(),
            ],
            'completion_method'
        );

        if ($record) {
            return $record;
        } else {
            return 0;
        }
    }

    /**
     * Sets the completion method for this badge in this given course.
     *
     * @param int $courseid The course id.
     * @param int $completionmethod The completion method.
     * @return int Return completion method else false.
     */
    public function set_course_awarding(int $courseid, int $completionmethod) {
        global $DB;

        return $DB->insert_record(
            'local_oeb_course_badge',
            [
                'courseid' => $courseid,
                'badgeid' => $this->get_id(),
                'completion_method' => $completionmethod,
            ]
        );
    }

    /**
     * Gets and returns the Open Education badges.
     *
     * @return openeducation_badge[] The badges.
     */
    public static function get_badges() {
        $badges = [];

        try {
            $client = openeducation_client::get_instance();
            $badgesdata = $client->get_badges_all();
            foreach ($badgesdata as $badgedata) {
                $badge = self::get_instance_from_array($badgedata);
                $badges[$badge->get_id()] = $badge;
            }
        } catch (Exception $e) {
            // Any Exception should have already been handled.
        }

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
            $badgesdata = $client->get_badges_earned_all($user);
            foreach ($badgesdata as $badgedata) {
                $badge = self::get_instance_from_array($badgedata);
                $badges[$badge->get_id()] = $badge;
            }
        } catch (Exception $e) {
            // Any Exception should have already been handled.
        }

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
        return $this->createdat;
    }

    /**
     * Set creation time.
     *
     * @param datetime $createdat Creation time as a datetime
     */
    public function set_created_at($createdat) {
        $this->createdat = $createdat;
        return $this;
    }

    /**
     * Get creator.
     *
     * @return string Creator as a string
     */
    public function get_created_by() {
        return $this->createdby;
    }

    /**
     * Set creator.
     *
     * @param datetime $createdby Creator as a string
     */
    public function set_created_by($createdby) {
        $this->createdby = $createdby;
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
        return $this->criteriaurl;
    }

    /**
     * Set criteria url.
     *
     * @param string $criteriaurl
     */
    public function set_criteria_url($criteriaurl) {
        $this->criteriaurl = $criteriaurl;
        return $this;
    }

    /**
     * Get criteria text.
     *
     * @return string
     */
    public function get_criteria_text() {
        return $this->criteriatext;
    }

    /**
     * Set criteria text.
     *
     * @param string $criteriatext
     */
    public function set_criteria_text($criteriatext) {
        $this->criteriatext = $criteriatext;
        return $this;
    }

    /**
     * Get recipient count.
     *
     * @return int
     */
    public function get_recipient_count() {
        return $this->recipientcount;
    }

    /**
     * Set recipient count.
     *
     * @param int $recipientcount The recipient count of the badge
     */
    public function set_recipient_count($recipientcount) {
        $this->recipientcount = $recipientcount;
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
        return $this->sourceurl;
    }

    /**
     * Set source url.
     *
     * @param string $sourceurl
     */
    public function set_source_url($sourceurl) {
        $this->sourceurl = $sourceurl;
        return $this;
    }

    /**
     * Get issuer name.
     *
     * @return string
     */
    public function get_issuer_name() {
        return $this->issuername;
    }

    /**
     * Set issuer name.
     *
     * @param string $issuername
     */
    public function set_issuer_name($issuername) {
        $this->issuername = $issuername;
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
        return $this->badgeurl;
    }

    /**
     * Set badge url.
     *
     * @param string $badgeurl
     */
    public function set_badge_url($badgeurl) {
        $this->badgeurl = $badgeurl;
        return $this;
    }
}
