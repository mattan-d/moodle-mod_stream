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
 * Admin UI to delete grade items for Stream activities.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('modstreamdeletegradeitems');

$PAGE->set_url(new moodle_url('/mod/stream/admin/delete_grade_items.php'));
$PAGE->set_title(get_string('deletegradeitems', 'mod_stream'));
$PAGE->set_heading(get_string('deletegradeitems', 'mod_stream'));

$mform = new \mod_stream\form\delete_grade_items_form();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletegradeitems', 'mod_stream'));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/category.php', ['category' => 'modsettings']));
} else if ($data = $mform->get_data()) {
    $dryrun = !empty($data->dryrun);
    $courseids = null;

    if (($data->coursescope ?? 'all') === 'selected') {
        $courseids = array_values(array_filter(array_map('intval', (array) ($data->courseids ?? [])), static function($id) {
            return $id > 0;
        }));
    }

    if ($dryrun) {
        echo $OUTPUT->notification(get_string('deletegradeitems_dryrunnotice', 'mod_stream'), 'warning');
    }

    if ($courseids === null) {
        echo $OUTPUT->notification(get_string('deletegradeitems_scope_all', 'mod_stream'), 'info');
    } else {
        echo $OUTPUT->notification(get_string('deletegradeitems_scope_selected', 'mod_stream', count($courseids)), 'info');
    }

    $summary = \mod_stream\local\grade_items_cleaner::process($courseids, $dryrun);

    if ($summary['processed'] === 0) {
        echo $OUTPUT->notification(get_string('deletegradeitems_nonefound', 'mod_stream'), 'info');
    } else {
        $table = new html_table();
        $table->head = [
            get_string('course'),
            get_string('activity'),
            get_string('grade'),
            get_string('status'),
        ];
        $table->attributes['class'] = 'generaltable';

        foreach ($summary['results'] as $entry) {
            $stream = $entry['stream'];
            $courselabel = format_string($stream->coursename) . ' (' . format_string($stream->coursefullname) . ')';
            $activitylabel = format_string($stream->name) . ' (ID: ' . $stream->id . ')';

            if (!empty($entry['error'])) {
                $status = get_string('deletegradeitems_error', 'mod_stream', $entry['error']);
            } else if ($entry['skipped']) {
                $status = get_string('deletegradeitems_skipped', 'mod_stream');
            } else if ($dryrun) {
                $status = get_string('deletegradeitems_woulddelete', 'mod_stream', $entry['gradeitemid']);
            } else {
                $status = get_string('deletegradeitems_deleted', 'mod_stream', $entry['gradeitemid']);
            }

            $table->data[] = [
                $courselabel,
                $activitylabel,
                $stream->grade,
                $status,
            ];
        }

        echo html_writer::table($table);

        echo $OUTPUT->notification(get_string('deletegradeitems_summary', 'mod_stream', (object) [
            'processed' => $summary['processed'],
            'deleted' => $summary['deleted'],
            'skipped' => $summary['skipped'],
            'failed' => $summary['failed'],
        ]), $summary['failed'] > 0 ? 'error' : 'success');

        if (!$dryrun && $summary['deleted'] > 0) {
            if ($summary['regrade_failed'] > 0) {
                echo $OUTPUT->notification(get_string('deletegradeitems_regradesummary', 'mod_stream', (object) [
                    'success' => $summary['regrade_success'],
                    'failed' => $summary['regrade_failed'],
                ]), 'error');
                foreach ($summary['regraded'] as $regrade) {
                    if (!$regrade['success']) {
                        echo $OUTPUT->notification(get_string('deletegradeitems_regradeerror', 'mod_stream', (object) [
                            'courseid' => $regrade['courseid'],
                            'error' => $regrade['error'],
                        ]), 'error');
                    }
                }
            } else {
                echo $OUTPUT->notification(get_string('deletegradeitems_regradesuccess', 'mod_stream',
                        $summary['regrade_success']), 'success');
            }
        }
    }

    echo $OUTPUT->continue_button(new moodle_url('/mod/stream/admin/delete_grade_items.php'));
} else {
    $mform->display();
}

echo $OUTPUT->footer();
