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
 * External API for tracking video views.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stream\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_module;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/stream/lib.php');

class tracker extends external_api {

    public static function mark_as_viewed_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'videoid' => new external_value(PARAM_RAW, 'The video identifier'),
        ]);
    }

    public static function mark_as_viewed($cmid, $videoid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::mark_as_viewed_parameters(), [
            'cmid' => $cmid,
            'videoid' => $videoid,
        ]);

        $cm = get_coursemodule_from_id('stream', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        //require_capability('mod/stream:view', $context);

        $stream = $DB->get_record('stream', ['id' => $cm->instance], '*', MUST_EXIST);

        // Check if already viewed.
        $record = $DB->get_record('stream_viewed_videos', [
            'streamid' => $stream->id,
            'userid' => $USER->id,
            'videoid' => $params['videoid'],
        ]);

        if (!$record) {
            $newrecord = new \stdClass();
            $newrecord->streamid = $stream->id;
            $newrecord->userid = $USER->id;
            $newrecord->videoid = $params['videoid'];
            $newrecord->timeviewed = time();
            $DB->insert_record('stream_viewed_videos', $newrecord);

            // Update grade.
            stream_update_grades($stream, $USER->id);
        }

        return ['status' => 'ok'];
    }

    public static function mark_as_viewed_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of the operation')
        ]);
    }
}
