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
 * Contains the xmldb_local_visibility_upgrade function.
 *
 * @package    local_visibility
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2017 UC Regents
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function containing changes to the visibility_schedule data table.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_local_visibility_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Dates in this section are formatted as: YYYYMMDD00.

    return true;

}
