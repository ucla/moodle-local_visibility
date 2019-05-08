<?php
// This file is part of the UCLA local_visibility plugin for Moodle - http://moodle.org/
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
 * Collection of functions for plugin.
 *
 * @package    local_visibility
 * @copyright  2019  UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_visibility;
defined('MOODLE_INTERNAL') || die();

/**
 * Collection of functions for plugin.
 *
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core {
    /**
     * Checks if course is hidden due to a scheduled visibility window.
     *
     * If so, then display notice.
     *
     * @return boolean  Returns true if notice was set, otherwise false.
     */
    static function set_visiblity_notice($course) {
        global $CFG, $DB;
        $schedule = $DB->get_records('ucla_visibility_schedule', array('courseid' => $course->id, 'past' => 0));
        $noticeset = false;
        foreach ($schedule as $range) {
            $title = '';
            if ($range->hideuntil >= time() && $range->hidefrom <= time()) {
                $title = '';
                if (isset($range->title) && $range->title != '') {
                    $title = '<i>(' . $range->title . ')</i>';
                }
                $hideuntil = userdate($range->hideuntil, get_string('strftimedatetime', 'langconfig'));
                \core\notification::info(get_string('coursehidden', 'local_visibility',
                        array('hideuntil' => $hideuntil, 'title' => $title)),
                        $CFG->wwwroot .'/');
                $noticeset = true;
                break;
            }
        }
        return $noticeset;
    }

    /**
     * If user can update course, then display current or upcoming scheduled
     * visibility windows.
     *
     * @param object $course
     */
    static function set_instructor_visiblity_notice($course) {
        // Display the temporary course visibility status for users with update access.
        if (has_capability('moodle/course:update', \context_course::instance($course->id))) {
            // If the cron hasn't hidden/unhidden the course yet, just change it ourselves. Also retrieve visibility schedule.
            $visibilityandranges = \local_visibility\task\course_visibility_task::determine_visibility($course->id, $course->visible);

            if (isset($visibilityandranges)) {
                $course->visible = $visibilityandranges['visible'];
                $visibilityschedule = $visibilityandranges['ranges'];

                // Determine the closest upcoming range.
                foreach ($visibilityschedule as $range) {
                    if ($range->hideuntil > time()) {
                        $upcomingrange = $range;
                        break;
                    }
                }
            }

            // Determine whether the course is scheduled to be hidden, or scheduled to be unhidden.
            if (isset($upcomingrange)) {
                $strftimedatetime = get_string('strftimedatetime', 'langconfig');
                $tempvisibilitystatus = null;
                $time = time();
                if ($course->visible && $upcomingrange->hidefrom >= $time && $upcomingrange->hideuntil > $time) {
                    // Course is scheduled to be hidden.
                    $tempvisibilitystatus = get_string('temphidenotif', 'local_visibility',
                                array('hidefrom' => userdate($upcomingrange->hidefrom, $strftimedatetime),
                                      'hideuntil' => userdate($upcomingrange->hideuntil, $strftimedatetime)));
                } else if (!$course->visible && $upcomingrange->hidefrom <= $time && $upcomingrange->hideuntil > $time) {
                    // Course is scheduled to be unhidden.
                    $tempvisibilitystatus = get_string('unhiddennotif', 'local_visibility',
                        array('hideuntil' => userdate($upcomingrange->hideuntil, $strftimedatetime)));
                }
                if (isset($tempvisibilitystatus)) {
                    // If it is set, include the range title in the notification message.
                    $title = '';
                    if (isset($upcomingrange->title) && $upcomingrange->title != '') {
                        $title = ' <i>(' . $upcomingrange->title . ')</i>';
                    }
                    \core\notification::info($tempvisibilitystatus . $title);
                }
            }
        }
    }
}
