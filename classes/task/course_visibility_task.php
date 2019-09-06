<?php
// This file is part of the local_visibility plugin for Moodle - http://moodle.org/
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
 * Checks if a course should be visible and updates course visibility accordingly.
 *
 * @package    local_visibility
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_visibility\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Task class.
 *
 * @package    local_visibility
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_visibility_task extends \core\task\scheduled_task {
    /**
     * Iterates through each course in {course_visibility_schedule} and determines their visibility.
     *
     * @return void
     */
    public function execute() {
        self::determine_visibility();
    }
    /**
     * Determines which courses need their visibilities changed, and updates them appropriately.
     *
     * @param $id int, optional, course id - if set, only check visibility of this single course.
     *                 Must be provided along with $visible.
     * @param $visible, optional, current course visibility. Must be provided along with $id.
     * @return array, If $id and $visible are not set, null. Otherwise, returns an array of the form:
     *              Array('visible' => the updated visibility,
     *                    'ranges' => array of visibility ranges for the specified course)
     */
    public static function determine_visibility($id = null, $visible = null) {
        global $DB;

        // Retrieve the visibility ranges...
        $ranges = array();
        if (is_null($id) || is_null($visible)) {
            // ...for each course.
            $sql = "SELECT cvs.id, cvs.courseid, cvs.hidefrom, cvs.hideuntil, cvs.past, c.visible
                      FROM {course_visibility_schedule} cvs
                      JOIN {course} c
                        ON cvs.courseid = c.id
                     WHERE cvs.past = 0";
            $ranges = $DB->get_recordset_sql($sql);
        } else {
            // ...for the single course.
            $sql = "SELECT id, courseid, hidefrom, hideuntil, title, past
                      FROM {course_visibility_schedule}
                     WHERE courseid = :courseid
                           AND past = 0";
            $ranges = $DB->get_records_sql($sql, array('courseid' => $id));

            // Need to add the 'visible' property to each range.
            foreach ($ranges as $range) {
                $range->visible = $visible;
            }
        }

        // Determine which courses need their visibilities changed.
        // This algorithm should still work if overlap exists.
        $time = time();
        $visibilitychecks = array(); // Map: ('courseid' => boolean, should course be hidden?)
        $coursevisibility = array(); // Map: ('courseid' => boolean, is course visible?)
        // Iterate through each range and determine whether the course should be hidden.
        foreach ($ranges as $range) {
            // Check if this range is now in the past. If so, it can be ignored in the future.
            if ($range->hideuntil <= time()) {
                $DB->update_record('course_visibility_schedule', array('id' => $range->id, 'past' => 1));
            }

            if ($range->hidefrom <= $time && $range->hideuntil > $time) {
                // Course should be hidden.
                $visibilitychecks[$range->courseid] = true;
            } else if (!isset($visibilitychecks[$range->courseid])) {
                // Course should be visible - unless we determine it should be hidden.
                $visibilitychecks[$range->courseid] = false;
            }
            // Store course visibility.
            if (!isset($coursevisibility[$range->courseid])) {
                $coursevisibility[$range->courseid] = $range->visible;
            }
        }
        // Iterate through each course and determine whether its visibility should be changed.
        foreach ($visibilitychecks as $courseid => $visibilitycheck) {
            if ($visibilitycheck && $coursevisibility[$courseid]) {

                // Course is visible, but shouldn't be. Hide it.
                course_change_visibility($courseid, false);

                // Log visibility action - hide.
                $context = \context_course::instance($courseid);
                $event = \local_visibility\event\course_visibility_hide::create(array(
                    'context' => $context
                ));
                $event->trigger();

                if (!is_null($visible) && !is_null($id)) {
                    $visible = false;
                    break;
                }
            }
            if (!$visibilitycheck && !$coursevisibility[$courseid]) {

                // Course isn't visible, but should be. Show it.
                course_change_visibility($courseid, true);

                // Log visibility action - show.
                $context = \context_course::instance($courseid);
                $event = \local_visibility\event\course_visibility_show::create(array(
                    'context' => $context
                ));
                $event->trigger();

                if (!is_null($visible) && !is_null($id)) {
                    $visible = true;
                    break;
                }
            }
        }

        if (is_null($id) || is_null($visible)) {
            return null;
        } else {
            return array('visible' => $visible, 'ranges' => $ranges);
        }
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('course_visibility_task', 'local_visibility');
    }
}
