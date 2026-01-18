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
          return true;
      case FEATURE_BACKUP_MOODLE2:
          return true;
      default:
          return null;
  }
}

/**
* Saves a new instance of the stream into the database
*
* @param stdClass $stream Submitted data from the form in mod_form.php
* @param mod_stream_mod_form $mform The form instance itself (if needed)
* @return int The id of the newly inserted stream record
*/
function stream_add_instance(stdClass $stream, $mform = null) {
    global $DB;

    $stream->timecreated = time();
    
    // Ensure video_order is properly handled
    if (empty($stream->video_order)) {
        $stream->video_order = '[]';
    }
    
    // Ensure collection_mode is properly handled
    if (!isset($stream->collection_mode)) {
        $stream->collection_mode = 0;
    }
    
    // If collection mode is enabled but no manual selection, set to auto_collection
    if ($stream->collection_mode && (empty($stream->identifier) || trim($stream->identifier) === '')) {
        $stream->identifier = 'auto_collection';
    }
    
    $stream->id = $DB->insert_record('stream', $stream);

    stream_grade_item_update($stream);

    return $stream->id;
}

/**
* Updates an instance of the stream in the database
*
* @param stdClass $stream An object from the form in mod_form.php
* @param mod_stream_mod_form $mform The form instance itself (if needed)
* @return boolean Success/Fail
*/
function stream_update_instance(stdClass $stream, $mform = null) {
    global $DB;

    $stream->timemodified = time();
    $stream->id = $stream->instance;

    // Ensure video_order is properly handled
    if (empty($stream->video_order)) {
        $stream->video_order = '[]';
    }
    
    // Ensure collection_mode is properly handled
    if (!isset($stream->collection_mode)) {
        $stream->collection_mode = 0;
    }
    
    // If collection mode is enabled but no manual selection, set to auto_collection
    if ($stream->collection_mode && (empty($stream->identifier) || trim($stream->identifier) === '')) {
        $stream->identifier = 'auto_collection';
    }

    $result = $DB->update_record('stream', $stream);

    stream_grade_item_update($stream);

    return $result;
}

/**
* Removes an instance of the stream from the database
*
* @param int $id Id of the module instance
* @return boolean Success/Failure
*/
function stream_delete_instance($id) {
  global $DB;

  if (!$stream = $DB->get_record('stream', ['id' => $id])) {
      return false;
  }

  $DB->delete_records('stream_viewed_videos', ['streamid' => $stream->id]);
  $DB->delete_records('stream', ['id' => $stream->id]);

  stream_grade_item_update($stream, null, true);

  return true;
}

/**
* Given a course_module object, this function returns any
* "extra" information that may be needed when printing
* a daily link to the activity.
*
* @param stdClass $cm
* @return \completion_info|null
* @throws \dml_exception
*/
function stream_get_coursemodule_info($cm) {
  global $DB;

  if (!$stream = $DB->get_record('stream', array('id' => $cm->instance), 'id, name, intro, introformat')) {
      return null;
  }
  $info = new \completion_info($cm);
  return $info;
}

/**
* Update the grade for a user in a stream activity.
*
* @param object $stream The stream activity object.
* @param int $userid The ID of the user to update the grade for.
* @param bool $batch If true, we are in batch update, so we don't need to fetch the grade item.
* @return void
* @throws dml_exception
*/
function stream_update_grades($stream, $userid, $batch = false) {
  if (empty($stream->grade)) {
      return; // No grading for this activity.
  }

  $totalvideos = count(array_filter(explode(',', $stream->identifier)));
  if ($totalvideos == 0) {
      return; // Avoid division by zero.
  }

  global $DB;
  $viewedcount = $DB->count_records('stream_viewed_videos', ['streamid' => $stream->id, 'userid' => $userid]);

  $fraction = $viewedcount / $totalvideos;
  $newgrade = $fraction * $stream->grade;

  $grades = new \stdClass();
  $grades->userid = $userid;
  $grades->rawgrade = $newgrade;

  stream_grade_item_update($stream, $grades);
}

/**
* Create, update or delete grade item for given stream
*
* @param object $stream object with extra cmidnumber
* @param mixed $grades optional array/object of grade(s); 'null' means grade item is being updated
* @param bool $delete if true, the grade item will be deleted
* @return int grade_item id
*/
function stream_grade_item_update($stream, $grades = null, $delete = false) {
  global $CFG;
  require_once($CFG->libdir.'/gradelib.php');

  $item = array();
  $item['itemname'] = $stream->name;
  $item['idnumber'] = $stream->id; // Fallback for $cm->idnumber.
  if (isset($stream->cmidnumber)) {
      $item['idnumber'] = $stream->cmidnumber;
  }

  if ($delete) {
      return grade_update('mod/stream', $stream->course, 'mod', 'stream', $stream->id, 0, null, $item, ['deleted' => 1]);
  }

  if (!empty($stream->grade)) {
      $item['gradetype'] = GRADE_TYPE_VALUE;
      $item['grademax']  = $stream->grade;
      $item['grademin']  = 0;
  } else {
      $item['gradetype'] = GRADE_TYPE_NONE;
  }

  return grade_update('mod/stream', $stream->course, 'mod', 'stream', $stream->id, 0, $grades, $item);
}
