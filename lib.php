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
 * stream plugin main library file.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the information on whether the module supports a feature
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function stream_supports($feature) {

    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the stream into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $stream Submitted data from the form in mod_form.php
 * @param mod_stream_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted stream record
 */
function stream_add_instance(stdClass $stream, mod_stream_mod_form $mform = null) {
    global $DB;

    $stream->timecreated = time();
    $stream->id = $DB->insert_record('stream', $stream);

    return $stream->id;
}

/**
 * Updates an instance of the stream in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $stream An object from the form in mod_form.php
 * @param mod_stream_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function stream_update_instance(stdClass $stream, mod_stream_mod_form $mform = null) {
    global $DB;

    $stream->timemodified = time();
    $stream->id = $stream->instance;

    $result = $DB->update_record('stream', $stream);

    return $result;
}

/**
 * Removes an instance of the stream from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function stream_delete_instance($id) {
    global $DB;

    if (!$stream = $DB->get_record('stream', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('stream', ['id' => $stream->id]);

    return true;
}
