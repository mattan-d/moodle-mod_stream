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
 * Strings for component 'mod_stream', language 'en'.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accountid'] = 'Stream account ID';
$string['accountid_desc'] = '';
$string['apiendpoint'] = 'Stream API Endpoint';
$string['apiendpoint_desc'] = 'Choose which Stream API endpoint the Stream activity will use to connect.';
$string['apiidentifier'] = 'Stream API Identifier';
$string['apiidentifier_desc'] = 'The identifier field to use when making a call to the Stream API';
$string['before'] = 'before';
$string['audioplayer_idnumbers'] = 'Course ID Numbers for Audio Player';
$string['audioplayer_idnumbers_desc'] = 'Enter course ID number patterns (one per line). If a course ID number contains any of these strings, the audio player will be automatically enabled for Stream activities in that course, regardless of the activity setting. Example: AUDIO, MUSIC, LANG will match courses with idnumbers like "AUDIO101", "MUSIC-2024", "LANG_COURSE".';
$string['builtinaudioplayer'] = 'Course Types with Built-in Audio Player';
$string['builtinaudioplayer_desc'] = 'Template for identifying short course names using Regular Expressions.';
$string['collectionmode'] = 'Video Recordings Collection Mode';
$string['collectionmode_desc'] = 'Enable this option to automatically gather new recordings. You can still manually select specific videos to customize your playlist.';
$string['collectionmode_help'] = 'When enabled, this activity can automatically collect new video recordings. You can also manually select specific videos to create a custom playlist. If no manual selection is made, all course recordings will be included automatically. This feature works best with the local_stream plugin installed and configured.';
$string['collectionmode_plugin_required'] = 'The {$a} plugin is required for automatic video collection mode to work.';
$string['connectionfailed'] = 'Connection failed: ';
$string['connectionok'] = 'Connection working.';
$string['connectionsettings'] = 'Connection settings';
$string['connectionsettings_desc'] = 'These settings define how Moodle connects to Stream.';
$string['connectionstatus'] = 'Connection status';
$string['defaultincludeaudio'] = 'Default Include Audio';
$string['defaultincludeaudio_desc'] = 'Set the default value for the "Include Audio" checkbox when creating new Stream activities. When enabled, new activities will have audio enabled by default.';
$string['dragtoorder'] = 'Drag to reorder';
$string['identifier'] = 'Video identifiers';
$string['identifier_help'] = 'The video identifiers from the stream server.';
$string['includeaudio'] = 'Include Audio';
$string['includeaudio_desc'] = 'Enable audio in the video player';
$string['includeaudio_help'] = 'When enabled, the video player will include audio playback. When disabled, videos will play without sound.';
$string['loadind'] = 'Loading video list...';
$string['modulename'] = 'STREAM';
$string['modulename_help'] =
        'Stream is a cutting-edge video platform designed for schools, seamlessly integrating with current systems for optimal teaching. This Moodle plugin brings Stream\'s advanced features into your LMS, enriching the learning journey for both teachers and students.';
$string['modulenameplural'] = 'STREAM';
$string['nametitle'] = 'Title';
$string['noresults'] = 'No results or videos were found.';
$string['noresultswithid'] = 'No results or videos were found. Missing video identifier(s): {$a}';
$string['playlistorder'] = 'Playlist Order';
$string['playlistorder_help'] = 'Drag and drop videos to change their order in the playlist.';
$string['pluginadministration'] = 'Stream administration';
$string['pluginname'] = 'STREAM';
$string['privacy:metadata'] =
        'The STREAM if integrated with <a href="https://stream-platform.cloud">CentricApp</a> will save data for detection of piracy.';
