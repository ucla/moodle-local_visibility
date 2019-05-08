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
 * English strings for visibility plugin
 *
 * @package    local_visibility
 * @subpackage lang
 * @subpackage en
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course visibility';

$string['course_visibility_task'] = 'Temporarily hidden courses';
$string['schedulecoursevisibility'] = 'Schedule course visibility:';
$string['schedulesubmit'] = 'Save session';
$string['hideemptyerror'] = 'This field cannot be empty.';
$string['temphideerror'] = '"Hide from" time must be before "Hide until" time.';
$string['overlaperror'] = 'Selected range overlaps with the following ranges:';
$string['overlaperror2'] = 'Would you like to merge your selected date range with these date ranges?';
$string['overlaperrorbutton'] = 'Merge';
$string['temphidenotif'] = 'This course will be temporarily hidden from <b>{$a->hidefrom}</b> to <b>{$a->hideuntil}</b>.';
$string['unhiddennotif'] = 'This course is temporarily hidden. It will become visible on <b>{$a->hideuntil}</b>.';
$string['hidefrom'] = 'Hide from';
$string['hideuntil'] = 'Hide until';
$string['coursevisibilityheader'] = 'Course visibility settings';
$string['scheduletableheader'] = 'Course will be hidden...';
$string['titlecolopt'] = 'Title <i>(optional)</i>';
$string['titlecol'] = 'Title';
$string['placeholder'] = 'e.g. \'Midterm #1\'';
$string['fromcol'] = 'From';
$string['untilcol'] = 'Until';
$string['visibilitytitle'] = 'Visibility';
$string['visibilitydisabled'] = 'This option is disabled because the course is scheduled to be hidden.';
$string['visibilitylink'] = 'Click here to modify the course visibility schedule.';
$string['hideoption'] = 'Hidden from students';
$string['showoption'] = 'Visible to students';
$string['save'] = 'Save';
$string['errordeleteschedule'] = 'Could not find scheduled entry to delete';
$string['successaddedschedule'] = 'Successfully added scheduled course visiblity';
$string['successcoursehidden'] = 'Course is now hidden from students';
$string['successcourseunhidden'] = 'Course is now visible to students';
$string['successdeleteallschedule'] = 'Successfully deleted all scheduled sessions';
$string['successdeleteschedule'] = 'Successfully deleted schedule';
$string['successmergeschedule'] = 'Successfully merged scheduled course visiblity';
$string['coursehidden'] = 'This course is temporarily unavailable to students. It will become available again on <b>{$a->hideuntil}</b>. {$a->title}';
$string['deleteall'] = 'Delete all';
// Logging event handler.
$string['eventcoursevisibilityhide'] = 'Course visibility - hidden';
$string['eventcoursevisibilityshow'] = 'Course visibility - visible';
$string['eventcoursevisibilityhide_desc'] = 'This course visibility has been changed. Course is now hidden.';
$string['eventcoursevisibilityshow_desc'] = 'This course visibility has been changed. Course is now visible.';
// Confirmation Modal
$string['confirmremovevisibilitysession'] = 'Are you absolutely sure you want to delete this hidden visibility session?';
$string['confirmdeleteallsessions'] = 'Are you sure you would like to delete ALL hidden visibility sessions?';