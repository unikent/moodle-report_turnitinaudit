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
$dir     = optional_param('dir', 'DESC', PARAM_ALPHA) == 'DESC' ? 'DESC' : 'ASC';

admin_externalpage_setup('reportturnitinaudit', '', null, '', array('pagelayout' => 'report'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('turnitinaudit', 'report_turnitinaudit'));

$changescount = \report_turnitinaudit\report::count_assignments();

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

foreach ($columns as $column => $strcolumn) {
    if ($sort != $column) {
        $columnicon = '';
        if ($column == 'lastaccess') {
            $columndir = 'DESC';
        } else {
            $columndir = 'ASC';
        }
    } else {
        $columndir = $dir == 'ASC' ? 'DESC' : 'ASC';
        if ($column == 'lastaccess') {
            $columnicon = $dir == 'ASC' ? 'up' : 'down';
        } else {
            $columnicon = $dir == 'ASC' ? 'down' : 'up';
        }
        $columnicon = " <img src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

    }

    $url = new \moodle_url('/report/turnitinaudit/index.php', array(
        'sort' => $column,
        'dir' => $columndir,
        'page' => $page,
        'perpage' => $perpage
    ));
    $hcolumns[$column] = \html_writer::link($url, $strcolumn) . $columnicon;
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
$result = \report_turnitinaudit\report::get_assignments($orderby, $page, $perpage);
foreach ($result as $data) {
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

echo html_writer::table($table);

echo $OUTPUT->footer();
