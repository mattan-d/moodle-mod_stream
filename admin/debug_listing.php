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
 * Debug page for Stream video listing (admin only).
 *
 * @package    mod_stream
 * @copyright  2026 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/stream/locallib.php');
require_once($CFG->dirroot . '/mod/stream/classes/external/listing.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('modstreamdebuglisting');

$email = optional_param('email', $USER->email, PARAM_RAW);
$term = optional_param('term', '', PARAM_TEXT);
$sort = optional_param('sort', 'timecreated', PARAM_ALPHANUMEXT);
$videoid = optional_param('videoid', '', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/mod/stream/admin/debug_listing.php', [
        'email' => $email,
        'term' => $term,
        'sort' => $sort,
        'videoid' => $videoid,
]));
$PAGE->set_title(get_string('debuglisting', 'mod_stream'));
$PAGE->set_heading(get_string('debuglisting', 'mod_stream'));

$config = get_config('stream');
$accountid = (string) ($config->accountid ?? '');
$accountmasked = $accountid === '' ? '(empty)' : (substr($accountid, 0, 4) . '…' . substr($accountid, -4)
        . ' (len=' . strlen($accountid) . ')');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('debuglisting', 'mod_stream'));
echo html_writer::div(get_string('debuglisting_desc', 'mod_stream'), 'mb-3');

// Filter form.
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring(), 'class' => 'mb-4']);
echo html_writer::start_div('form-inline');
echo html_writer::label('Email', 'email', false, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
        'type' => 'email',
        'name' => 'email',
        'id' => 'email',
        'value' => s($email),
        'class' => 'form-control mr-3',
        'size' => 40,
]);
echo html_writer::label('Term', 'term', false, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'term',
        'id' => 'term',
        'value' => s($term),
        'class' => 'form-control mr-3',
]);
echo html_writer::label('Sort', 'sort', false, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'sort',
        'id' => 'sort',
        'value' => s($sort),
        'class' => 'form-control mr-3',
]);
echo html_writer::label('Video ID (optional)', 'videoid', false, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'videoid',
        'id' => 'videoid',
        'value' => s($videoid),
        'class' => 'form-control mr-3',
]);
echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('run', 'mod_stream'),
        'class' => 'btn btn-primary',
]);
echo html_writer::end_div();
echo html_writer::end_tag('form');

// Section helpers.
$section = static function(string $title, string $bodyhtml, string $type = 'info') use ($OUTPUT): void {
    echo $OUTPUT->heading($title, 3);
    echo $OUTPUT->box($bodyhtml, 'generalbox alert alert-' . $type);
};

