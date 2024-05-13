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
 * Mobile class.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stream\output;

/**
 * Class mobile
 *
 * Provides methods for generating output data for mobile views of course content.
 *
 * @package mod_stream
 */
class mobile {

    /**
     * Generate output data for the mobile view of a course.
     *
     * @param array $args Arguments for generating the mobile view.
     *                    - 'cmid' (int): Course module ID.
     *                    - 'wstoken' (string): Session token.
     *
     * @return array Output data for the mobile view.
     *               - 'templates' (array): Array of templates to be rendered.
     *                                      Each template includes an ID and HTML content.
     *               - 'javascript' (string): JavaScript code to be included in the output.
     *               - 'otherdata' (mixed): Additional data (if any).
     *               - 'files' (array): Array of files to be included in the output.
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $_COOKIE;

        $data = [
                'cmid' => $args['cmid'],
        ];

        return [
                'templates' => [
                        [
                                'id' => 'main',
                                'html' => json_encode($args),
                        ],
                ],
                'javascript' => file_get_contents(__DIR__ . '/mobile.js'),
                'otherdata' => '',
                'files' => [],
        ];
    }
}
