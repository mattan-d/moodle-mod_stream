<?php
// This file is part of the Zoom plugin for Moodle - http://moodle.org/
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
 * Internal library of functions for module stream
 *
 * All the zoom specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stream;

use mod_stream\local\jwt_helper;

/**
 * Class stream_video.
 *
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stream_video {

    /**
     * Call for list videos in stream.
     *
     * @param string $term
     *
     * @return array
     * @throws dml_exception
     */
    public static function listing($term) {
        global $USER;

        $json = self::call([
                'term' => $term,
                'email' => $USER->email,
        ]);

        return json_decode($json);
    }

    /**
     * Call for get player code.
     *
     * @param int $cmid
     * @param string $identifier
     * @param string $safetyplayer
     *
     * @return string
     * @throws dml_exception
     */
    public static function player($cmid, $identifier, $safetyplayer) {
        global $USER;

        $config = get_config('stream');
        $payload = [
                'identifier' => $identifier,
                'matricula' => $cmid,
                'fullname' => fullname($USER),
                'email' => $USER->email,
                'safetyplayer' => $safetyplayer,
        ];

        $token = jwt_helper::encode($config->token, $payload);

        return "<div id='stream-background'><iframe width='100%' height='600px' frameborder='0' id='stream-video' " .
                "allowfullscreen src='{$config->apiendpoint}/embed/{$identifier}?token={$token}'></iframe></div>";
    }

    /**
     * call execution.
     *
     * @param array $data
     */
    public static function call($data = []) {
        global $CFG;

        $config = get_config('stream');
        if (isset($config->apiendpoint)) {
            $url = $config->apiendpoint . '/webservice/api/v2';

            $headers = [
                    'Authorization: Bearer ' . $config->accountid,
            ];

            $options = [
                    'CURLOPT_POST' => true,
                    'CURLOPT_RETURNTRANSFER' => true,
                    'CURLOPT_HTTPHEADER' => $headers,
            ];

            $curl = new \curl();
            $output = $curl->post($url, $data, $options);
        } else {
            $output = false;
        }

        return $output;
    }
}
