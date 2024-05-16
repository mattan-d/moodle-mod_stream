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
 * mod_stream module external API
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stream\external;

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/stream/locallib.php');

/**
 * Class for connecting to a Stream server and handling AJAX calls.
 *
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class listing extends \external_api {

    /**
     * Connects to a stream and retrieves meta-data about a videos.
     *
     * @return external_function_parameters Parameters for listing instances.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'term' => new external_value(PARAM_TEXT, 'Search term to filter results.'),
                'courseid' => new external_value(PARAM_INT, 'the course ID.'),
        ]);
    }

    /**
     * Listing videos.
     *
     * @param string $term
     * @param int $courseid
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function execute($term, $courseid) {
        $params = self::validate_parameters(self::execute_parameters(), [
                'term' => $term,
                'courseid' => $courseid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        require_capability('moodle/course:update', $context);

        $helper = new \mod_stream\stream_video();
        $response = $helper->listing($params['term']);

        return $response;
    }

    /**
     * Listing returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
                'status' => new external_value(PARAM_TEXT, 'Status of the request.'),
                'videos' => new external_multiple_structure(
                        new external_single_structure(
                                [
                                        'id' => new external_value(PARAM_TEXT, 'ID of the video.'),
                                        'title' => new external_value(PARAM_TEXT, 'Title of the video.'),
                                        'thumbnail' => new external_value(PARAM_TEXT, 'Thumbnail URL of the video.'),
                                        'size' => new external_value(PARAM_INT, 'Size of the video in bytes.'),
                                        'date' => new external_value(PARAM_INT, 'Date of the video in Unix timestamp format.'),
                                        'duration' => new external_value(PARAM_TEXT, 'Duration of the video.'),
                                        'source' => new external_value(PARAM_TEXT, 'Source URL of the video.'),
                                        'author' => new external_value(PARAM_INT, 'ID of the author of the video.'),
                                        'datecreated' => new external_value(PARAM_INT,
                                                'Date the video was created in Unix timestamp format.'),
                                ]
                        )
                ),
        ]);
    }
}
