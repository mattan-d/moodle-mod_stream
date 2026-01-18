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
 * CLI script to convert Stream playlist activities to single video activities.
 *
 * This script will:
 * 1. Find all Stream activities that have multiple videos (playlists)
 * 2. Create separate Stream activities for each video in the playlist
 * 3. Preserve the original activity settings and user progress
 * 4. Optionally remove the original playlist activity
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/stream/lib.php');
require_once($CFG->dirroot . '/mod/stream/locallib.php'); // Added require statement
require_once($CFG->dirroot . '/course/modlib.php');

// Get CLI options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'courseid' => null,
        'streamid' => null,
        'dry-run' => false,
        'remove-original' => false,
        'remove-only' => false,
        'verbose' => false,
    ],
    [
        'h' => 'help',
        'c' => 'courseid',
        's' => 'streamid',
        'd' => 'dry-run',
        'r' => 'remove-original',
        'o' => 'remove-only',
        'v' => 'verbose',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Convert Stream playlist activities to single video activities.

This script will find Stream activities that contain multiple videos (playlists)
and create separate Stream activities for each video in the playlist.

Options:
-h, --help              Print out this help
-c, --courseid=ID       Only process activities in the specified course
-s, --streamid=ID       Only process the specified Stream activity
-d, --dry-run           Show what would be done without making changes
-r, --remove-original   Remove the original playlist activity after conversion
-o, --remove-only       Only remove original playlist activities (no conversion)
-v, --verbose           Show detailed output

Example:
\$sudo -u www-data /usr/bin/php mod/stream/cli/convert_playlist_to_single.php
\$sudo -u www-data /usr/bin/php mod/stream/cli/convert_playlist_to_single.php --courseid=123
\$sudo -u www-data /usr/bin/php mod/stream/cli/convert_playlist_to_single.php --streamid=456 --dry-run
\$sudo -u www-data /usr/bin/php mod/stream/cli/convert_playlist_to_single.php --remove-original --verbose
\$sudo -u www-data /usr/bin/php mod/stream/cli/convert_playlist_to_single.php --remove-only --courseid=123
";

    echo $help;
    die;
}

// Ensure we are running as admin.
\core\session\manager::set_user(get_admin());

/**
 * Get Stream activities that are playlists (have multiple videos).
 *
 * @param int|null $courseid Optional course ID to filter by
 * @param int|null $streamid Optional stream ID to filter by
 * @return array Array of stream records
 */
function get_playlist_activities($courseid = null, $streamid = null) {
    global $DB;
    
    $params = [];
    $where = "1=1";
    
    if ($courseid) {
        $where .= " AND s.course = :courseid";
        $params['courseid'] = $courseid;
    }
    
    if ($streamid) {
        $where .= " AND s.id = :streamid";
        $params['streamid'] = $streamid;
    }
    
    $sql = "SELECT s.*, c.shortname as coursename
            FROM {stream} s
            JOIN {course} c ON c.id = s.course
            WHERE $where
            ORDER BY s.course, s.name";
    
    $streams = $DB->get_records_sql($sql, $params);
    
    // Filter to only include activities with multiple videos
    $playlists = [];
    foreach ($streams as $stream) {
        if (!empty($stream->identifier) && $stream->identifier !== 'auto_collection') {
            $identifiers = array_filter(explode(',', $stream->identifier));
            if (count($identifiers) > 1) {
                $stream->video_count = count($identifiers);
                $stream->video_ids = $identifiers;
                $playlists[] = $stream;
            }
        }
    }
    
    return $playlists;
}

/**
 * Create a single video Stream activity from a playlist video.
 *
 * @param object $original_stream The original playlist stream activity
 * @param string $video_id The video ID for the new activity
 * @param int $video_index The index of the video in the playlist (for naming)
 * @param bool $dry_run Whether this is a dry run
 * @return object|null The new stream activity or null if dry run
 */
