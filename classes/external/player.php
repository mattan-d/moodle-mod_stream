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
 * External API for stream player.
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

require_once($CFG->dirroot . '/mod/stream/locallib.php');

class player extends external_api {

    public static function get_player_html_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'identifier' => new external_value(PARAM_RAW, 'The video identifier'),
            'includeaudio' => new external_value(PARAM_BOOL, 'Whether to include the audio player'),
        ]);
    }

    public static function get_player_html($cmid, $identifier, $includeaudio) {
        $params = self::validate_parameters(self::get_player_html_parameters(), [
            'cmid' => $cmid,
            'identifier' => $identifier,
            'includeaudio' => $includeaudio,
        ]);

        $cm = get_coursemodule_from_id('stream', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        //require_capability('mod/stream:view', $context);

        return [
            'html' => \mod_stream\stream_video::player($params['cmid'], $params['identifier'], $params['includeaudio'])
        ];
    }

    public static function get_player_html_returns() {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'The HTML for the video player')
        ]);
    }
}
