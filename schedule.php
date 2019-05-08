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
 * Tool used to schedule a course's visibility.
 *
 * @copyright 2017 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_visibility
 */

require_once('../../config.php');
require_once('./schedule_form.php');

$courseid = required_param('id', PARAM_INT);

// Access control checks.
require_login();
$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:update', $coursecontext);
require_capability('moodle/course:visibility', $coursecontext);

// Set up a moodle page.
$course = get_course($courseid);
$PAGE->set_url('/local/visibility/schedule.php', array('id' => $courseid));
$PAGE->set_context($coursecontext);
$PAGE->set_course($course);
$PAGE->set_pagelayout('admin');
$PAGE->set_title("$course->shortname: ".get_string('coursevisibilityheader', 'local_visibility'));
$PAGE->set_heading($course->fullname);
$baseurl = new moodle_url('schedule.php', array('id' => $courseid));

// Include javascript.
$PAGE->requires->js_call_amd('local_visibility/visibility', 'init');

// Instantiate moodleform.
$mform = new schedule_form(null, array('courseid' => $courseid));

if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect($baseurl);
}

// If form was submitted, update table with new date range.
if ($data = $mform->get_data()) {
    if (optional_param('savevisiblebutton', 0, PARAM_RAW) && isset($data->visible)) {
        $DB->update_record('course', array('id' => $courseid, 'visible' => $data->visible));
        if ($data->visible) {
            \core\notification::success(get_string('successcourseunhidden', 'local_visibility'));
        } else {
            \core\notification::success(get_string('successcoursehidden', 'local_visibility'));
        }
    } else if (optional_param('submitbutton', 0, PARAM_RAW) && isset($data->hideuntil) && isset($data->hidefrom)) {
        $data->courseid = $courseid;

        // Hide the course immediately if appropriate.
        if ($data->hidefrom <= time() && $data->hideuntil > time()) {
            course_change_visibility($courseid, false);
        }

        $data->past = ($data->hideuntil < time());
        $DB->insert_record('ucla_visibility_schedule', $data);

        \core\notification::success(get_string('successaddedschedule', 'local_visibility'));
    } else if (optional_param('mergebutton', 0, PARAM_RAW) && isset($data->hideuntil) && isset($data->hidefrom)) {
        // Hide the course immediately if appropriate.
        if ($data->hidefrom <= time() && $data->hideuntil > time()) {
            course_change_visibility($courseid, false);
        }
        \core\notification::success(get_string('successmergeschedule', 'local_visibility'));
    }
    redirect($baseurl);
}

// Start rendering.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursevisibilityheader', 'local_visibility'));

$mform->display();

echo $OUTPUT->footer();
