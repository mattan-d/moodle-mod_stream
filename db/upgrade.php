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
 * This file keeps track of upgrades to the Stream plugin
 *
 * @package    mod_stream
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute stream upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 */
function xmldb_stream_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024121101) {
        // Define table stream to be altered.
        $table = new xmldb_table('stream');

        // Change field identifier to text.
        $field = new xmldb_field('identifier', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'name');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        // Drop field topic.
        $field = new xmldb_field('topic');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2024121101, 'stream');
    }

    if ($oldversion < 2024121102) {
        // Define table stream to be altered.
        $table = new xmldb_table('stream');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', true, null, false, '100', 'introformat');

        // Conditionally launch add field grade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table stream_viewed_videos to be created.
        $table = new xmldb_table('stream_viewed_videos');

        // Adding fields to table stream_viewed_videos.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('streamid', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, null, null);
        $table->add_field('videoid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeviewed', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, null, null);

        // Adding keys to table stream_viewed_videos.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('streamid', XMLDB_KEY_FOREIGN, ['streamid'], 'stream', 'id');
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', 'id');

        // Adding indexes to table stream_viewed_videos.
        $table->add_index('stream_user_video', XMLDB_INDEX_UNIQUE, ['streamid', 'userid', 'videoid']);

        // Conditionally launch create table for stream_viewed_videos.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2024121102, 'stream');
    }

    if ($oldversion < 2024121103) {
        // Define table stream to be altered.
        $table = new xmldb_table('stream');
        $field = new xmldb_field('video_order', XMLDB_TYPE_TEXT, null, null, null, null, null, 'identifier');

        // Conditionally launch add field video_order.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2024121103, 'stream');
    }

    if ($oldversion < 2024121104) {
        // Define table stream to be altered.
        $table = new xmldb_table('stream');
        $field = new xmldb_field('collection_mode', XMLDB_TYPE_INTEGER, '1', true, XMLDB_NOTNULL, null, '0', 'video_order');

        // Conditionally launch add field collection_mode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index for collection_mode.
        $index = new xmldb_index('collection_mode', XMLDB_INDEX_NOTUNIQUE, ['collection_mode']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2024121104, 'stream');
    }

    if ($oldversion < 2024121105) {
        // Define table stream to be altered.
        $table = new xmldb_table('stream');
        $field = new xmldb_field('video_names', XMLDB_TYPE_TEXT, null, null, null, null, null, 'video_order');

        // Conditionally launch add field video_names.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2024121105, 'stream');
    }

    if ($oldversion < 2025120100) {
        // Define table stream to be altered.
        $table = new xmldb_table('stream');
        $field = new xmldb_field('includeaudio', XMLDB_TYPE_INTEGER, '1', true, XMLDB_NOTNULL, null, '0', 'collection_mode');

        // Conditionally launch add field includeaudio.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Stream savepoint reached.
        upgrade_mod_savepoint(true, 2025120100, 'stream');
    }

    if ($oldversion < 2025123100) {
        $table = new xmldb_table('stream');

        // Add timeopen field
        $field = new xmldb_field('timeopen', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, null, '0', 'includeaudio');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add timeclose field
        $field = new xmldb_field('timeclose', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, null, '0', 'timeopen');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025123100, 'stream');
    }

    return true;
}
