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
 * SSO into Stream and open the personal recording page.
 *
 * @package    mod_stream
 * @copyright  2026 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();

$endpoint = rtrim((string) get_config('stream', 'apiendpoint'), '/');
$accountid = (string) get_config('stream', 'accountid');

if ($endpoint === '' || $accountid === '') {
    throw new \moodle_exception('connectionfailed', 'mod_stream');
}

global $USER, $DB;

$user = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);
$payload = [
        'email' => (string) $user->email,
        'firstname' => (string) $user->firstname,
        'lastname' => (string) $user->lastname,
        'username' => (string) $user->username,
];

$curl = new \curl();
$response = $curl->post(
        $endpoint . '/webservice/api/v4',
        $payload,
        [
                'CURLOPT_RETURNTRANSFER' => true,
                'CURLOPT_CONNECTTIMEOUT' => 10,
                'CURLOPT_TIMEOUT' => 30,
                'CURLOPT_HTTPHEADER' => [
                        'Authorization: Bearer ' . $accountid,
                        'Accept: application/json',
                ],
        ]
);

$data = json_decode((string) $response);
$info = method_exists($curl, 'get_info') ? $curl->get_info() : [];
$httpcode = (int) ($info['http_code'] ?? 0);

if (!is_object($data) || empty($data->sesskey)) {
    debugging(
            'mod_stream gotorecord SSO failed: http=' . $httpcode .
            ' raw=' . substr((string) $response, 0, 300),
            DEBUG_DEVELOPER
    );
    throw new \moodle_exception('connectionfailed', 'mod_stream');
}

$recordurl = $endpoint . '/videos/record';
redirect(new moodle_url($endpoint . '/', [
        'sesskey' => (string) $data->sesskey,
        'redirect' => $recordurl,
]));
