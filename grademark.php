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
$format  = optional_param('format', null, PARAM_ALPHA);

$headings = array(
    "Module Shortcode",
    "Assignment",
    "Students on course",
    "Students with submissions",
    "Students with grades"
);

// We exporting a CSV?
if (isset($format) && $format === "csv") {
    require_once($CFG->libdir . '/csvlib.class.php');

    $assignments = \report_turnitinaudit\grademark::get_assignments(0, 10000);

    $csv = array($headings);

    foreach ($assignments as $data) {
        $row = array();

        $row[] = $data->course_shortname;
        $row[] = $data->assignment_name;
        $row[] = $data->students_on_course;
        $row[] = $data->students_with_submissions;
        $row[] = $data->students_with_grades;

        $csv[] = $row;
    }

    \csv_export_writer::download_array("Turnitin_Grademark_Report", $csv, "comma");
    die;
}

admin_externalpage_setup('reportturnitinauditgrademark', '', null, '', array('pagelayout' => 'report'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('turnitinaudit_grademark', 'report_turnitinaudit'));

$assignment_count = $DB->count_records('turnitintooltwo');

echo "<p>There are $assignment_count turnitintool V2 assignments.</p>";
echo '<p style="float:right"><a href="?format=csv" target="_blank">Download CSV</a></p>';

$table = new html_table();
$table->head = $headings;

$table->colclasses = array('leftalign assignment', 'leftalign students_on_assignment', 'leftalign students_with_grades', 'leftalign students_on_course');
$table->id = 'turnitinaudit_grademark';
$table->attributes['class'] = 'admintable generaltable';
$table->data = array();

$assignments = \report_turnitinaudit\grademark::get_assignments($page, $perpage);

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
