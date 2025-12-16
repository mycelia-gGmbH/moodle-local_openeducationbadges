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
 * Badge list page renderable
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_openeducationbadges\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use local_openeducationbadges\badge;
use local_openeducationbadges\client;
use oeb_course_badge_form;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../form/course_badge.php');

/**
 * Class containing data for badge list page
 *
 * @package    local_openeducationbadges
 * @copyright  2024 Esirion AG
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge_page implements renderable, templatable {
    /** @var string $heading Heading of the template. */
    private $heading = '';

    /** @var badge[] $badges Array of badges to render. */
    private $badges = [];

    /** @var client $client Client object. */
    private $client = null;

    /** @var \context $context Context of rendering. */
    private $context = null;

    /**
     * Constructor.
     *
     * @param string $heading
     * @param badge[] $badges
     * @param \context $context
     */
    public function __construct($heading, $badges, $context) {
        $this->heading = $heading;
        $this->badges = $badges;
        $this->context = $context;
        $this->client = client::get_instance();
    }

    #[\Override]
    public function export_for_template(renderer_base $output): \stdClass {
        global $CFG;

        $data = new \stdClass();
        $data->heading = $this->heading;
        $data->badges = [];
        foreach ($this->badges as $badge) {
            $badgedata = [
                "badgeimage" => $badge->get_image(),
                "badgelink" => $badge->get_badge_url(),
                "badgename" => s($badge->get_name()),
                "badgeissuer" => s($badge->get_issuer_name()),
                "badgedesc" => s($badge->get_description()),
            ];

            if ($this->context instanceof \context_course) {
                $mform = $this->get_course_badge_form($badge, $this->context);
                $badgedata["badgefooter"] = [
                    "heading" => s(get_string('selectaward', 'local_openeducationbadges')),
                    "form" => $mform->render(),
                ];
            } else if ($this->context instanceof \context_system) {
                $editlink = $CFG->wwwroot . '/local/openeducationbadges/badge.php?action=edit&badge=' . $badge->get_slug();
                $badgedata["badgefooter"] = [
                    "actions" => true,
                    "editlink" => $editlink,
                ];
            }

            $data->badges[] = $badgedata;
        }

        if ($this->context instanceof \context_system) {
            $createlink = $CFG->wwwroot . '/local/openeducationbadges/badge.php?action=create&issuer=';

            $data->issuers = [];
            foreach ($this->client->get_client_ids() as $clientid) {
                $issuers = $this->client->get_issuers($clientid);
                foreach ($issuers as $issuer) {
                    $data->issuers[] = [
                        "name" => $issuer['name'],
                        "slug" => $issuer['slug'],
                        "createlink" => $createlink . $issuer['slug'] . '&clientid=' . $clientid,
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Gets the name of the mustache template used to render the data.
     *
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'local_openeducationbadges/badge_page';
    }

    /**
     * Creates awarding form for given badge in given course context.
     *
     * @param badge $badge
     * @param \context_course $context
     * @return oeb_course_badge_form
     */
    private function get_course_badge_form(badge $badge, \context_course $context): oeb_course_badge_form {
        global $DB, $PAGE;

        $badgeid = $badge->get_id();
        $courseid = $context->instanceid;

        $urlparams = ['courseid' => $courseid];
        $PAGE->set_url($PAGE->url, $urlparams);

        $coursecompletionrecord = $DB->get_record(
            'local_oeb_course_badge',
            [
                'courseid' => $courseid,
                'badgeid' => $badgeid,
                'completion_method' => badge::COMPLETION_TYPE_COURSE,
            ],
            '*',
        );

        $activitycompletionrecords = $DB->get_records(
            'local_oeb_course_badge',
            [
                'courseid' => $courseid,
                'badgeid' => $badgeid,
                'completion_method' => badge::COMPLETION_TYPE_ACTIVITY,
            ],
            '',
            'activityid,id'
        );

        $mform = new oeb_course_badge_form($PAGE->url, $badgeid, $courseid);

        if ($coursecompletionrecord) {
            $mform->set_data(['coursecompletion_'.strval($badgeid) => 1]);
        }

        foreach ($activitycompletionrecords as $activityid => $object) {
            $mform->set_data(['activitycompletion_'.strval($badgeid).'_'.strval($activityid) => 1]);
        }

        if ($data = $mform->get_data()) {
            $dataarr = json_decode(json_encode($data), true);

            if (array_key_exists('submit_'.strval($badgeid), $dataarr)) {
                $coursecompletion = intval($dataarr['coursecompletion_'.strval($badgeid)]);
                if ($coursecompletionrecord && !$coursecompletion) {
                    $DB->delete_records('local_oeb_course_badge', ['id' => $coursecompletionrecord->id]);
                } else if (!$coursecompletionrecord && $coursecompletion) {
                    $coursecompletionrecord = new \stdClass;
                    $coursecompletionrecord->courseid = $courseid;
                    $coursecompletionrecord->badgeid = $badgeid;
                    $coursecompletionrecord->completion_method = badge::COMPLETION_TYPE_COURSE;
                    $coursecompletionrecord->activityid = 0;
                    $DB->insert_record('local_oeb_course_badge', $coursecompletionrecord);
                }

                $activites = array_keys($mform->get_activity_options());
                foreach ($activites as $activityid) {
                    $activitycompletion = intval($dataarr['activitycompletion_'.strval($badgeid).'_'.strval($activityid)]);
                    if (!$activitycompletion && array_key_exists($activityid, $activitycompletionrecords)) {
                        $DB->delete_records('local_oeb_course_badge', ['id' => $activitycompletionrecords[$activityid]->id]);
                    } else if ($activitycompletion && !array_key_exists($activityid, $activitycompletionrecords)) {
                        $activitycompletionrecord = new \stdClass;
                        $activitycompletionrecord->courseid = $courseid;
                        $activitycompletionrecord->badgeid = $badgeid;
                        $activitycompletionrecord->completion_method = badge::COMPLETION_TYPE_ACTIVITY;
                        $activitycompletionrecord->activityid = $activityid;
                        $DB->insert_record('local_oeb_course_badge', $activitycompletionrecord);
                    };
                };
            }
        }

        return $mform;
    }
}
