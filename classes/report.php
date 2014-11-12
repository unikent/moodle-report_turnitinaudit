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
 * Turnitin Audit Report
 *
 * @package    report_turnitinaudit
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_turnitinaudit;

defined('MOODLE_INTERNAL') || die();

class report {
    /**
     * Returns a list of Turnitin v2 assignments.
     */
    public static function get_assignments($orderby, $page, $perpage) {
        global $DB;

        $sql = <<< SQL
            SELECT
                GROUP_CONCAT(DISTINCT cco.name ORDER BY cco.path SEPARATOR ' / ') name,
                c.shortname,
                t.name tii_name,
                t.numparts tii_parts,
                CASE t.anon
                    WHEN 1 THEN 'Yes'
                    ELSE 'No'
                END tii_anon,
                CASE
                    t.allowlate WHEN 1 THEN 'Yes'
                    ELSE 'No'
                END  tii_allowlate,
                CASE
                    t.reportgenspeed
                    WHEN 0 THEN 'Generate reports immediately, first report is final'
                    WHEN 1 THEN 'Generate reports immediately, reports can be overwritten until due date'
                    ELSE 'Generate reports on due date'
                END  tii_reportgenspeed,
                CASE t.submitpapersto
                    WHEN 0 THEN 'No Repository'
                    WHEN 1 THEN 'Standard Repository'
                    ELSE 'Institutional Repository (Where Applicable)'
                END  tii_submitpapersto,
                CASE t.studentreports
                    WHEN 1 THEN 'Yes'
                    ELSE 'No'
                END  tii_studentorigreports,
                CASE
                    WHEN availablefrom > 0 OR availableuntil > 0 THEN 'Yes'
                    ELSE 'No'
                END tii_restrict_access
            FROM {course} c
                JOIN {course_categories} cc
                    ON c.category = cc.id
                JOIN {course_categories} cco
                    ON CONCAT(cc.path,'/') like CONCAT(cco.path,'/%')
                        AND cco.name <> 'Removed'
                JOIN {course_modules} cm
                    ON cm.course = c.id
                JOIN {modules} m
                    ON m.id = cm.module
                        AND m.name = 'turnitintool'
                JOIN {turnitintool} t
                    ON c.id=t.course
                        AND cm.instance = t.id
            GROUP BY shortname, tii_name
            ORDER BY $orderby
SQL;

        return $DB->get_records_sql($sql, array(), $page * $perpage, $perpage);
    }
}