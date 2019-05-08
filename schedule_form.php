<?php
// This file is part of Moodle - http://moodle.org/
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
 * Visibility schedule form definition.
 *
 * @copyright 2017 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_visibility
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/formslib.php");

/**
 * Form used to schedule a course's visibility.
 *
 * @copyright 2017 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_visibility
 */
class schedule_form extends moodleform {
    /**
     * The selected course.
     * @var object
     */
    private $course = null;
    /**
     * The ranges in the course visibility schedule.
     * @var array of records ('id' => int, 'courseid' => int, 'hideuntil' => int, 'hidefrom' => int, 'past' => bool).
     */
    private $visibilityschedule = null;

    /**
     * Add elements to the moodleform and initializes $course and $visibilityschedule.
     */
    public function definition() {
        global $DB, $OUTPUT;

        // Retrieve the schedule for this course.
        $this->course = $DB->get_record('course', array('id' => $this->_customdata['courseid']));
        $this->visibilityschedule = $DB->get_records('ucla_visibility_schedule',
                array('courseid' => $this->_customdata['courseid']));

        // Sort the schedule by increasing order of start time, so that the earlier schedules are displayed first.
        usort($this->visibilityschedule, function($a, $b) {
            return ($a->hidefrom > $b->hidefrom);
        });

        // Include a table of the previously scheduled hidden ranges.
        $strftimedaydatetime = get_string('strftimedaydatetime', 'langconfig');
        $deletebuttonname = "scheduledeletebutton";
        $upcomingfound = false;

        // Set up the table data.
        $table = new html_table();
        $table->attributes['class'] = 'table-condensed';
        $table->attributes['style'] = 'min-width: 50%;';
        $table->head = array(
            get_string('titlecol', 'local_visibility'),
            get_string('fromcol', 'local_visibility'),
            get_string('untilcol', 'local_visibility'), '');
        $table->data = array();
        foreach ($this->visibilityschedule as $index => $range) {
            // This is used to delete the range.
            $table->rowclasses[$index] = 'visibility-session range' . $range->id;
            $deletebutton = '<img src="' . $OUTPUT->image_url('t/delete', '') .
                    '" style="cursor:pointer;" class="rangedeletebutton"'.
                    'data-course="'. $this->course->id .'" data-id="'. $range->id .'";/>';

            // Format the rows based on whether their ranges are upcoming or in the past.
            if (!$upcomingfound && $range->hideuntil > time()) {
                $upcomingfound = true; // We only want to bold one of them. The closest upcoming range.
                $table->rowclasses[$index] .= ' bold';
            } else if ($range->past || $range->hideuntil <= time()) {
                $table->rowclasses[$index] .= ' text-muted';
            }
            $table->rowclasses[$index] .= ' range' . $range->id . ' '; // This is used to delete the range.

            $table->data[] = array(
                $range->title ? $range->title : '<i>n/a</i>',
                userdate($range->hidefrom, $strftimedaydatetime),
                userdate($range->hideuntil, $strftimedaydatetime),
                $deletebutton);
        }

        // Start defining the form elements.
        $mform = $this->_form;

        $mform->addElement('html', '<br>');

        $choices = array();
        $choices['0'] = get_string('hideoption', 'local_visibility');
        $choices['1'] = get_string('showoption', 'local_visibility');
        $mform->addElement('select', 'visible', get_string('visibilitytitle', 'local_visibility'), $choices);
        $mform->addHelpButton('visible', 'visible');
        $mform->setDefault('visible', $this->course->visible);

        // If the course is scheduled to be hidden, do not allow the user to change the visibility.
        if ($upcomingfound) {
            $mform->hardFreeze('visible');
            $mform->setConstant('visible', $this->course->visible);
        } else {
            $mform->addElement('submit', 'savevisiblebutton', get_string('save', 'local_visibility'));
        }

        $mform->addElement('html', '<br>');

        $mform->addElement('static', 'schedulevisibilitytitle',
                '<h4>'. get_string('schedulecoursevisibility', 'local_visibility') .'</h4>', null);

        $mform->addElement('text', 'title', get_string('titlecolopt', 'local_visibility'),
                array('placeholder' => get_string('placeholder', 'local_visibility'),  'maxlength' => 50, 'size' => 40));
        $mform->setType('title', PARAM_RAW);

        $mform->addElement('date_time_selector', 'hidefrom', get_string('hidefrom', 'local_visibility'));
        $mform->addElement('date_time_selector', 'hideuntil', get_string('hideuntil', 'local_visibility'));

        $mform->addElement('hidden', 'id', $this->course->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('submit', 'submitbutton', get_string('schedulesubmit', 'local_visibility'));

        $mform->addElement('html', '<br>');

        // Draw the table.
        if (!empty($this->visibilityschedule)) {
            $mform->addElement('static', 'hiddenfromtabletitle', get_string('scheduletableheader', 'local_visibility'), null);
            $mform->addElement('html', html_writer::table($table) . '<br>');
            $mform->registerNoSubmitButton($deletebuttonname);

            // Define a delete all button, which deletes the sessions using ajax.
            $mform->addElement('button', 'rangedeleteallbutton', get_string("deleteall"),
                    array('data-course' => $this->course->id));
        }
    }

    /**
     * Checks if the date range is valid.
     * Possible errors:
     *      Date range overlaps with a previous date range.
     *      Hidefrom or Hideuntil are not set.
     *      Hideuntil is before Hidefrom.
     *
     * @param object $data
     * @param array $files
     */
    public function validation($data, $files) {
        global $DB;

        $errors = array();

        // Refresh and resort the schedule for this course, since some ranges may have been deleted.
        $this->visibilityschedule = $DB->get_records('ucla_visibility_schedule',
                array('courseid' => $this->_customdata['courseid']));
        usort($this->visibilityschedule, function($a, $b) {
            return ($a->hidefrom > $b->hidefrom);
        });

        // Make sure everything was provided.
        if (empty($data['hidefrom'])) {
            $errors['hidefrom'] = get_string('hideemptyerror', 'local_visibility');
        }
        if (empty($data['hideuntil'])) {
            $errors['hideuntil'] = get_string('hideemptyerror', 'local_visibility');
        }
        if (empty($data['hidefrom']) || empty($data['hidefrom'])) {
            return $errors;
        }

        // Ensure that hidefrom is before hideuntil.
        if ($data['hideuntil'] < $data['hidefrom']) {
            $errors['hideuntil'] = get_string('temphideerror', 'local_visibility');
            return $errors;
        }

        // Check if any overlap with previous ranges.
        $merge = optional_param('mergebutton', 0, PARAM_RAW);
        $overlap = self::date_range_overlap($data, $merge);
        if (!$merge && !empty($overlap)) {
            // Set up a table of the overlapping ranges.
            $strftimedaydatetime = get_string('strftimedaydatetime', 'langconfig');
            $table = new html_table();
            $table->attributes['class'] = 'table-condensed';
            $table->attributes['style'] = 'margin-top: 1%;';
            $table->head = array(
                get_string('titlecol', 'local_visibility'),
                get_string('fromcol', 'local_visibility'),
                get_string('untilcol', 'local_visibility'), '');
            $table->data = array();
            foreach ($overlap as $range) {
                $table->data[] = array(
                    $range['title'] ? $range['title'] : '<i>n/a</i>',
                    userdate($range['hidefrom'], $strftimedaydatetime),
                    userdate($range['hideuntil'], $strftimedaydatetime));
            }
            $tablehtml = html_writer::table($table);
            // Give user the option to either merge with the overlapping ranges or cancel.
            $mergebutton = '<input name = "mergebutton" type="submit"'.
                    'value="'. get_string('overlaperrorbutton', 'local_visibility') .'">';
            $cancelbutton = '<input name="cancel" value="Cancel" type="submit" onclick="skipClientValidation = true;'.
                    'return true;" class=" btn-cancel" id="id_cancel">';
            $errors['title'] = get_string('overlaperror', 'local_visibility') .
                    $tablehtml .
                    '<div style="margin-top: 5%">' . get_string('overlaperror2', 'local_visibility') . '</div>' .
                    '<div style="margin-top: 1%; margin-left: 28%;">' .
                    $mergebutton . $cancelbutton .
                    '</div>';
        }

        return $errors;
    }

    /**
     * Utility function which checks whether hidefrom and hideuntil overlap with any of
     * the ranges in $this->visibilityschedule.
     * When $merge is true, it will also merge the new range into the existing schedule,
     * and update the ucla_visibility_schedule table in the database.
     *
     * @param object $data
     * @param boolean $merge Optional. If true, merge the new hidefrom and hideuntil into the old schedule.
     * @return associative array of of date ranges that the new range overlaps with.
     *         (id => array('id', 'courseid', 'hidefrom', 'hideuntil', 'title'))
     */
    private function date_range_overlap($data, $merge = false) {
        global $DB;

        // Convert $this->visibilityschedule to an array of intervals ('start', 'end').
        $intervals = array();
        foreach ($this->visibilityschedule as $interval) {
            $intervals[] = array('id' => $interval->id, 'title' => $interval->title, 'courseid' => $this->course->id,
                    'hidefrom' => $interval->hidefrom, 'hideuntil' => $interval->hideuntil);
        }
        $intervals[] = array('id' => null, 'title' => $data['title'], 'courseid' => $this->course->id,
                'hidefrom' => $data['hidefrom'], 'hideuntil' => $data['hideuntil']);

        // Sort the intervals by increasing order of start time.
        usort($intervals, function($a, $b) {
            return ($a['hidefrom'] > $b['hidefrom']);
        });

        // Iteratively check for overlap.
        $overlap = array();
        for ($i = 1; $i < count($intervals); $i++) {
            if ($intervals[$i - 1]['hideuntil'] >= $intervals[$i]['hidefrom']) {
                // Merge intervals[$i] into intervals[$i - 1].
                if ($intervals[$i - 1]['hideuntil'] < $intervals[$i]['hideuntil']) {
                    $intervals[$i - 1]['hideuntil'] = $intervals[$i]['hideuntil'];
                    $overlap[] = $intervals[$i - 1]; // Record the interval we're modifying.
                } else {
                    $overlap[] = $intervals[$i]; // Record the interval we're deleting.
                }
                if (is_null($intervals[$i - 1]['id'])) {
                    // The interval has no record in the database.
                    $intervals[$i - 1]['id'] = $intervals[$i]['id'];
                } else if ($merge) {
                    $DB->delete_records('ucla_visibility_schedule', array('id' => $intervals[$i]['id']));
                }
                // Update the range in the database.
                $intervals[$i - 1]['past'] = ($intervals[$i - 1]['hideuntil'] < time());
                $DB->update_record('ucla_visibility_schedule', $intervals[$i - 1]);

                array_splice($intervals, $i, $i);
                $i--;
            }
        }
        return $overlap;
    }
}
