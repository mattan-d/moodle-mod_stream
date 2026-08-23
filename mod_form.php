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
 * Stream configuration form.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/stream/locallib.php');

/**
 * Mod Form.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_stream_mod_form extends moodleform_mod {

    /**
     * Definition.
     *
     * @throws HTML_QuickForm_Error
     * @throws coding_exception
     */
    public function definition() {
        global $PAGE, $OUTPUT, $USER, $DB;

        $mform = $this->_form;
        $PAGE->requires->jquery();

        $jsinitargs = [];

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('nametitle', 'stream'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Video Recordings Collection Mode
        $mform->addElement('advcheckbox', 'collection_mode', get_string('collectionmode', 'stream'),
                get_string('collectionmode_desc', 'stream'));
        $mform->addHelpButton('collection_mode', 'collectionmode', 'stream');
        $mform->setDefault('collection_mode', 0); // Changed from 1 to 0

        $includeaudio_default = 0;

        // Check if we can determine the course
        if (isset($this->current->course) && $this->current->course > 0) {
            $course = $DB->get_record('course', ['id' => $this->current->course], 'idnumber');
            if ($course && !empty($course->idnumber)) {
                // Get the configured course types from admin settings
                $audiocoursetypes = get_config('stream', 'audiocoursetypes');

                if (!empty($audiocoursetypes)) {
                    // Split by newlines and trim each string
                    $coursetypes = array_filter(array_map('trim', explode("\n", $audiocoursetypes)));

                    // Check if course idnumber contains any of the configured strings
                    foreach ($coursetypes as $type) {
                        if (!empty($type) && strpos($course->idnumber, $type) !== false) {
                            $includeaudio_default = 1;
                            break;
                        }
                    }
                }
            }
        }

        // If no course-specific match, fall back to global default
        if ($includeaudio_default === 0) {
            $defaultaudio = get_config('stream', 'defaultincludeaudio');
            $includeaudio_default = $defaultaudio !== false ? $defaultaudio : 0;
        }

        $mform->addElement('advcheckbox', 'includeaudio', get_string('includeaudio', 'stream'),
                get_string('includeaudio_desc', 'stream'));
        $mform->addHelpButton('includeaudio', 'includeaudio', 'stream');
        $mform->setDefault('includeaudio', $includeaudio_default);

        $defaultautoplaynext = get_config('stream', 'defaultautoplaynext');
        $autoplaynext_default = ($defaultautoplaynext !== false) ? (int) $defaultautoplaynext : 1;
        $mform->addElement('advcheckbox', 'autoplaynext', get_string('autoplaynext', 'stream'),
                get_string('autoplaynext_desc', 'stream'));
        $mform->addHelpButton('autoplaynext', 'autoplaynext', 'stream');
        $mform->setDefault('autoplaynext', $autoplaynext_default);

        $mform->addElement('hidden', 'identifier');
        $mform->setType('identifier', PARAM_TEXT);
        $mform->addRule('identifier', null, 'required', null, 'client');

        // Add the video_order hidden field to the form
        $mform->addElement('hidden', 'video_order');
        $mform->setType('video_order', PARAM_TEXT);

        $mform->addElement('hidden', 'video_names');
        $mform->setType('video_names', PARAM_TEXT);

        // Set default values for existing instances
        if (!empty($this->current->instance)) {
            $stream = $DB->get_record('stream', ['id' => $this->current->instance]);
            if ($stream) {
                $stream->video_order = stream_sync_video_order($stream->identifier ?? '', $stream->video_order ?? '[]');

                // Set default for identifier
                $mform->setDefault('identifier', $stream->identifier);

                // Set default for collection_mode
                $mform->setDefault('collection_mode', $stream->collection_mode ?? 0); // Keep this as is - it uses the stored value or 0 if not set

                // Set default for includeaudio
                $mform->setDefault('includeaudio', $stream->includeaudio ?? 0);

                // Set default for autoplaynext
                $mform->setDefault('autoplaynext', $stream->autoplaynext ?? 1);

                // Set default for video_order
                $mform->setDefault('video_order', $stream->video_order);

                if (!empty($stream->video_names)) {
                    $mform->setDefault('video_names', $stream->video_names);
                } else {
                    $mform->setDefault('video_names', '{}');
                }

                $identifiers = stream_parse_identifiers($stream->identifier ?? '');
                if (!empty($identifiers)) {
                    $selectedvideos = \mod_stream\stream_video::get_videos_by_id($identifiers);
                    $initialvideos = [];
                    foreach ($selectedvideos as $video) {
                        $initialvideos[] = [
                            'id' => (string) $video->id,
                            'title' => (string) ($video->title ?? ''),
                            'thumbnail' => (string) ($video->thumbnail ?? ''),
                            'duration' => (string) ($video->duration ?? ''),
                        ];
                    }
                    if (!empty($initialvideos)) {
                        $jsinitargs['initialvideos'] = $initialvideos;
                    }
                }
            }
        } else {
            // For new instances, set empty defaults
            $mform->setDefault('video_order', '[]');
            $mform->setDefault('video_names', '{}');
            $mform->setDefault('includeaudio', 0);
            $mform->setDefault('autoplaynext', $autoplaynext_default);
        }

        $this->standard_intro_elements();

        // Only show video search if collection mode is disabled
        $mform->addElement('html', '<div id="video-search-container">');
        $mform->addElement('html', $OUTPUT->render_from_template('mod_stream/search', [
                'endpoint' => get_config('stream', 'apiendpoint'),
                'token' => get_config('stream', 'accountid'),
                'email' => $USER->email,
        ]));
        $mform->addElement('html', '</div>');

        // Add JavaScript to handle collection mode behavior
        $PAGE->requires->js_init_code('
    function handleCollectionMode() {
        var collectionMode = document.getElementById("id_collection_mode");
        var identifierField = document.querySelector("input[name=\'identifier\']");
        
        if (collectionMode && identifierField) {
            if (collectionMode.checked) {
                // In collection mode, set a special identifier but keep video search visible
                if (identifierField.value === "" || identifierField.value === "auto_collection") {
                    identifierField.value = "auto_collection";
                }
            } else {
                // Not in collection mode, clear auto_collection if it was set
                if (identifierField.value === "auto_collection") {
                    identifierField.value = "";
                }
            }
        }
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        var collectionMode = document.getElementById("id_collection_mode");
        if (collectionMode) {
            collectionMode.addEventListener("change", handleCollectionMode);
            handleCollectionMode(); // Initial call
        }
    });
');

        $mform->addElement('header', 'timing', get_string('timing', 'stream'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'stream'), ['optional' => true]);
        $mform->addHelpButton('timeopen', 'timeopen', 'stream');

        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'stream'), ['optional' => true]);
        $mform->addHelpButton('timeclose', 'timeclose', 'stream');

        $this->standard_grading_coursemodule_elements();

        // New activity: default grade type to "None" (core sets points from $CFG->gradepointdefault otherwise).
        if (empty($this->_cm)) {
            $gradefieldname = \core_grades\component_gradeitems::get_field_name_for_itemnumber('mod_stream', 0, 'grade');
            if ($mform->elementExists($gradefieldname)) {
                $gradeelement = $mform->getElement($gradefieldname);
                $mform->setDefault($gradeelement->getName() . '[modgrade_type]', 'none');
            }
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

        $PAGE->requires->js_call_amd('mod_stream/main', 'init', [$jsinitargs]);
    }

    /**
     * Perform minimal validation on the settings form
     *
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Collection mode allows either auto_collection or manual selection
        if (empty($data['collection_mode'])) {
            // Not in collection mode - require manual video selection
            if (empty(trim($data['identifier'])) || trim($data['identifier']) === 'auto_collection') {
                $errors['identifier'] = get_string('required');
            }
        }
        // If collection mode is enabled, we accept either auto_collection or manual selection

        // Validate video_order is valid JSON if provided
        if (!empty($data['video_order'])) {
            $decoded = json_decode($data['video_order'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Reset to empty array if invalid JSON
                $data['video_order'] = '[]';
            }
        }

        if (!empty($data['video_names'])) {
            $decoded = json_decode($data['video_names'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Reset to empty object if invalid JSON
                $data['video_names'] = '{}';
            }
        }

        return $errors;
    }

    /**
     * Set default values for the form.
     * This is called before the form is displayed.
     *
     * @param array $default_values
     */
    public function set_data($default_values) {
        // For new instances, set grade to 0 (no grade item) by default.
        if (empty($default_values->instance)) {
            if (!isset($default_values->grade)) {
                $default_values->grade = 0;
            }
        }
        parent::set_data($default_values);
    }

    /**
     * Add elements to form
     */
    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        if (!empty($default_values['identifier'])) {
            $default_values['video_order'] = stream_sync_video_order(
                $default_values['identifier'],
                $default_values['video_order'] ?? '[]'
            );
        }

        // Ensure video_order is properly set during data preprocessing
        if (isset($default_values['video_order']) && !empty($default_values['video_order'])) {
            // Make sure it's valid JSON
            $decoded = json_decode($default_values['video_order'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $default_values['video_order'] = '[]';
            }
        } else {
            $default_values['video_order'] = '[]';
        }

        if (isset($default_values['video_names']) && !empty($default_values['video_names'])) {
            // Make sure it's valid JSON
            $decoded = json_decode($default_values['video_names'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $default_values['video_names'] = '{}';
            }
        } else {
            $default_values['video_names'] = '{}';
        }

        // Ensure includeaudio is properly set during data preprocessing
        if (isset($default_values['includeaudio'])) {
            $default_values['includeaudio'] = (int)$default_values['includeaudio'];
        } else {
            $default_values['includeaudio'] = 0;
        }

        if (isset($default_values['autoplaynext'])) {
            $default_values['autoplaynext'] = (int) $default_values['autoplaynext'];
        } else {
            $default_values['autoplaynext'] = 1;
        }
    }
}
