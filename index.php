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
 * Read-only previews, profiles and administrative CLI authorisations.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/sanitizedclone:view', $context);

$PAGE->set_url(new moodle_url('/local/sanitizedclone/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_sanitizedclone'));
$PAGE->set_heading(get_string('pluginname', 'local_sanitizedclone'));
$PAGE->requires->js_call_amd('local_sanitizedclone/dashboard', 'init');

$action = optional_param('action', '', PARAM_ALPHA);
$selected = optional_param('profileid', 0, PARAM_INT);
$canmanage = has_capability('local/sanitizedclone:manageprofiles', $context);
$canauthorize = has_capability('local/sanitizedclone:authorize', $context);

if ($action !== '') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new moodle_exception('invalidaction');
    }
    require_sesskey();
    if ($action === 'create') {
        require_capability('local/sanitizedclone:manageprofiles', $context);
        $name = trim(required_param('name', PARAM_TEXT));
        if ($name === '' || core_text::strlen($name) > 120) {
            throw new moodle_exception('invalidprofilename', 'local_sanitizedclone');
        }
        // Explicit profile toggles are immutable after an operation is approved.
        $settings = [];
        foreach (\local_sanitizedclone\local\policy::STAGES as $stage) {
            $settings[$stage] = (bool) optional_param('stage_' . $stage, 0, PARAM_BOOL);
        }
        $selected = $DB->insert_record('local_sanitizedclone_profile', (object) [
            'name' => $name,
            'configjson' => json_encode($settings, JSON_THROW_ON_ERROR),
            'timecreated' => time(),
        ]);
    } else if ($action === 'authorize') {
        require_capability('local/sanitizedclone:authorize', $context);
        if (!required_param('confirm', PARAM_BOOL)) {
            throw new moodle_exception('confirmationrequired', 'local_sanitizedclone');
        }
        $destinationid = required_param('destinationid', PARAM_ALPHANUMEXT);
        if (!preg_match('/^[a-zA-Z0-9_-]{12,64}$/D', $destinationid)) {
            throw new moodle_exception('invaliddestination', 'local_sanitizedclone');
        }
        \local_sanitizedclone\local\planner::authorize($selected, $destinationid);
    } else {
        throw new moodle_exception('invalidaction');
    }
    redirect(new moodle_url('/local/sanitizedclone/index.php', ['profileid' => $selected]));
}

$profiles = $DB->get_records('local_sanitizedclone_profile', null, 'name ASC');
$stageoptions = [];
foreach (\local_sanitizedclone\local\policy::STAGES as $stage) {
    $stageoptions[] = ['field' => 'stage_' . $stage,
        'label' => get_string('stage' . $stage, 'local_sanitizedclone')];
}
$profilelist = [];
foreach ($profiles as $profile) {
    $profilelist[] = ['id' => $profile->id, 'name' => format_string($profile->name),
        'previewurl' => (new moodle_url('/local/sanitizedclone/index.php',
            ['profileid' => $profile->id]))->out(false)];
}
$operations = [];
foreach ($DB->get_records('local_sanitizedclone_operation', null, 'id DESC', '*', 0, 50) as $operation) {
    $checkpoints = $DB->get_records('local_sanitizedclone_checkpoint', ['operationid' => $operation->id], 'step ASC');
    $operations[] = [
        'id' => $operation->id, 'state' => $operation->state,
        'destinationid' => s($operation->destinationid),
        'timecreated' => userdate($operation->timecreated),
        'progress' => count(array_filter($checkpoints, static fn($c) => $c->state === 'done')) .
            '/' . count($checkpoints),
        'command' => 'php local/sanitizedclone/cli/run.php --operation=' . $operation->id .
            ' --attestation=/secure/destination.json --confirm-isolated',
    ];
}
$preview = null;
if ($selected) {
    $profile = $DB->get_record('local_sanitizedclone_profile', ['id' => $selected], '*', MUST_EXIST);
    $settings = \local_sanitizedclone\local\policy::normalize(
        json_decode($profile->configjson, true, 512, JSON_THROW_ON_ERROR));
    $plan = \local_sanitizedclone\local\planner::create($settings);
    $known = [];
    foreach ($plan['inventory']['known'] as $item) {
        $known[] = ['source' => $item['table'], 'action' => $item['stage'], 'count' => $item['rows']];
    }
    $unknown = [];
    foreach ($plan['inventory']['unknown'] as $item) {
        $unknown[] = ['source' => $item['source'], 'reason' => $item['reason'],
            'count' => $item['rows']];
    }
    $preview = [
        'id' => $selected,
        'name' => format_string($profile->name),
        'known' => $known,
        'unknown' => $unknown,
        'blockers' => $plan['inventory']['blockers'],
        'hash' => \local_sanitizedclone\local\planner::hash($plan),
    ];
}

$data = [
    'canmanage' => $canmanage,
    'stageoptions' => $stageoptions,
    'canauthorize' => $canauthorize,
    'actionurl' => (new moodle_url('/local/sanitizedclone/index.php'))->out(false),
    'sesskey' => sesskey(),
    'profiles' => $profilelist,
    'operations' => $operations,
    'preview' => $preview,
    'hasoperations' => !empty($operations),
    'hasprofiles' => !empty($profilelist),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_sanitizedclone/dashboard', $data);
echo $OUTPUT->footer();