$string['privacy:metadata:stream'] = 'Stream configuration';
$string['privacy:metadata:stream:email'] = 'The Email of the user accessing the Stream server.';
$string['privacy:metadata:stream:fullname'] = 'The full name of the user accessing the Stream server.';
$string['safetyid'] = 'Student ID';
$string['safetynone'] = 'Anything';
$string['search'] = 'Search';
$string['selectedvideos'] = 'Selected Videos';
$string['sortbyname'] = 'Sort by name';
$string['sortbytimecreated'] = 'Sort by date';
$string['sortbyview'] = 'Sort by views';
$string['sortbysize'] = 'Sort by size';
$string['stream'] = 'STREAM';
$string['stream:addinstance'] = 'Add a new STREAM';
$string['stream:view'] = 'View STREAM';
$string['topic'] = 'Video Topic';
$string['topic_help'] = 'the video topic from stream server.';
$string['upload'] = 'Upload';
$string['viewed'] = 'Viewed';
$string['views'] = 'Views';
$string['activityclosed'] = 'This activity closed on {$a}';
$string['activitynotavailableyet'] = 'This activity will be available from {$a}';
$string['timeclose'] = 'Close time';
$string['timeclose_help'] = 'Students will not be able to view the activity after this time. If disabled, the activity will remain available indefinitely.';
$string['timeopen'] = 'Open time';
$string['timeopen_help'] = 'Students will only be able to view the activity from this time onwards. If disabled, the activity will be available immediately.';
$string['timing'] = 'Timing';
$string['courseid'] = 'Course ID';
$string['courseid_help'] = 'Optional. Enter a course ID to process only Stream activities in that course. Leave empty to process all courses.';
$string['courseids'] = 'Courses';
$string['courseids_help'] = 'Search and select one or more courses to process. Only Stream activities in the selected courses will be included.';
$string['coursenotfound'] = 'Course with ID {$a} was not found.';
$string['coursescope'] = 'Course scope';
$string['coursescope_help'] = 'Choose whether to process Stream activities in all courses on the site, or only in specific courses selected below.';
$string['coursesselectionrequired'] = 'Please select at least one course.';
$string['allcourses'] = 'All courses';
$string['selectedcourses'] = 'Selected courses';
$string['searchcourses'] = 'Search for courses...';
$string['deletegradeitems'] = 'STREAM: Delete grade items';
$string['deletegradeitems_desc'] = 'Find Stream activities with grade items and delete them from the gradebook. This also resets the grade setting on each activity to 0 and recalculates the gradebook for each affected course. Restricted to site administrators.';
$string['deletegradeitems_deleted'] = 'Deleted grade item ID {$a}';
$string['deletegradeitems_dryrunnotice'] = 'Dry run mode — no changes were made.';
$string['deletegradeitems_error'] = 'Error: {$a}';
$string['deletegradeitems_link'] = 'Open delete grade items tool';
$string['deletegradeitems_nonefound'] = 'No Stream activities with grade items were found.';
$string['deletegradeitems_scope_all'] = 'Scope: all courses';
$string['deletegradeitems_scope_selected'] = 'Scope: {$a} selected course(s)';
$string['deletegradeitems_skipped'] = 'Skipped — no grade item found';
$string['deletegradeitems_summary'] = 'Processed: {$a->processed}. Deleted: {$a->deleted}. Skipped: {$a->skipped}. Failed: {$a->failed}.';
$string['deletegradeitems_woulddelete'] = 'Would delete grade item ID {$a}';
$string['deletegradeitems_regradesuccess'] = 'Gradebooks recalculated successfully for {$a} course(s).';
$string['deletegradeitems_regradesummary'] = 'Gradebook recalculation finished. Success: {$a->success}. Failed: {$a->failed}.';
$string['deletegradeitems_regradeerror'] = 'Failed to recalculate gradebook for course {$a->courseid}: {$a->error}';
$string['dryrun'] = 'Dry run';
$string['dryrun_help'] = 'When enabled, the tool only shows what would be deleted without making any changes. Disable this to perform the actual deletion.';
$string['run'] = 'Run';
$string['tools'] = 'Tools';
$string['tools_desc'] = 'Administrative maintenance tools for Stream.';