function create_single_video_activity($original_stream, $video_id, $video_index, $dry_run = false) {
    global $DB, $CFG;
    
    // Get video details to use the actual video title
    $video_details = null;
    try {
        $videos = mod_stream\stream_video::get_videos_by_id([$video_id]);
        if (!empty($videos)) {
            $video_details = $videos[0];
        }
    } catch (Exception $e) {
        // If we can't get video details, we'll use a fallback name
        if ($dry_run) {
            echo "    Warning: Could not fetch video details for {$video_id}: " . $e->getMessage() . "\n";
        }
    }
    
    // Determine the activity name
    if ($video_details && !empty($video_details->title)) {
        $activity_name = $video_details->title;
    } else {
        // Fallback to playlist name with video number
        $activity_name = $original_stream->name . " - Video " . ($video_index + 1);
    }
    
    if ($dry_run) {
        return (object)[
            'name' => $activity_name,
            'identifier' => $video_id,
            'video_title' => $video_details ? $video_details->title : 'Unknown'
        ];
    }
    
    // Get the course module for the original activity
    $original_cm = get_coursemodule_from_instance('stream', $original_stream->id, $original_stream->course);
    if (!$original_cm) {
        throw new Exception("Could not find course module for stream {$original_stream->id}");
    }
    
    // Create new stream record
    $new_stream = new stdClass();
    $new_stream->course = $original_stream->course;
    $new_stream->name = $activity_name;
    $new_stream->identifier = $video_id;
    $new_stream->video_order = json_encode([$video_id]);
    $new_stream->collection_mode = 0; // Single video, not collection mode
    $new_stream->intro = $original_stream->intro;
    $new_stream->introformat = $original_stream->introformat;
    $new_stream->grade = $original_stream->grade;
    $new_stream->timecreated = time();
    $new_stream->timemodified = time();
    
    // Insert the new stream record
    $new_stream->id = $DB->insert_record('stream', $new_stream);
    
    // Create the course module
    $moduleinfo = new stdClass();
    $moduleinfo->course = $original_stream->course;
    $moduleinfo->module = $original_cm->module;
    $moduleinfo->modulename = 'stream';
    $moduleinfo->instance = $new_stream->id;
    $moduleinfo->section = $original_cm->section;
    $moduleinfo->visible = $original_cm->visible;
    $moduleinfo->visibleoncoursepage = $original_cm->visibleoncoursepage;
    $moduleinfo->groupmode = $original_cm->groupmode;
    $moduleinfo->groupingid = $original_cm->groupingid;
    $moduleinfo->completion = $original_cm->completion;
    $moduleinfo->completionview = $original_cm->completionview;
    $moduleinfo->completionexpected = $original_cm->completionexpected;
    $moduleinfo->availabilityconditionsjson = $original_cm->availabilityconditionsjson;
    
    // Copy all the stream-specific fields
    foreach ($new_stream as $key => $value) {
        if ($key !== 'id') {
            $moduleinfo->$key = $value;
        }
    }
    
    // Add the module to the course
    $new_cm = add_moduleinfo($moduleinfo, get_course($original_stream->course));
    
    // Update grade item
    stream_grade_item_update($new_stream);
    
    return $new_stream;
}

/**
 * Copy user progress from original playlist to new single video activities.
 *
 * @param object $original_stream The original playlist stream activity
 * @param array $new_streams Array of new single video stream activities
 * @param bool $dry_run Whether this is a dry run
 */
function copy_user_progress($original_stream, $new_streams, $dry_run = false) {
    global $DB;
    
    if ($dry_run) {
        return;
    }
    
    // Get all user progress for the original stream
    $viewed_videos = $DB->get_records('stream_viewed_videos', ['streamid' => $original_stream->id]);
    
    foreach ($viewed_videos as $viewed) {
        // Find which new stream this video belongs to
        foreach ($new_streams as $new_stream) {
            if ($new_stream->identifier === $viewed->videoid) {
                // Copy the progress to the new stream
                $new_progress = new stdClass();
                $new_progress->streamid = $new_stream->id;
                $new_progress->userid = $viewed->userid;
                $new_progress->videoid = $viewed->videoid;
                $new_progress->timeviewed = $viewed->timeviewed;
                
                $DB->insert_record('stream_viewed_videos', $new_progress);
                
                // Update grades for this user in the new activity
                stream_update_grades($new_stream, $viewed->userid);
                break;
            }
        }
    }
}

/**
 * Remove the original playlist activity.
 *
 * @param object $original_stream The original playlist stream activity
 * @param bool $dry_run Whether this is a dry run
 */
function remove_original_activity($original_stream, $dry_run = false) {
    global $CFG;
    
    if ($dry_run) {
        return;
    }
    
    require_once($CFG->dirroot . '/course/lib.php');
    
    $cm = get_coursemodule_from_instance('stream', $original_stream->id, $original_stream->course);
    if ($cm) {
        course_delete_module($cm->id);
    }
}

// Main execution
cli_heading('Stream Playlist to Single Video Converter');

