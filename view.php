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
    throw new \moodle_exception('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$timenow = time();
$available = true;
$availabilitymessage = '';

if ($stream->timeopen > 0 && $timenow < $stream->timeopen) {
    $available = false;
    $availabilitymessage = get_string('activitynotavailableyet', 'stream', userdate($stream->timeopen));
}

if ($stream->timeclose > 0 && $timenow > $stream->timeclose) {
    $available = false;
    $availabilitymessage = get_string('activityclosed', 'stream', userdate($stream->timeclose));
}

// Allow teachers/managers to always view
$context = context_module::instance($cm->id);
if (!$available && !has_capability('mod/stream:addinstance', $context)) {
    $PAGE->set_url('/mod/stream/view.php', ['id' => $cm->id]);
    $PAGE->set_title(format_string($stream->name));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->notification($availabilitymessage, 'notifyproblem');
    echo $OUTPUT->footer();
    exit;
}

$includeaudio = $stream->includeaudio ?? 0;
if (!$includeaudio) {
    $audioplayer_config = get_config('stream', 'audioplayer_idnumbers');
    if (!empty($audioplayer_config) && !empty($course->shortname)) {
        $patterns = array_filter(array_map('trim', explode("\n", $audioplayer_config)));
        foreach ($patterns as $pattern) {
            if (!empty($pattern) && stripos($course->shortname, $pattern) !== false) {
                $includeaudio = 1;
                break;
            }
        }
    }
}

$event = \mod_stream\event\course_module_viewed::create([
        'objectid' => $PAGE->cm->instance,
        'context' => $PAGE->context,
]);

$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $stream);
$event->trigger();

$PAGE->set_url('/mod/stream/view.php', ['id' => $cm->id]);
$PAGE->requires->js_call_amd('mod_stream/playlist', 'init');
$PAGE->set_title(format_string($stream->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

if (trim(strip_tags($stream->intro))) {
    echo $OUTPUT->box(format_module_intro('stream', $stream, $cm->id), 'generalbox', 'intro');
}

$show_noresults_notification = function(array $requestedidentifiers = []) use ($OUTPUT): void {
    if (!empty($requestedidentifiers)) {
        echo $OUTPUT->notification(
                get_string('noresultswithid', 'stream', implode(', ', $requestedidentifiers)),
                'notifyinfo'
        );
    } else {
        echo $OUTPUT->notification(get_string('noresults', 'stream'), 'notifyinfo');
    }
};

// Check if this is collection mode
if ($stream->collection_mode) {
    // Handle collection mode
    $videos = [];

    // Check if we have manual video selection in addition to collection mode
    if (!empty($stream->identifier) && trim($stream->identifier) !== 'auto_collection') {
        // Use manually selected videos
        $identifiers = array_values(array_filter(explode(',', $stream->identifier)));
        if (!empty($identifiers)) {
            $videos = mod_stream\stream_video::get_videos_by_id($identifiers);
        }
    }

    // If no manual selection or collection mode is pure auto, get videos from local_stream plugin
    if (empty($videos)) {
        // Check if local_stream plugin exists and has the required function
        if (function_exists('local_stream_get_course_videos')) {
            $collectionVideos = local_stream_get_course_videos($course->id, $cm->id);
            if (!empty($collectionVideos)) {
                $videos = $collectionVideos;
            }
        } else {
            // Fallback: show message that local_stream plugin is required for auto collection
            if (empty($stream->identifier) || trim($stream->identifier) === 'auto_collection') {
                echo $OUTPUT->notification(
                        get_string('collectionmode_plugin_required', 'stream', 'local_stream'),
                        'notifywarning'
                );
            }
        }
    }

    if (empty($videos)) {
        $show_noresults_notification();
        echo $OUTPUT->footer();
        exit;
    }
} else {
    // Regular mode - get videos from identifier field
    if (empty($stream->identifier) || trim($stream->identifier) === '') {
        $show_noresults_notification();
        echo $OUTPUT->footer();
        exit;
    }

    $identifiers = array_values(array_filter(explode(',', $stream->identifier)));

    if (empty($identifiers)) {
        $show_noresults_notification();
        echo $OUTPUT->footer();
        exit;
    }

    $videos = mod_stream\stream_video::get_videos_by_id($identifiers);

    // Check if we have any videos before proceeding
    if (empty($videos)) {
        $show_noresults_notification($identifiers);
        echo $OUTPUT->footer();
        exit;
    }

}

// Get viewed videos for the current user.
$viewedvideoids = [];
if (!empty($videos)) {
    list($usql, $params) = $DB->get_in_or_equal(array_column($videos, 'id'), SQL_PARAMS_NAMED, 'video');
    $params['userid'] = $USER->id;
    $params['streamid'] = $stream->id;
    $viewedvideos = $DB->get_records_sql("SELECT videoid FROM {stream_viewed_videos}
                                          WHERE userid = :userid AND streamid = :streamid AND videoid $usql", $params);
    $viewedvideoids = array_keys($viewedvideos);
}

$customVideoNames = [];
if (!empty($stream->video_names)) {
    $decoded = json_decode($stream->video_names, true);
    if (is_array($decoded)) {
        $customVideoNames = $decoded;
    }
}

foreach ($videos as $video) {
    $video->viewed = in_array($video->id, $viewedvideoids);
    if (isset($customVideoNames[$video->id]) && !empty($customVideoNames[$video->id])) {
        $video->custom_title = $customVideoNames[$video->id];
        $video->original_title = $video->title;
        $video->title = $customVideoNames[$video->id];
    }
}

// Apply custom order if available and not in collection mode
if (!$stream->collection_mode && !empty($stream->video_order)) {
    $videoOrder = json_decode($stream->video_order, true);
    if (is_array($videoOrder) && !empty($videoOrder)) {
        $orderedVideos = [];
        $videoMap = [];

        // Create a map of video ID to video object
        foreach ($videos as $video) {
            $videoMap[$video->id] = $video;
        }

        // Add videos in the specified order
        foreach ($videoOrder as $videoId) {
            if (isset($videoMap[$videoId])) {
                $orderedVideos[] = $videoMap[$videoId];
                unset($videoMap[$videoId]);
            }
        }

        // Add any remaining videos that weren't in the order
        foreach ($videoMap as $video) {
            $orderedVideos[] = $video;
        }

        $videos = $orderedVideos;
    }
}

// Determine if we should show the playlist sidebar (only show if more than 1 video)
$show_playlist = count($videos) > 1;

// Mark the initially displayed video as viewed (playlist clicks only track on switch).
$initial_player = '';
if (!empty($videos)) {
    stream_mark_video_as_viewed($stream, $USER->id, $videos[0]->id, false);
    $videos[0]->viewed = true;

    $helper = new \mod_stream\stream_video();
    $initial_player = $helper->player($cm->id, $videos[0]->id, $includeaudio);
}

$template_data = [
        'cmid' => $cm->id,
        'includeaudio' => $includeaudio,
        'autoplaynext' => !empty($stream->autoplaynext) ? 1 : 0,
        'videos' => $videos,
        'initial_player' => $initial_player,
        'show_playlist' => $show_playlist,
        'single_video' => !$show_playlist,
        'stream_id' => $stream->id,
        'course_id' => $course->id,
        'collection_mode' => $stream->collection_mode
];

// Add current video (first video by default)
if (!empty($videos)) {
    $template_data['current_video'] = $videos[0];
    // Mark first video as active
    $videos[0]->active = true;
}

echo $OUTPUT->render_from_template('mod_stream/playlist', $template_data);

// Update grades for the current user
stream_update_grades($stream, $USER->id);

echo $OUTPUT->footer();
