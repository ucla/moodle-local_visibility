<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Installation script for local_visibility.
 *
 * @package    local_visibility
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Runs extra commands when installing. Need to migrate old hide/from ranges to new table.
 * @return boolean
 */
function xmldb_local_visibility_install() {
    global $DB;
    $dbman = $DB->get_manager();

    // Check if hidestartdate and hideenddate exist in old table.
    if (!$dbman->field_exists('course', 'hidestartdate') &&
            !$dbman->field_exists('course', 'hideenddate')) {
        return true;
    }

    // Retrieve old ranges.
    $sql = "SELECT id, hidestartdate, hideenddate
              FROM {course}
             WHERE 1=1";
    $oldranges = $DB->get_records_sql($sql);

    // Remove fields from old table.
    $coursetable = new xmldb_table('course');
    $fields = array();
    $fields[] = new xmldb_field('hidestartdate', XMLDB_TYPE_INTEGER, '15', XMLDB_UNSIGNED, null, null, null, null, null, null);
    $fields[] = new xmldb_field('hideenddate', XMLDB_TYPE_INTEGER, '15', XMLDB_UNSIGNED, null, null, null, null, null, null);

    foreach ($fields as $field) {
        if ($dbman->field_exists($coursetable, $field)) {
            $dbman->drop_field($coursetable, $field);
        }
    }

    // Add records to new table.
    $newranges = array();
    foreach ($oldranges as $range) {
        if (!empty($range->hidestartdate) || !empty($range->hideenddate)) {
            $newrange = array('courseid' => $range->id,
                    'hidefrom' => $range->hidestartdate,
                    'hideuntil' => $range->hideenddate,
                    'title' => '',
                    'past' => $range->hideenddate < time());
            // If hidefrom is 0, just make it a year later than hidefrom.
            if (empty($range->hideenddate)) {
                $newrange['hideuntil'] = $range->hidestartdate + 31557600;
            }
            $newranges[] = $newrange;
        }
    }
    if (!empty($newranges)) {
        $DB->insert_records('ucla_visibility_schedule', $newranges);
    }

    return true;
}