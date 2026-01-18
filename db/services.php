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
 * Stream external functions and service definitions.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
        'mod_stream_video_list' => [
                'classname' => 'mod_stream\external\listing',
                'methodname' => 'execute',
                'description' => 'Retrieves the pertinent videos through the Stream system API.',
                'type' => 'read',
                'ajax' => true,
        ],
        'mod_stream_get_player' => [
                'classname' => 'mod_stream\external\player',
                'methodname' => 'get_player_html',
                'description' => 'Get the HTML for a stream video player.',
                'type' => 'read',
                'ajax' => true,
        ],
        'mod_stream_mark_as_viewed' => [
                'classname' => 'mod_stream\external\tracker',
                'methodname' => 'mark_as_viewed',
                'description' => 'Marks a video as viewed for the current user and updates the grade.',
                'type' => 'write',
                'ajax' => true,
        ],
];
