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
 * Turnitintool report - grademark usage
 *
 * @package    report_turnitinaudit
 * @copyright  2014 Jake Blatchford <J.Blatchford@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG, $OUTPUT, $DB;

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');


// page parameters
$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);    // how many per page
$sort    = optional_param('sort', 'timemodified', PARAM_ALPHA);
$dir     = optional_param('dir', 'DESC', PARAM_ALPHA);

admin_externalpage_setup('reportturnitinauditgrademark', '', null, '', array('pagelayout' => 'report'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('turnitinaudit_grademark', 'report_turnitinaudit'));

$sql = <<<SQL
    SELECT 
        COUNT(*)
    FROM
        {turnitintool}
SQL;

$assignment_count = $DB->count_records_sql($sql);

echo "<p>There are $assignment_count turnitintool assignments.</p>";

$table = new html_table();
$table->head = array(
    "Module",
    "Assignment",
    "Students on course",
    "Students with submissions",
    "Students with grades"    
);

$table->colclasses = array('leftalign assignment', 'leftalign students_on_assignment', 'leftalign students_with_grades', 'leftalign students_on_course');
$table->id = 'turnitinaudit_grademark';
$table->attributes['class'] = 'admintable generaltable';
$table->data = array();

$sql = <<<SQL
    SELECT 
        c.shortname as course_shortname,
        a.name as assignment_name,
        a.students_with_submissions,
        a.students_with_grades,
        COUNT(ue.id) as students_on_course
    FROM
        (SELECT 
            counts . *,
                SUM(Case
                    When ts.submission_grade IS NOT NULL THEN 1
                    ELSE 0
                END) as students_with_grades
        FROM
            mdl_turnitintool_submissions ts
        INNER JOIN (SELECT 
            t.id as turnitintool_id,
                t.name as name,
                t.course as course,
                COUNT(ts.id) as students_with_submissions
        FROM
            mdl_turnitintool t
        INNER JOIN mdl_turnitintool_submissions ts ON ts.turnitintoolid = t.id
        GROUP BY t.id) as counts ON counts.turnitintool_id = ts.turnitintoolid
        GROUP BY turnitintoolid) a
            INNER JOIN
        mdl_enrol e ON e.courseid = a.course
            INNER JOIN
        mdl_course c ON c.id = a.course
            INNER JOIN
        mdl_user_enrolments ue ON ue.enrolid = e.id
    WHERE
        e.roleid IN (SELECT 
                id
            FROM
                mdl_role
            WHERE
                shortname = 'student'
                    OR shortname = 'sds_student')
    GROUP BY a.turnitintool_id
SQL;

$assignments = $DB->get_recordset_sql($sql, array(), $page*$perpage, $perpage);

if ($assignments->valid()) {
    foreach ($assignments as $data) {
        $row = array();
        
        $row[] = s($data->course_shortname);
        $row[] = s($data->assignment_name);
        $row[] = s($data->students_on_course);
        $row[] = s($data->students_with_submissions);
        $row[] = s($data->students_with_grades);

        $table->data[] = $row;
    }
}

echo html_writer::table($table);

$baseurl = new moodle_url('grademark.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($assignment_count, $page, $perpage, $baseurl);

echo $OUTPUT->footer();
