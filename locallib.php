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
     * @param string $sort
     *
     * @return array
     * @throws \dml_exception
     */
    public static function listing($term, $sort) {
        global $USER;

        $json = self::call([
                'term' => $term,
                'email' => $USER->email,
                'sort' => $sort,
        ]);

        return json_decode($json);
    }

    /**
     * Get video details by a list of identifiers.
     *
     * @param array $identifiers
     * @return array
     * @throws \dml_exception
     */
    public static function get_videos_by_id(array $identifiers) {
        global $USER;

        if (empty($identifiers)) {
            return [];
        }

        $json = self::call([
            'ids' => json_encode($identifiers),
            'email' => $USER->email
        ]);
        $response = json_decode($json);

        if (isset($response->status) && $response->status === 'success' && !empty($response->videos)) {
            // Reorder the videos based on the original identifiers array.
            $videosbyid = [];
            foreach ($response->videos as $video) {
                $videosbyid[$video->id] = $video;
            }

            $orderedvideos = [];
            foreach ($identifiers as $id) {
                if (isset($videosbyid[$id])) {
                    $orderedvideos[] = $videosbyid[$id];
                }
            }
            return $orderedvideos;
        }

        return [];
    }

    /**
     * Call for get player code.
     *
     * @param int $cmid
     * @param string $identifier
     * @param boolean $includeaudio
     * @param boolean $autoplay Whether the player should start automatically.
     *
     * @return string
     * @throws \dml_exception
     */
    public static function player($cmid, $identifier, $includeaudio = false, $autoplay = false) {
        global $USER;

        $config = get_config('stream');
        $payload = [
                'identifier' => $identifier,
                'matricula' => $cmid,
                'fullname' => fullname($USER),
                'email' => $USER->email,
        ];

        $token = jwt_helper::encode($config->accountid, $payload, HOURSECS);

        $html = '';
        $query = 'token=' . urlencode($token);
        if ($autoplay) {
            // Compatible with Stream/VideoTube embeds that read autoplay from the URL.
            $query .= '&autoplay=1';
        }
        $videoallow = "allow='autoplay; fullscreen; encrypted-media; picture-in-picture'";

        if ($includeaudio) {
            $html .= "<div class='stream-background'><iframe width='960px' style='height: 150px !important;' height='100px' frameborder='0' " .
                    "allowfullscreen allow='autoplay; fullscreen; encrypted-media' " .
                    "src='{$config->apiendpoint}/embed-audio/{$identifier}?token=" . urlencode($token) . "'></iframe></div>";
        }

        $html .= "<div class='stream-background'><iframe class='stream-video-iframe' width='960px' height='540px' frameborder='0' " .
                "allowfullscreen {$videoallow} src='{$config->apiendpoint}/embed/{$identifier}?{$query}'></iframe></div>";

        return $html;
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
                    'Accept: application/json',
            ];

            $options = [
                    'CURLOPT_POST' => true,
                    'CURLOPT_RETURNTRANSFER' => true,
                    'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
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
