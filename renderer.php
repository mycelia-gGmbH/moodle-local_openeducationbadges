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

use classes\openeducation_badge;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/badge.php');
require_once(__DIR__ . '/form/course_badge.php');

/**
 * HTML output renderer for Open Education Badges plugin
 *
 * @package    local_openeducationbadges
 * @copyright  2024, esirion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_openeducationbadges_renderer extends plugin_renderer_base {

	/**
    * Renders the list of badges.
    *
    * @param openeducation_badge[] $badges
    * @param context $context
    * @return string
    */
	public function render_badgelist($badges, context $context) {
		$html = $this->print_heading('badgelisttitle', 2);
		$html .= $this->render_badges($badges, $context);

		return $html;
	}

	/**
    * Renders the Open Education Badges.
    *
	 * @param openeducation_badge[] $badges
    * @param context $context
    * @return string HTML
    */
	protected function render_badges($badges, context $context) {
		$html = '';

		if (count($badges) === 0) {
			$html .= $this->output->notification(get_string('nobadges', 'local_openeducationbadges'), 'notifynotice');
		} else {
			$items = '';

			foreach ($badges as $badge) {
				$items .= html_writer::tag('li',
					$this->print_badge_card($badge, $context),
					array('class' => 'openeducation-badge-card')
				);
			}

			$html .= html_writer::tag('ul', $items, array('class' => 'badgelist'));
		}

		return $html;
	}

	/**
	 * Generates the HTML for a heading.
    *
    * @param string $id The string id in the module's language file.
    * @param int $level The heading level.
    * @return string The hX-tag
    */
	public function print_heading($id, $level = 3) {
		return $this->output->heading(get_string($id, 'local_openeducationbadges'), $level);
	}

	/**
    * Generates the HTML for the badge image.
    *
    * @param openeducation_badge $badge The badge object
    * @return string The img-tag
    */
	public function print_badge_image(openeducation_badge $badge) {
		$params = array(
			"src" => $badge->get_image(),
			"alt" => s($badge->get_name()),
			"class" => "badgeimage"
		);

		return html_writer::empty_tag("img", $params);
	}

	/**
    * Generates the HTML for the badge card.
    *
    * @param openeducation_badge $badge The badge object
	 * @param context $context
    * @return string The card html
    */
	 public function print_badge_card(openeducation_badge $badge, context $context) {
		$badgeimage = $this->print_badge_image($badge);

		$badgename = html_writer::link(
			$badge->get_badge_url(),
			s($badge->get_name()),
			array('class' => 'badgename', 'target' => '_blank')
		);

		$badgeissuer = html_writer::tag('p',
			s($badge->get_issuer_name()),
			array('class' => 'badgeissuer')
		);

		$badgedesc = html_writer::tag('p',
			s($badge->get_description()),
			array('class' => 'badgedesc')
		);

		$card = html_writer::div(
			$badgeimage . html_writer::div(
				$badgename . $badgeissuer . $badgedesc,
				'badgeinfo'
			),
			'openeducation-badge-card-body'
		);

		$card .= $this->print_badge_card_footer($badge, $context);

		return $card;
	}

	/**
    * Generates the HTML for the badge card footer.
    *
    * @param openeducation_badge $badge The badge object
	 * @param context $context
    * @return string The card footer html
    */
	 public function print_badge_card_footer(openeducation_badge $badge, context $context) {
		global $PAGE;
		global $DB;

		$card_footer = '';

		if ($context instanceof context_course) {

			$badgeid = $badge->get_id();
			$course_id = $context->instanceid;

			$urlparams =  array('courseid' => $course_id);
			$PAGE->set_url($PAGE->url, $urlparams);

			$completionMethodRecord = $DB->get_record(
				'local_oeb_course_badge',
				array(
					'courseid' => $course_id,
					'badgeid' => $badgeid,
				),
				'*',
			);

			$mform = new oeb_course_badge_form($PAGE->url, $badgeid);

			if ($completionMethodRecord) {
				$mform->set_data(['coursecompletion_'.strval($badgeid) => $completionMethodRecord->completion_method]);
			} else {
				$mform->set_data(['coursecompletion_'.strval($badgeid) => 0]);
			}

			if ($data = $mform->get_data()) {
				$data_arr = json_decode(json_encode($data), true);

				if (array_key_exists('submit_'.strval($badgeid), $data_arr)) {
					$completion_method = intval($data_arr['coursecompletion_'.strval($badgeid)]);

					if ($completionMethodRecord) {
						if ($completion_method == 0) {
							$DB->delete_records('local_oeb_course_badge', array('id' => $completionMethodRecord->id));
						} else {
							$completionMethodRecord->completion_method = $completion_method;
							$DB->update_record('local_oeb_course_badge', $completionMethodRecord);
						}
					} else if (!$completionMethodRecord && $completion_method) {
						$completionMethodRecord = new stdClass;
						$completionMethodRecord->courseid = $course_id;
						$completionMethodRecord->badgeid = $badgeid;
						$completionMethodRecord->completion_method = $completion_method;
						$DB->insert_record('local_oeb_course_badge', $completionMethodRecord);
					}
				}
			}

			$card_footer .= html_writer::div(
				// $mform->display(), // TODO Form select completion method checkbox save button

				html_writer::tag('p',
					s(get_string('selectaward', 'local_openeducationbadges')),
					array('class' => 'badgeawarding')
				) . $mform->render(),
				'openeducation-badge-card-footer'
			);
		}

		return $card_footer;
	}

	/**
	 * Render assertions for user.
	 *
	 * @param stdClass $user
	 * @return string
	 */
	public function render_user_assertions($user) {
		global $DB;

		$badges = openeducation_badge::get_earned_badges($user);

		if (count($badges) === 0) {
			$output = get_string('nobadgesearned', 'local_openeducationbadges');
		} else {
			$context = context_system::instance();
			$output = $this->render_badges($badges, $context);
		}

		return $output;
	}
}
