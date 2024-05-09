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
        global $PAGE, $OUTPUT;

        $mform = $this->_form;
        $PAGE->requires->jquery();
        $PAGE->requires->js_call_amd('mod_stream/main', 'init');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('nametitle', 'stream'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'identifier', get_string('identifier', 'stream'), ['size' => '64', 'readonly' => true]);
        $mform->setType('identifier', PARAM_TEXT);
        $mform->addHelpButton('identifier', 'identifier', 'stream');
        $mform->addRule('identifier', null, 'required', null, 'client');
        $mform->addRule('identifier', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'topic', get_string('topic', 'stream'), ['size' => '255', 'readonly' => true]);
        $mform->setType('topic', PARAM_TEXT);
        $mform->addHelpButton('topic', 'topic', 'stream');

        $this->standard_intro_elements();
        $mform->addElement('html', $OUTPUT->render_from_template('mod_stream/search', []));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
