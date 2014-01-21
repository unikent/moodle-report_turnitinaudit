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
 * Config changes report
 *
 * @package    report
 * @subpackage turnitinaudit
 * @copyright  2009 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG, $OUTPUT, $DB;

require dirname(__FILE__).'/../../config.php';
require_once $CFG->libdir.'/adminlib.php';

// page parameters
$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);    // how many per page
$sort    = optional_param('sort', 'timemodified', PARAM_ALPHA);
$dir     = optional_param('dir', 'DESC', PARAM_ALPHA);

admin_externalpage_setup('reportturnitinaudit', '', null, '', array('pagelayout'=>'report'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('turnitinaudit', 'report_turnitinaudit'));

$sql = <<<SQL
    SELECT COUNT(DISTINCT cc.id)
        FROM {course_categories} cc
            JOIN {course_categories} cco
                ON concat(cc.path,'/') LIKE concat(cco.path,'/%')
                    AND cco.name<>'Removed'
            JOIN {course} c
                ON c.category = cc.id
            JOIN {course_modules} cm
                ON cm.course = c.id
            JOIN {modules} m
                ON m.id = cm.module
                    AND m.name='turnitintool'
SQL;

$changescount = $DB->count_records_sql($sql);

$columns = array('name'    => get_string('catname', 'report_turnitinaudit'),
    'shortname'     => get_string('shortname', 'report_turnitinaudit'),
    'tii_name' => get_string('tii_name', 'report_turnitinaudit'),
    'tii_parts'       => get_string('tii_parts', 'report_turnitinaudit'),
    'tii_anon'         => get_string('tii_anon', 'report_turnitinaudit'),
    'tii_allowlate'        => get_string('tii_allowlate', 'report_turnitinaudit'),
    'tii_reportgenspeed'     => get_string('tii_reportgenspeed', 'report_turnitinaudit'),
    'tii_submitpapersto'     => get_string('tii_submitpapersto', 'report_turnitinaudit'),
    'tii_studentorigreports'     => get_string('tii_studentorigreports', 'report_turnitinaudit'),
    'tii_restrict_access'     => get_string('tii_restrict_access', 'report_turnitinaudit'),
);
$hcolumns = array();


if (!isset($columns[$sort])) {
    $sort = 'cc.path, c.shortname';
}

foreach ($columns as $column=>$strcolumn) {
    if ($sort != $column) {
        $columnicon = '';
        if ($column == 'lastaccess') {
            $columndir = 'DESC';
        } else {
            $columndir = 'ASC';
        }
    } else {
        $columndir = $dir == 'ASC' ? 'DESC':'ASC';
        if ($column == 'lastaccess') {
            $columnicon = $dir == 'ASC' ? 'up':'down';
        } else {
            $columnicon = $dir == 'ASC' ? 'down':'up';
        }
        $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

    }
    $hcolumns[$column] = "<a href=\"index.php?sort=$column&amp;dir=$columndir&amp;page=$page&amp;perpage=$perpage\">".$strcolumn."</a>$columnicon";
}

$baseurl = new moodle_url('index.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($changescount, $page, $perpage, $baseurl);

$table = new html_table();
$table->head  = array($hcolumns['name'], $hcolumns['shortname'], $hcolumns['tii_name'], $hcolumns['tii_parts'], $hcolumns['tii_anon'], $hcolumns['tii_allowlate'],
    $hcolumns['tii_reportgenspeed'], $hcolumns['tii_submitpapersto'], $hcolumns['tii_studentorigreports'], $hcolumns['tii_restrict_access']);
$table->colclasses = array('leftalign name', 'leftalign shortname', 'leftalign tii_name', 'leftalign tii_parts', 'leftalign tii_anon', 'leftalign tii_allowlate');
$table->id = 'turnitinaudit';
$table->attributes['class'] = 'admintable generaltable';
$table->data  = array();

$orderby = "$sort $dir";

$sql = <<< SQL
SELECT GROUP_CONCAT(DISTINCT cco.name ORDER BY cco.path SEPARATOR ' / ') name,
c.shortname, t.name tii_name, t.numparts tii_parts,
CASE t.anon WHEN 1 THEN 'Yes' ELSE 'No' END tii_anon,
CASE t.allowlate WHEN 1 THEN 'Yes' ELSE 'No' END  tii_allowlate,
CASE t.reportgenspeed
WHEN 0 THEN 'Generate reports immediately, first report is final'
WHEN 1 THEN 'Generate reports immediately, reports can be overwritten until due date'
ELSE 'Generate reports on due date' END  tii_reportgenspeed,
CASE t.submitpapersto
WHEN 0 THEN 'No Repository'
WHEN 1 THEN 'Standard Repository'
ELSE 'Institutional Repository (Where Applicable)' END  tii_submitpapersto,
CASE t.studentreports WHEN 1 THEN 'Yes' ELSE 'No' END  tii_studentorigreports,
CASE WHEN availablefrom >0 OR availableuntil >0 THEN 'Yes' ELSE 'No' END tii_restrict_access
FROM {course} c
JOIN {course_categories} cc
ON c.category = cc.id
JOIN {course_categories} cco
ON concat(cc.path,'/') like concat(cco.path,'/%')
AND cco.name<>'Removed'
JOIN {course_modules} cm
ON cm.course = c.id
JOIN {modules} m
ON m.id = cm.module
AND m.name='turnitintool'
JOIN {turnitintool} t
ON c.id=t.course
AND cm.instance = t.id
GROUP BY shortname,tii_name
ORDER BY $orderby
SQL;

$rs = $DB->get_recordset_sql($sql, array(), $page*$perpage, $perpage);

if ($rs->valid()) {
    foreach ($rs as $data) {
        $row = array();
        $row[] = s($data->name);
        $row[] = s($data->shortname);
        $row[] = s($data->tii_name);
        $row[] = s($data->tii_parts);
        $row[] = s($data->tii_anon);
        $row[] = s($data->tii_allowlate);
        $row[] = s($data->tii_reportgenspeed);
        $row[] = s($data->tii_submitpapersto);
        $row[] = s($data->tii_studentorigreports);
        $row[] = s($data->tii_restrict_access);

        $table->data[] = $row;
    }
}

$rs->close();

echo html_writer::table($table);

echo $OUTPUT->footer();
