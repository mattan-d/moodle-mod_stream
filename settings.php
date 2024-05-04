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
 * Stream configuration settings.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/resourcelib.php');
require_once(__DIR__ . '/locallib.php');

if ($ADMIN->fulltree) {

    $check = json_decode(streamvideo::call(['connection' => true]));
    if ($check->status == 'failed') {
        $notifyclass = 'notifyproblem';
        $status = 'connectionfailed';
        $errormessage = $check->description;
    } else {
        $status = 'connectionok';
        $notifyclass = 'notifysuccess';
        $errormessage = '';
    }

    $statusmessage = $OUTPUT->notification(get_string('connectionstatus', 'mod_stream') .
            ': ' . get_string($status, 'mod_stream') . $errormessage, $notifyclass);
    $connectionstatus = new admin_setting_heading('stream/connectionstatus', $statusmessage, '');
    $settings->add($connectionstatus);

    $settings->add(new admin_setting_heading('stream/connectionsettings',
            get_string('connectionsettings', 'mod_stream'),
            get_string('connectionsettings_desc', 'mod_stream')));

    $settings->add(new admin_setting_configtext('stream/apiendpoint',
            get_string('apiendpoint', 'mod_stream'),
            get_string('apiendpoint_desc', 'mod_stream'), 'https://demo.centricstream.co.il'));

    $settings->add(new admin_setting_configpasswordunmask('stream/accountid',
            get_string('accountid', 'mod_stream'),
            get_string('accountid_desc', 'mod_stream'), ''));

    $itemseguranca = [
            'none' => get_string('safetynone', 'mod_stream'),
            'id' => get_string('safetyid', 'mod_stream'),
    ];

    $infofields = $DB->get_records('user_info_field');
    foreach ($infofields as $infofield) {
        $itemseguranca["profile_{$infofield->id}"] = $infofield->name;
    }

    $settings->add(new admin_setting_configselect('stream/apiidentifier',
            get_string('apiidentifier', 'mod_stream'),
            get_string('apiidentifier_desc', 'mod_stream'), 'id',
            $itemseguranca
    ));
}
