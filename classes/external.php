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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/crypt/jwt.php');
require_once(__DIR__ . '/../locallib.php');

/**
 * Class for connecting to a Stream server and handling AJAX calls.
 *
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_stream_external extends external_api {

    /**
     * Connects to a stream and retrieves meta-data about a videos.
     *
     * @return external_function_parameters Parameters for listing instances.
     */
    public static function listing_parameters() {
        return new \external_function_parameters([
                'term' => new \external_value(PARAM_TEXT, 'Search term to filter results.'),
                'courseid' => new \external_value(PARAM_INT, 'the course ID.'),
        ]);
    }

    /**
     * Listing videos.
     *
     * @param string $term
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function listing($term, $courseid) {
        $params = self::validate_parameters(self::listing_parameters(), [
                'term' => $term,
                'courseid' => $courseid
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        require_capability('moodle/course:update', $context);

        $response = mod_stream\stream_video::listing($params['term']);
        return $response;
    }

    /**
     * Listing returns.
     *
     * @return external_single_structure
     */
    public static function listing_returns() {
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
