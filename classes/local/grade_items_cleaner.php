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
 * Helper to delete grade items for Stream activities.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stream\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Deletes Stream activity grade items and resets the grade field.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_items_cleaner {

    /**
     * Get Stream activities that have a non-zero grade setting.
     *
     * @param int|int[]|null $courseids Optional course ID, list of IDs, or null for all courses.
     * @return array Array of stream records with course fields.
     */
    public static function get_stream_activities_with_grades($courseids = null): array {
        global $DB;

        $params = [];
        $where = "s.grade <> 0";

        if ($courseids !== null && $courseids !== []) {
            if (!is_array($courseids)) {
                $courseids = [(int) $courseids];
            } else {
                $courseids = array_map('intval', $courseids);
            }
            list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
            $where .= " AND s.course $insql";
            $params += $inparams;
        }

        $sql = "SELECT s.*, c.shortname AS coursename, c.fullname AS coursefullname
                  FROM {stream} s
                  JOIN {course} c ON c.id = s.course
                 WHERE $where
              ORDER BY c.shortname, s.name";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Delete the grade item for a Stream activity and reset its grade field.
     *
     * @param \stdClass $stream The stream activity (must include id and course).
     * @param bool $dryrun Whether this is a dry run.
     * @return array{success: bool, skipped: bool, gradeitemid: int|null, message: string}
     */
    public static function delete_stream_grade_item(\stdClass $stream, bool $dryrun = false): array {
        global $CFG, $DB;

        require_once($CFG->libdir . '/gradelib.php');

        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'stream',
            'iteminstance' => $stream->id,
            'courseid' => $stream->course,
        ]);

        if (!$gradeitem) {
            return [
                'success' => false,
                'skipped' => true,
                'gradeitemid' => null,
                'message' => 'nogradeitem',
            ];
        }

        if ($dryrun) {
            return [
                'success' => true,
                'skipped' => false,
                'gradeitemid' => (int) $gradeitem->id,
                'message' => 'dryrun',
            ];
        }

        $gradeitemid = (int) $gradeitem->id;
        $gradeitem->delete('mod/stream');
        $DB->set_field('stream', 'grade', 0, ['id' => $stream->id]);

        return [
            'success' => true,
            'skipped' => false,
            'gradeitemid' => $gradeitemid,
            'message' => 'deleted',
        ];
    }

    /**
     * Force a full gradebook recalculation for a course.
     *
     * @param int $courseid Course ID.
     * @return array{courseid: int, success: bool, error: string|null}
     */
    public static function regrade_course(int $courseid): array {
        global $CFG;

        require_once($CFG->libdir . '/gradelib.php');

        grade_force_full_regrading($courseid);
        $result = grade_regrade_final_grades($courseid);

        if ($result === true) {
            return [
                'courseid' => $courseid,
                'success' => true,
                'error' => null,
            ];
        }

        $error = is_array($result) ? implode('; ', $result) : (string) $result;

        return [
            'courseid' => $courseid,
            'success' => false,
            'error' => $error,
        ];
    }

    /**
     * Process all matching Stream activities and regrade affected courses.
     *
     * @param int|int[]|null $courseids Optional course ID, list of IDs, or null for all courses.
     * @param bool $dryrun Whether this is a dry run.
     * @return array{processed: int, deleted: int, skipped: int, failed: int, results: array,
     *     regraded: array, regrade_success: int, regrade_failed: int}
     */
    public static function process($courseids = null, bool $dryrun = false): array {
        $streams = self::get_stream_activities_with_grades($courseids);

        $summary = [
            'processed' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'failed' => 0,
            'results' => [],
            'regraded' => [],
            'regrade_success' => 0,
            'regrade_failed' => 0,
        ];

        $affectedcourses = [];

        foreach ($streams as $stream) {
            $entry = [
                'stream' => $stream,
                'success' => false,
                'skipped' => false,
                'gradeitemid' => null,
                'message' => '',
                'error' => null,
            ];

            try {
                $result = self::delete_stream_grade_item($stream, $dryrun);
                $entry = array_merge($entry, $result);

                if ($result['skipped']) {
                    $summary['skipped']++;
                } else if ($result['success']) {
                    $summary['deleted']++;
                    if (!$dryrun) {
                        $affectedcourses[(int) $stream->course] = true;
                    }
                }

                $summary['processed']++;
            } catch (\Throwable $e) {
                $entry['error'] = $e->getMessage();
                $summary['failed']++;
                $summary['processed']++;
            }

            $summary['results'][] = $entry;
        }

        // Recalculate gradebooks for courses where grade items were deleted.
        if (!$dryrun && !empty($affectedcourses)) {
            foreach (array_keys($affectedcourses) as $affectedcourseid) {
                try {
                    $regraderesult = self::regrade_course((int) $affectedcourseid);
                } catch (\Throwable $e) {
                    $regraderesult = [
                        'courseid' => (int) $affectedcourseid,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }

                $summary['regraded'][] = $regraderesult;
                if ($regraderesult['success']) {
                    $summary['regrade_success']++;
                } else {
                    $summary['regrade_failed']++;
                }
            }
        }

        return $summary;
    }
}
