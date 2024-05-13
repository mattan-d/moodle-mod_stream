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
 * Mobile stream Content
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

ob_start();
header('Access-Control-Allow-Origin: *');

require_once('locallib.php');

$id = required_param('id', PARAM_INT);

// Verify course context.
$cm = get_coursemodule_from_id('stream', $id);
if (!$cm) {
    moodle_exception('invalidcoursemodule');
}
$course = $DB->get_record('course', ['id' => $cm->course]);
if (!$course) {
    moodle_exception('coursemisconf');
}

require_course_login($course, true, $cm, true, true);

$context = context_module::instance($cm->id);
require_capability('mod/stream:view', $context);

$PAGE->set_url(new \moodle_url('/mod/stream/mobile.php', ['id' => $id]));
$PAGE->set_title(format_string($content['title']));
$PAGE->set_heading($course->fullname);

// Embed specific page setup.
$PAGE->add_body_class('stream-embed');
$PAGE->set_pagelayout('embedded');

echo $OUTPUT->header();

if ($stream->intro) {
    echo $OUTPUT->box(format_module_intro('stream', $stream, $cm->id), 'generalbox mod_introbox', 'streamintro');
}

$config = get_config('stream');

$safetyplayer = "";
if ($config->safety) {
    $safety = $config->safety;
    if (strpos($safety, 'profile') === 0) {
        $safety = str_replace('profile_', '', $safety);
        $safetyplayer = $USER->profile[$safety];
    } else {
        $safetyplayer = $USER->$safety;
    }
}

$player = mod_stream\stream_video::player($id, $stream->identifier, $safetyplayer);

echo $OUTPUT->box($player, 'generalbox player', 'streamintro');
echo $OUTPUT->footer();

$html = ob_get_contents();
ob_clean();

$html = preg_replace('/<link.*?theme\/styles.php\/\w+\/\w+\/all"\s+\/>/', '', $html);
echo $html;
