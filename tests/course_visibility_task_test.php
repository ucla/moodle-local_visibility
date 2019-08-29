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
 * Tests the course_visibility_task class.
 *
 * @package    local_visibility
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * PHPunit testcase class.
 *
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_visibility_task_test extends advanced_testcase {

    /**
     * @var int The current time.
     */
    private $now = null;

    /**
     * Database changes are made.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Add schedule to table.
     * @param $courseid
     * @param $hidefrom
     * @param $hideuntil
     * @return id of record inserted
     */
    private function add_schedule($courseid, $hidefrom, $hideuntil, $past = 2) {
        global $DB;
        return $DB->insert_record('course_visibility_schedule', array('courseid' => $courseid,
                'hidefrom' => $hidefrom, 'hideuntil' => $hideuntil, 'title' => '',
                'past' => ($past == 2)? ($hideuntil < $this->now) : $past));
    }
    /**
     * Delete schedule from table.
     * @param $id of schedule
     */
    private function del_schedule($id) {
        global $DB;
        $DB->delete_records('course_visibility_schedule', array('id' => $id));
    }

    /**
     * Test that the execute() method works as expected.
     */
    public function test_execute() {
        global $DB;

        // Course id => expected visible value.
        $expectations = array();
        $this->now = time();
        $now = $this->now;
        $courseids = array();

        // 1. Create normal course with nothing set.
        $course = $this->getDataGenerator()->create_course();
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // 2. Single schedule. Hide from not passed. Course visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now + 5 * MINSECS, $now + 10 * MINSECS);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // 3. Single schedule. Hide from passed. Hide until not passed. Course visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 5 * MINSECS, $now + 5 * MINSECS);
        $expectations[$course->id] = 0;
        $courseids[] = $course->id;

        // 4. Single schedule. Hide until passed. Course visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 10 * MINSECS, $now - 5 * MINSECS);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // 5. Single schedule. Hide from not passed. Course NOT visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now + 5 * MINSECS, $now + 10 * MINSECS);
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // 6. Single schedule. Hide from passed. Course NOT visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 5 * MINSECS, $now + 5 * MINSECS);
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 0;
        $courseids[] = $course->id;

        // 7. Single schedule. Hide until passed. Course NOT visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 10 * MINSECS, $now - 5 * MINSECS, 0);
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // 8. Two schedules. First hide from not passed. Course visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now +  5 * MINSECS, $now + 10 * MINSECS);
        self::add_schedule($course->id, $now + 15 * MINSECS, $now + 20 * MINSECS);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // 9. Two schedules. First hide from passed. Course visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now -  5 * MINSECS, $now + 5 * MINSECS);
        self::add_schedule($course->id, $now + 10 * MINSECS, $now + 15 * MINSECS);
        $expectations[$course->id] = 0;
        $courseids[] = $course->id;

        // 10. Two schedules. First hide until passed. Course visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 10 * MINSECS, $now -  5 * MINSECS);
        self::add_schedule($course->id, $now +  5 * MINSECS, $now + 10 * MINSECS);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;
        
        // 11. Two schedules. Second hide from passed. Course visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 15 * MINSECS, $now - 10 * MINSECS);
        self::add_schedule($course->id, $now -  5 * MINSECS, $now +  5 * MINSECS);
        $expectations[$course->id] = 0;
        $courseids[] = $course->id;

        // 12. Two schedules. Second hide until passed. Course visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 20 * MINSECS, $now - 15 * MINSECS);
        self::add_schedule($course->id, $now - 10 * MINSECS, $now -  5 * MINSECS, 0);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // 13. Two schedules. First hide from not passed. Course NOT visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now +  5 * MINSECS, $now + 10 * MINSECS);
        self::add_schedule($course->id, $now + 15 * MINSECS, $now + 20 * MINSECS);
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // 14. Two schedules. First hide from passed. Course NOT visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now -  5 * MINSECS, $now + 5 * MINSECS);
        self::add_schedule($course->id, $now + 10 * MINSECS, $now + 15 * MINSECS);
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 0;
        $courseids[] = $course->id;

        // 15. Two schedules. First hide until passed. Course NOT visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 10 * MINSECS, $now -  5 * MINSECS);
        self::add_schedule($course->id, $now +  5 * MINSECS, $now + 10 * MINSECS);
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;
        
        // 16. Two schedules. Second hide from passed. Course NOT visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 15 * MINSECS, $now - 10 * MINSECS);
        self::add_schedule($course->id, $now -  5 * MINSECS, $now +  5 * MINSECS);
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 0;
        $courseids[] = $course->id;

        // 17. Two schedules. Second hide until passed. Course NOT visible initially.
        $course = $this->getDataGenerator()->create_course();
        self::add_schedule($course->id, $now - 20 * MINSECS, $now - 15 * MINSECS);
        self::add_schedule($course->id, $now - 10 * MINSECS, $now -  5 * MINSECS, 0);
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 1;
        $courseids[] = $course->id;

        // Run execute.
        $task = new \local_visibility\task\course_visibility_task();
        $task->execute();

        $testcase = 1;
        foreach ($expectations as $courseid => $expectation) {
            $this->assertEquals($expectation, get_course($courseid)->visible,
                    'Failed testcase ' . $testcase);
            ++$testcase;
        }

        // Test determine_visibility() when used for a single course.
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);
            \local_visibility\task\course_visibility_task::determine_visibility($courseid, $course->visible);
            $this->assertEquals($expectations[$courseid], get_course($courseid)->visible,
                    'Failed testcase ' . $testcase);
            ++$testcase;
        }
    }
}