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
     * Always returns a Moodle external-API safe structure.
     *
     * @param string $term
     * @param string $sort
     *
     * @return array
     * @throws \dml_exception
     */
    public static function listing($term, $sort) {
        global $USER;

        $empty = [
                'status' => 'error',
                'videos' => [],
        ];

        $json = self::call([
                'term' => $term,
                'email' => $USER->email,
                'sort' => $sort,
        ]);

        if ($json === false || $json === null || $json === '') {
            return $empty;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return $empty;
        }

        $videos = [];
        if (!empty($decoded['videos']) && is_array($decoded['videos'])) {
            foreach ($decoded['videos'] as $video) {
                if (!is_array($video)) {
                    continue;
                }
                $videos[] = self::normalize_listing_video($video);
            }
        }

        return [
                'status' => !empty($decoded['status']) ? (string) $decoded['status'] : (empty($videos) ? 'error' : 'success'),
                'videos' => $videos,
        ];
    }

    /**
     * Normalize a Stream API video row for Moodle external return validation.
     *
     * @param array $video
     * @return array
     */
    public static function normalize_listing_video(array $video) {
        return [
                'id' => isset($video['id']) ? (string) $video['id'] : '',
                'title' => isset($video['title']) ? (string) $video['title'] : '',
                'thumbnail' => isset($video['thumbnail']) ? (string) $video['thumbnail'] : '',
                'size' => isset($video['size']) ? (int) $video['size'] : 0,
                'date' => isset($video['date']) ? (int) $video['date'] : 0,
                'duration' => isset($video['duration']) ? (string) $video['duration'] : '',
                'source' => isset($video['source']) ? (string) $video['source'] : '',
                'author' => isset($video['author']) ? (int) $video['author'] : 0,
                'datecreated' => isset($video['datecreated']) ? (string) $video['datecreated'] : '',
                'views' => isset($video['views']) ? (int) $video['views'] : 0,
                'elapsed' => isset($video['elapsed']) ? (string) $video['elapsed'] : '',
        ];
    }

    /**
     * Build a minimal video object so the player can still render when metadata is missing.
     *
     * @param string $id
     * @return \stdClass
     */
    public static function make_stub_video($id) {
        $video = new \stdClass();
        $video->id = (string) $id;
        $video->title = 'Video ' . $id;
        $video->thumbnail = '';
        $video->size = 0;
        $video->date = 0;
        $video->duration = '';
        $video->source = '';
        $video->author = 0;
        $video->datecreated = '';
        $video->views = 0;
        $video->elapsed = '';
        return $video;
    }

    /**
     * Get video details by a list of identifiers.
     *
     * Always returns one entry per requested id (API metadata when available,
     * otherwise a stub) so the activity view can still embed the player.
     *
     * @param array $identifiers
     * @return array
     * @throws \dml_exception
     */
    public static function get_videos_by_id(array $identifiers) {
        global $USER;

        $identifiers = array_values(array_filter(array_map(static function($id) {
            return trim((string) $id);
        }, $identifiers), static function($id) {
            return $id !== '' && $id !== 'auto_collection';
        }));

        if (empty($identifiers)) {
            return [];
        }

        $json = self::call([
            'ids' => json_encode(array_values($identifiers)),
            'email' => $USER->email,
        ]);
        $response = json_decode($json);

        $videosbyid = [];
        if (is_object($response) && !empty($response->videos) && is_array($response->videos)) {
            foreach ($response->videos as $video) {
                if (!isset($video->id)) {
                    continue;
                }
                $videosbyid[(string) $video->id] = $video;
            }
        }

        $orderedvideos = [];
        foreach ($identifiers as $id) {
            if (isset($videosbyid[$id])) {
                $video = $videosbyid[$id];
                $video->id = (string) $video->id;
                $orderedvideos[] = $video;
            } else {
                // Keep the activity playable even if Stream metadata lookup failed
                // (privacy filters, API outage, older VideoTube, etc.).
                $orderedvideos[] = self::make_stub_video($id);
            }
        }

        return $orderedvideos;
    }

    /**
     * Call for get player code.
     *
     * @param int $cmid
     * @param string $identifier
     * @param boolean $includeaudio
     * @param boolean $autoplay Whether the player should start automatically.
     * @param boolean $audioonly Whether to show only the audio player (no video).
     *
     * @return string
     * @throws \dml_exception
     */
    public static function player($cmid, $identifier, $includeaudio = false, $autoplay = false, $audioonly = false) {
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

        $showaudio = !empty($includeaudio) || !empty($audioonly);
        if ($showaudio) {
            $html .= "<div class='stream-background stream-audio-player'><iframe class='stream-audio-iframe' width='960px' style='height: 150px !important;' height='100px' frameborder='0' " .
                    "allowfullscreen allow='autoplay; fullscreen; encrypted-media' " .
                    "src='{$config->apiendpoint}/embed-audio/{$identifier}?token=" . urlencode($token) . "'></iframe></div>";
        }

        if (empty($audioonly)) {
            $html .= "<div class='stream-background'><iframe class='stream-video-iframe' width='960px' height='540px' frameborder='0' " .
                    "allowfullscreen {$videoallow} src='{$config->apiendpoint}/embed/{$identifier}?{$query}'></iframe></div>";
        }

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