$pre = static function($value): string {
    if (is_string($value) && strlen($value) > 8000) {
        $value = substr($value, 0, 8000) . "\n… [truncated, total " . strlen($value) . " bytes]";
    }
    return html_writer::tag('pre', s(is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            ['style' => 'white-space:pre-wrap;max-height:420px;overflow:auto;background:#111;color:#eee;padding:12px;border-radius:6px;']);
};

// 1) Config.
$configrows = [
        'apiendpoint' => $config->apiendpoint ?? '(missing)',
        'accountid' => $accountmasked,
        'current_moodle_user' => fullname($USER) . ' <' . $USER->email . '>',
        'request_email' => $email,
        'plugin_version' => get_config('mod_stream', 'version'),
];
$section('1) Moodle Stream config', $pre($configrows), empty($config->apiendpoint) ? 'danger' : 'secondary');

// 2) Raw API call.
$post = [
        'term' => $term,
        'email' => $email,
        'sort' => $sort,
];
if ($videoid !== '') {
    $post = [
            'ids' => json_encode([$videoid]),
            'email' => $email,
    ];
}

$raw = \mod_stream\stream_video::call($post, true);
$debug = \mod_stream\stream_video::$lastdebug ?? [];

$rawok = is_string($raw) && $raw !== '';
$section(
        '2) Raw Stream API call',
        $pre([
                'url' => $debug['url'] ?? '',
                'post' => $debug['post'] ?? $post,
                'ok' => $debug['ok'] ?? false,
                'raw_type' => $debug['raw_type'] ?? gettype($raw),
                'raw_length' => $debug['raw_length'] ?? (is_string($raw) ? strlen($raw) : 0),
                'http_code' => $debug['http_code'] ?? null,
                'content_type' => $debug['content_type'] ?? null,
                'total_time' => $debug['total_time'] ?? null,
                'curl_errno' => $debug['curl_errno'] ?? null,
                'curl_error' => $debug['curl_error'] ?? null,
                'json_error_plain' => $debug['json_error_plain'] ?? null,
                'json_error_substitute' => $debug['json_error_substitute'] ?? null,
        ]),
        $rawok ? 'success' : 'danger'
);

echo $OUTPUT->heading('2b) Raw response body', 4);
echo $pre(is_string($raw) ? $raw : var_export($raw, true));

// 3) Decode steps.
$flags = defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
$decodedplain = is_string($raw) ? json_decode($raw, true) : null;
$errplain = json_last_error_msg();
$decodedsub = is_string($raw) ? json_decode((string) $raw, true, 512, $flags) : null;
$errsub = json_last_error_msg();

$section(
        '3) JSON decode',
        $pre([
                'plain_is_array' => is_array($decodedplain),
                'plain_error' => $errplain,
                'plain_status' => is_array($decodedplain) ? ($decodedplain['status'] ?? null) : null,
                'plain_video_count' => (is_array($decodedplain) && isset($decodedplain['videos']) && is_array($decodedplain['videos']))
                        ? count($decodedplain['videos']) : null,
                'substitute_is_array' => is_array($decodedsub),
                'substitute_error' => $errsub,
                'substitute_status' => is_array($decodedsub) ? ($decodedsub['status'] ?? null) : null,
                'substitute_video_count' => (is_array($decodedsub) && isset($decodedsub['videos']) && is_array($decodedsub['videos']))
                        ? count($decodedsub['videos']) : null,
        ]),
        is_array($decodedsub) ? 'success' : 'danger'
);

// 4) listing() helper (same path as Moodle picker).
$previoususeremail = $USER->email;
$USER->email = $email;
$listing = \mod_stream\stream_video::listing($term, $sort);
$USER->email = $previoususeremail;

$section(
        '4) stream_video::listing() result (same as picker)',
        $pre([
                'status' => $listing['status'] ?? null,
                'video_count' => isset($listing['videos']) && is_array($listing['videos']) ? count($listing['videos']) : null,
                'first_3' => array_slice($listing['videos'] ?? [], 0, 3),
        ]),
        (!empty($listing['videos']) ? 'success' : 'warning')
);

// 5) External API clean_returnvalue simulation.
$cleanok = false;
$cleanerror = '';
$cleancount = 0;
try {
    $clean = \mod_stream\external\listing::clean_returnvalue(
            \mod_stream\external\listing::execute_returns(),
            $listing
    );
    $cleanok = true;
    $cleancount = count($clean['videos'] ?? []);
} catch (Throwable $e) {
    $cleanerror = $e->getMessage();
    if (property_exists($e, 'debuginfo') && !empty($e->debuginfo)) {
        $cleanerror .= "\n" . $e->debuginfo;
    }
}

$section(
        '5) Moodle external clean_returnvalue (mod_stream_video_list)',
        $pre([
                'ok' => $cleanok,
                'video_count' => $cleancount,
                'error' => $cleanerror,
        ]),
        $cleanok ? 'success' : 'danger'
);

// 6) Optional single-id lookup.
if ($videoid !== '') {
    $byid = \mod_stream\stream_video::get_videos_by_id([$videoid]);
    $section(
            '6) get_videos_by_id(' . s($videoid) . ')',
            $pre($byid),
            !empty($byid) ? 'success' : 'warning'
    );
}

// 7) HTML table of videos from listing.
echo $OUTPUT->heading('7) Videos table', 3);
$videos = $listing['videos'] ?? [];
if (empty($videos)) {
    echo $OUTPUT->notification(get_string('noresults', 'mod_stream'), 'notifyproblem');
} else {
    $table = new html_table();
    $table->head = ['#', 'ID', 'Title', 'Duration', 'Thumbnail'];
    $table->data = [];
    $i = 0;
    foreach ($videos as $video) {
        $i++;
        $id = s($video['id'] ?? '');
        $title = s($video['title'] ?? '');
        $duration = s($video['duration'] ?? '');
        $thumb = (string) ($video['thumbnail'] ?? '');
        $thumbhtml = $thumb !== ''
                ? html_writer::empty_tag('img', [
                        'src' => $thumb,
                        'alt' => '',
                        'style' => 'width:120px;height:68px;object-fit:cover;background:#222;',
                ]) . html_writer::div(s(strlen($thumb) > 80 ? substr($thumb, 0, 80) . '…' : $thumb), 'small text-muted')
                : html_writer::span('(empty)', 'badge badge-warning');
        $table->data[] = [$i, $id, $title, $duration, $thumbhtml];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
