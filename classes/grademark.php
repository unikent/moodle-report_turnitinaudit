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
 * Grademark functions for Turnitin Audit Report
 *
 * @package    report_turnitinaudit
 * @copyright  2014 Jake Blatchford <J.Blatchford@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_turnitinaudit;

defined('MOODLE_INTERNAL') || die();

class grademark {
    public static function get_assignments($page, $perpage) {
        global $DB;

        $sql =
<<<SQL
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
                    {turnitintooltwo_submissions} ts
                INNER JOIN (SELECT
                    t.id as turnitintooltwo_id,
                        t.name as name,
                        t.course as course,
                        COUNT(ts.id) as students_with_submissions
                FROM
                    {turnitintooltwo} t
                INNER JOIN {turnitintooltwo_submissions} ts ON ts.turnitintooltwoid = t.id
                GROUP BY t.id) as counts ON counts.turnitintooltwo_id = ts.turnitintooltwoid
                GROUP BY turnitintooltwoid) a
                    INNER JOIN
                {enrol} e ON e.courseid = a.course
                    INNER JOIN
                {course} c ON c.id = a.course
                    INNER JOIN
                {user_enrolments} ue ON ue.enrolid = e.id
            WHERE
                e.roleid IN (SELECT
                        id
                    FROM
                        {role}
                    WHERE
                        shortname = 'student'
                            OR shortname = 'sds_student')
            GROUP BY a.turnitintooltwo_id
SQL;

        return $DB->get_records_sql($sql, array(), $page * $perpage, $perpage);
    }
}
