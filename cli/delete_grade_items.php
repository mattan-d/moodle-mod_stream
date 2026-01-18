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
 * CLI script to delete grade items for Stream activities.
 *
 * This script will:
 * 1. Find all Stream activities (optionally in a specific course)
 * 2. Delete grade items associated with those activities
 * 3. Reset the grade field to 0 in the stream table
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/mod/stream/lib.php');

// Get CLI options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'courseid' => null,
        'dry-run' => false,
        'verbose' => false,
    ],
    [
        'h' => 'help',
        'c' => 'courseid',
        'd' => 'dry-run',
        'v' => 'verbose',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Delete grade items for Stream activities.

This script will find Stream activities that have grade items
and delete them from the gradebook.

Options:
-h, --help              Print out this help
-c, --courseid=ID       Only process activities in the specified course (if not provided, all courses will be processed)
-d, --dry-run           Show what would be done without making changes
-v, --verbose           Show detailed output

Example:
\$sudo -u www-data /usr/bin/php mod/stream/cli/delete_grade_items.php
\$sudo -u www-data /usr/bin/php mod/stream/cli/delete_grade_items.php --courseid=123
\$sudo -u www-data /usr/bin/php mod/stream/cli/delete_grade_items.php --dry-run --verbose
\$sudo -u www-data /usr/bin/php mod/stream/cli/delete_grade_items.php --courseid=123 --dry-run
";

    echo $help;
    die;
}

// Ensure we are running as admin.
\core\session\manager::set_user(get_admin());

/**
 * Get Stream activities that have grade items.
 *
 * @param int|null $courseid Optional course ID to filter by
 * @return array Array of stream records with grade items
 */
function get_stream_activities_with_grades($courseid = null) {
    global $DB;

    $params = [];
    $where = "s.grade <> 0";

    if ($courseid) {
        $where .= " AND s.course = :courseid";
        $params['courseid'] = $courseid;
    }

    $sql = "SELECT s.*, c.shortname as coursename, c.fullname as coursefullname
            FROM {stream} s
            JOIN {course} c ON c.id = s.course
            WHERE $where
            ORDER BY c.shortname, s.name";

    return $DB->get_records_sql($sql, $params);
}

/**
 * Delete grade item for a Stream activity.
 *
 * @param object $stream The stream activity
 * @param bool $dryrun Whether this is a dry run
 * @param bool $verbose Whether to show verbose output
 * @return bool True if successful
 */
function delete_stream_grade_item($stream, $dryrun = false, $verbose = false) {
    global $DB;

    // Check if grade item exists.
    $gradeitem = grade_item::fetch([
        'itemtype' => 'mod',
        'itemmodule' => 'stream',
        'iteminstance' => $stream->id,
        'courseid' => $stream->course,
    ]);

    if (!$gradeitem) {
        if ($verbose) {
            cli_writeln("    No grade item found for this activity.");
        }
        return false;
    }

    if ($dryrun) {
        if ($verbose) {
            cli_writeln("    Would delete grade item ID: {$gradeitem->id}");
            cli_writeln("    Would reset stream grade field to 0");
        }
        return true;
    }

    // Delete the grade item.
    $gradeitem->delete('mod/stream');

    // Reset the grade field in the stream table.
    $DB->set_field('stream', 'grade', 0, ['id' => $stream->id]);

    if ($verbose) {
        cli_writeln("    Deleted grade item ID: {$gradeitem->id}");
        cli_writeln("    Reset stream grade field to 0");
    }

    return true;
}

// Main execution.
cli_heading('Stream Grade Items Deletion Tool');

$courseid = $options['courseid'] ? (int)$options['courseid'] : null;
$dryrun = $options['dry-run'];
$verbose = $options['verbose'];

if ($dryrun) {
    cli_writeln('DRY RUN MODE - No changes will be made');
    cli_writeln('');
}

if ($courseid) {
    // Verify course exists.
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        cli_error("Course with ID {$courseid} not found.");
    }
    cli_writeln("Processing Stream activities in course: {$course->shortname} ({$course->fullname})");
} else {
    cli_writeln('Processing Stream activities in ALL courses');
}
cli_writeln('');

// Get stream activities with grades.
cli_writeln('Finding Stream activities with grade items...');
$streams = get_stream_activities_with_grades($courseid);

if (empty($streams)) {
    cli_writeln('No Stream activities with grade items found.');
    exit(0);
}

cli_writeln('Found ' . count($streams) . ' Stream activities with grade items.');
cli_writeln('');

$totalprocessed = 0;
$totaldeleted = 0;
$totalfailed = 0;

foreach ($streams as $stream) {
    cli_writeln("Processing: {$stream->name} (ID: {$stream->id})");
    cli_writeln("  Course: {$stream->coursename} ({$stream->coursefullname})");
    cli_writeln("  Current grade setting: {$stream->grade}");

    try {
        $result = delete_stream_grade_item($stream, $dryrun, $verbose);

        if ($result) {
            $totaldeleted++;
            if ($dryrun) {
                cli_writeln("  [DRY RUN] Would delete grade item");
            } else {
                cli_writeln("  Successfully deleted grade item");
            }
        } else {
            cli_writeln("  Skipped - no grade item found");
        }

        $totalprocessed++;

    } catch (Exception $e) {
        cli_writeln("  ERROR: " . $e->getMessage());
        if ($verbose) {
            cli_writeln("    " . $e->getTraceAsString());
        }
        $totalfailed++;
    }

    cli_writeln('');
}

// Summary.
cli_writeln('=== Summary ===');
cli_writeln("Activities processed: {$totalprocessed}");
cli_writeln("Grade items deleted: {$totaldeleted}");
if ($totalfailed > 0) {
    cli_writeln("Failed: {$totalfailed}");
}

if ($dryrun) {
    cli_writeln('');
    cli_writeln('This was a dry run. To perform the actual deletion, run the script without --dry-run');
} else {
    cli_writeln('');
    cli_writeln('Grade items deletion completed!');
}

exit(0);