$courseid = $options['courseid'] ? (int)$options['courseid'] : null;
$streamid = $options['streamid'] ? (int)$options['streamid'] : null;
$dry_run = $options['dry-run'];
$remove_original = $options['remove-original'];
$remove_only = $options['remove-only'];
$verbose = $options['verbose'];

// Validate options
if ($remove_only && $remove_original) {
    cli_error('Cannot use both --remove-original and --remove-only options together.');
}

if ($dry_run) {
    cli_writeln('DRY RUN MODE - No changes will be made');
    cli_writeln('');
}

if ($remove_only) {
    cli_writeln('REMOVE ONLY MODE - Original playlist activities will be removed without conversion');
    cli_writeln('');
}

// Get playlist activities
cli_writeln('Finding Stream playlist activities...');
$playlists = get_playlist_activities($courseid, $streamid);

if (empty($playlists)) {
    cli_writeln('No playlist activities found.');
    exit(0);
}

cli_writeln('Found ' . count($playlists) . ' playlist activities to process.');
cli_writeln('');

$total_converted = 0;
$total_created = 0;
$total_removed = 0;

foreach ($playlists as $playlist) {
    cli_writeln("Processing: {$playlist->name} (ID: {$playlist->id}) in course {$playlist->coursename}");
    cli_writeln("  Contains {$playlist->video_count} videos");
    
    if ($verbose) {
        cli_writeln("  Video IDs: " . implode(', ', $playlist->video_ids));
    }
    
    try {
        $new_streams = [];
        
        if (!$remove_only) {
            // Create individual activities for each video
            foreach ($playlist->video_ids as $index => $video_id) {
                $video_id = trim($video_id);
                if (empty($video_id)) {
                    continue;
                }
                
                if ($verbose) {
                    cli_writeln("  Creating activity for video: {$video_id}");
                }
                
                $new_stream = create_single_video_activity($playlist, $video_id, $index, $dry_run);
                
                if ($dry_run) {
                    cli_writeln("    Would create: {$new_stream->name}");
                    if ($verbose && $new_stream->video_title !== 'Unknown') {
                        cli_writeln("      Video title: {$new_stream->video_title}");
                    }
                    $total_created++;
                } else {
                    if ($new_stream) {
                        $new_streams[] = $new_stream;
                        $total_created++;
                        if ($verbose) {
                            cli_writeln("    Created: {$new_stream->name}");
                        }
                    }
                }
            }
            
            // Copy user progress
            if (!empty($new_streams)) {
                if ($verbose) {
                    cli_writeln("  Copying user progress...");
                }
                copy_user_progress($playlist, $new_streams, $dry_run);
            }
        }
        
        // Remove original activity if requested or if remove-only mode
        if ($remove_original || $remove_only) {
            if ($verbose) {
                cli_writeln("  Removing original playlist activity...");
            }
            
            if ($dry_run) {
                cli_writeln("    Would remove: {$playlist->name}");
            } else {
                remove_original_activity($playlist, $dry_run);
                if ($verbose) {
                    cli_writeln("    Removed: {$playlist->name}");
                }
            }
            $total_removed++;
        }
        
        if (!$remove_only) {
            $total_converted++;
            cli_writeln("  ✓ Converted successfully");
        } else {
            cli_writeln("  ✓ Removed successfully");
        }
        
    } catch (Exception $e) {
        cli_writeln("  ✗ Error: " . $e->getMessage());
        if ($verbose) {
            cli_writeln("    " . $e->getTraceAsString());
        }
    }
    
    cli_writeln('');
}

// Summary
if ($remove_only) {
    cli_writeln('Removal Summary:');
    cli_writeln("  Playlists removed: {$total_removed}");
} else {
    cli_writeln('Conversion Summary:');
    cli_writeln("  Playlists processed: {$total_converted}");
    cli_writeln("  Single activities created: {$total_created}");
    if ($remove_original) {
        cli_writeln("  Original playlists removed: {$total_removed}");
    }
}

if ($dry_run) {
    cli_writeln('');
    cli_writeln('This was a dry run. To perform the actual operation, run the script without --dry-run');
} else {
    cli_writeln('');
    if ($remove_only) {
        cli_writeln('Removal completed successfully!');
    } else {
        cli_writeln('Conversion completed successfully!');
        
        if (!$remove_original) {
            cli_writeln('Note: Original playlist activities were preserved. Use --remove-original to delete them.');
        }
    }
}

exit(0);
