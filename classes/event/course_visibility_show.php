<?php
// This file is part of local_visibility plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course visibility (show) logging event handler.
 *
 * @package    local_visibility
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_visibility\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Records when the course becomes visible to students via the course visibility scheduler.
 *
 * @package    local_visibility
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_visibility_show extends \core\event\base {

    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcoursevisibilityshow', 'local_visibility');
    }

    /**
     * Returns info on course visibility change.
     *
     * @return string
     */
    public function get_description() {
        return get_string('eventcoursevisibilityshow_desc', 'local_visibility');
    }

    /**
     * Returns URL to the course effected.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('course/view.php', array(
                    'id' => $this->courseid
                ));
    }
}