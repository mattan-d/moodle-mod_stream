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
 * Stream view.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT);
$n = optional_param('n', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('stream', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $stream = $DB->get_record('stream', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $stream = $DB->get_record('stream', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $stream->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('stream', $stream->id, $course->id, false, MUST_EXIST);
} else {
    moodle_exception('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_stream\event\course_module_viewed::create([
        'objectid' => $PAGE->cm->instance,
        'context' => $PAGE->context,
]);

$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $stream);
$event->trigger();

$PAGE->set_url('/mod/stream/view.php', ['id' => $cm->id]);
$PAGE->requires->js_call_amd('mod_stream/main');
$PAGE->set_title(format_string($stream->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading($stream->name);
if ($stream->intro) {
    echo $OUTPUT->box(format_module_intro('stream', $stream, $cm->id), 'generalbox mod_introbox', 'streamintro');
}

$safetyplayer = "";

echo mod_stream\stream_video::player($id, $stream->identifier, $safetyplayer);
echo $OUTPUT->footer();
