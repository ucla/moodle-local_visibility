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
 * Course visibility library file.
 *
 * @package    local_visibility
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2017 UC Regents
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add visibility plugin to course navigation.
 * @param navigation_node $navigation - The parent navigation node.
 * @param stdClass $course
 * @param context_course $context
 */
function local_visibility_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
    $capabilities = array('moodle/course:update', 'moodle/course:visibility');
    $context = context_course::instance($course->id);
    if (!has_all_capabilities($capabilities, $context) || !can_access_course($course)) {
        return;
    }

    $title = get_string('pluginname', 'local_visibility');
    $path = new moodle_url("/local/visibility/schedule.php", array('id' => $course->id));
    $settingsnode = navigation_node::create($title,
                                            $path,
                                            navigation_node::TYPE_SETTING,
                                            null,
                                            'coursevisibility',
                                            new pix_icon($course->visible ? 'i/hide' : 'i/show', ''));
    if (isset($settingsnode)) {
        $navigation->add_node($settingsnode, 'users');
    }
}

