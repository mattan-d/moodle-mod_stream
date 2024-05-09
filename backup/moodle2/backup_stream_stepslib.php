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
 * Backup stream activity structure step.
 *
 * @package   mod_stream
 * @category  backup
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup stream activity structure step.
 *
 * @package   mod_stream
 * @category  backup
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_stream_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        $stream = new backup_nested_element('stream', ['id'],
                ['course', 'name', 'identifier', 'topic', 'intro', 'introformat']);
        $stream->set_source_table('stream', ['id' => backup::VAR_ACTIVITYID]);
        $stream->annotate_files('mod_stream', 'intro', null);

        return $this->prepare_activity_structure($stream);
    }
}
