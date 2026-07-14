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
 * Form to delete Stream grade items.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stream\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Delete grade items form.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_grade_items_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('deletegradeitems', 'mod_stream'));
        $mform->addElement('static', 'description', '', get_string('deletegradeitems_desc', 'mod_stream'));

        $mform->addElement('select', 'coursescope', get_string('coursescope', 'mod_stream'), [
            'all' => get_string('allcourses', 'mod_stream'),
            'selected' => get_string('selectedcourses', 'mod_stream'),
        ]);
        $mform->setDefault('coursescope', 'all');
        $mform->addHelpButton('coursescope', 'coursescope', 'mod_stream');

        $mform->addElement('course', 'courseids', get_string('courses'), [
            'multiple' => true,
            'noselectionstring' => get_string('searchcourses', 'mod_stream'),
        ]);
        $mform->hideIf('courseids', 'coursescope', 'neq', 'selected');
        $mform->addHelpButton('courseids', 'courseids', 'mod_stream');

        $mform->addElement('advcheckbox', 'dryrun', get_string('dryrun', 'mod_stream'));
        $mform->setDefault('dryrun', 1);
        $mform->addHelpButton('dryrun', 'dryrun', 'mod_stream');

        $this->add_action_buttons(true, get_string('run', 'mod_stream'));
    }

    /**
     * Validate form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (($data['coursescope'] ?? '') === 'selected') {
            $courseids = array_filter((array) ($data['courseids'] ?? []), static function($id) {
                return (int) $id > 0;
            });
            if (empty($courseids)) {
                $errors['courseids'] = get_string('coursesselectionrequired', 'mod_stream');
            }
        }

        return $errors;
    }
}
