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
 * This file processes AJAX actions in the course visibility plugin.
 *
 * @package    local_visibility
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$rangeid = optional_param('rangeid', -1, PARAM_INT);
$action  = required_param('action', PARAM_ALPHANUMEXT);

$PAGE->set_url(new moodle_url('local/visibility/ajax.php',
        array('courseid' => $courseid, 'action' => $action, 'rangeid' => $rangeid)));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

if ($course->id == SITEID) {
    throw new moodle_exception('invalidcourse');
}

require_login($course);

$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:update', $coursecontext);
require_capability('moodle/course:visibility', $coursecontext);

echo $OUTPUT->header();

$outcome = new stdClass();
$outcome->success = true;
$outcome->response = new stdClass();
$outcome->error = '';

switch ($action) {
    case 'delete':
        $deleted = $DB->delete_records('ucla_visibility_schedule',
                array('id' => $rangeid, 'courseid' => $courseid));
        if (empty($deleted)) {
            $outcome->success = false;
            $outcome->error = 'failed';
        } else {
            // See how many records are left.
            $outcome->count = $DB->count_records('ucla_visibility_schedule',
                    array('courseid' => $courseid));
            $outcome->successmsg = get_string('successdeleteschedule', 'local_visibility');
        }
        break;
    case 'deleteall':
        $deleted = $DB->delete_records('ucla_visibility_schedule',
                array('courseid' => $courseid));
        if (empty($deleted)) {
            $outcome->success = false;
            $outcome->error = 'failed';
        } else {
            $outcome->successmsg = get_string('successdeleteallschedule', 'local_visibility');
        }
        break;
    default:
        throw new moodle_exception('unknowajaxaction');
}

echo json_encode($outcome);
die();

