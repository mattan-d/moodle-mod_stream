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
 * 4. Recalculate gradebooks for affected courses
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');

use mod_stream\local\grade_items_cleaner;

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

cli_writeln('Finding Stream activities with grade items...');
$summary = grade_items_cleaner::process($courseid, $dryrun);

if ($summary['processed'] === 0) {
    cli_writeln('No Stream activities with grade items found.');
    exit(0);
}

cli_writeln('Found ' . $summary['processed'] . ' Stream activities with grade items.');
cli_writeln('');

foreach ($summary['results'] as $entry) {
    $stream = $entry['stream'];
    cli_writeln("Processing: {$stream->name} (ID: {$stream->id})");
    cli_writeln("  Course: {$stream->coursename} ({$stream->coursefullname})");
    cli_writeln("  Current grade setting: {$stream->grade}");

    if (!empty($entry['error'])) {
        cli_writeln("  ERROR: " . $entry['error']);
    } else if ($entry['skipped']) {
        cli_writeln("  Skipped - no grade item found");
        if ($verbose) {
            cli_writeln("    No grade item found for this activity.");
        }
    } else if ($dryrun) {
        cli_writeln("  [DRY RUN] Would delete grade item");
        if ($verbose) {
            cli_writeln("    Would delete grade item ID: {$entry['gradeitemid']}");
            cli_writeln("    Would reset stream grade field to 0");
        }
    } else {
        cli_writeln("  Successfully deleted grade item");
        if ($verbose) {
            cli_writeln("    Deleted grade item ID: {$entry['gradeitemid']}");
            cli_writeln("    Reset stream grade field to 0");
        }
    }

    cli_writeln('');
}

// Summary.
cli_writeln('=== Summary ===');
cli_writeln("Activities processed: {$summary['processed']}");
cli_writeln("Grade items deleted: {$summary['deleted']}");
if ($summary['failed'] > 0) {
    cli_writeln("Failed: {$summary['failed']}");
}

if ($dryrun) {
    cli_writeln('');
    cli_writeln('This was a dry run. To perform the actual deletion, run the script without --dry-run');
} else {
    if ($summary['deleted'] > 0) {
        cli_writeln('');
        cli_writeln('=== Gradebook recalculation ===');
        cli_writeln("Courses regraded successfully: {$summary['regrade_success']}");
        if ($summary['regrade_failed'] > 0) {
            cli_writeln("Courses failed to regrade: {$summary['regrade_failed']}");
            foreach ($summary['regraded'] as $regrade) {
                if (!$regrade['success']) {
                    cli_writeln("  Course {$regrade['courseid']}: {$regrade['error']}");
                } else if ($verbose) {
                    cli_writeln("  Course {$regrade['courseid']}: regraded");
                }
            }
        } else if ($verbose) {
            foreach ($summary['regraded'] as $regrade) {
                cli_writeln("  Course {$regrade['courseid']}: regraded");
            }
        }
    }

    cli_writeln('');
    cli_writeln('Grade items deletion completed!');
}

exit(0);
