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
 * Language strings.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allstagesrequired'] = 'All sanitization stages are mandatory.';
$string['alreadyrunning'] = 'Another sanitization is running.';
$string['analyzed'] = 'Recognized data sources (partial coverage)';
$string['authorizecli'] = 'Authorize CLI execution';
$string['blockers'] = 'Unreviewed or blocking sources';
$string['cli'] = 'CLI command';
$string['clifailed'] = 'Sanitization failed ({$a}); see non-sensitive audit records.';
$string['clihelp'] = 'Usage: php local/sanitizedclone/cli/run.php --operation=ID --attestation=/secure/destination.json --confirm-isolated [--manifest=/secure/manifest.json]';
$string['confirmationrequired'] = 'Explicit authorization is required.';
$string['confirmtext'] = 'I authorize execution only on a separately isolated copy, not on production.';
$string['count'] = 'Count';
$string['created'] = 'Created';
$string['createprofile'] = 'Create fixed-policy profile';
$string['destinationid'] = 'Destination installation identifier';
$string['eventdescriptionoperation_authorised'] = 'The clone sanitization operation {$a} was authorized.';
$string['eventdescriptionoperation_finished'] = 'The clone sanitization operation {$a} was finished.';
$string['eventdescriptionoperation_started'] = 'The clone sanitization operation {$a} was started.';
$string['eventoperation_authorised'] = 'Clone sanitization operation authorized';
$string['eventoperation_finished'] = 'Clone sanitization operation finished';
$string['eventoperation_started'] = 'Clone sanitization operation started';
$string['expiredplan'] = 'The authorization has expired. Authorize a new plan.';
$string['invalidattestation'] = 'The destination attestation is invalid.';
$string['invaliddestination'] = 'Invalid destination identifier.';
$string['invalidmanifestpath'] = 'The manifest must be a new file outside the web root and dataroot.';
$string['invalidprofilename'] = 'A valid profile name is required.';
$string['invalidstate'] = 'Operation state does not permit execution.';
$string['manifestwritefailed'] = 'Unable to write the technical manifest.';
$string['nooperations'] = 'No operations have been authorized.';
$string['noprofiles'] = 'No profiles exist.';
$string['operation'] = 'Operation';
$string['operations'] = 'Operations and execution history';
$string['planchanged'] = 'The data inventory changed since approval. Reauthorize the plan.';
$string['planfingerprint'] = 'Plan SHA-256:';
$string['pluginname'] = 'Sanitized clone';
$string['preview'] = 'Read-only inventory preview';
$string['privacy:metadata'] = 'This plugin records configuration profiles, counts and operation states, without storing personal data in its own tables. Moodle core events record the initiating administrator.';
$string['profilename'] = 'Profile name';
$string['profiles'] = 'Sanitization profiles';
$string['progress'] = 'Checkpoints done / total';
$string['purged'] = 'Temporary audit and checkpoint records for operation {$a} were removed.';
$string['purgenotallowed'] = 'Cleanup is restricted to operations older than 30 days in a terminal state.';
$string['purgeusage'] = 'Usage: php local/sanitizedclone/cli/purge.php --operation=ID --attestation=/secure/destination.json --confirm-isolated --confirm-purge';
$string['reason'] = 'Reason';
$string['safetywarning'] = 'Danger: do not run against production. An isolated destination attestation and separate secret are required. Unknown sources always block approval.';
$string['sanitizedclone%3Aauthorize'] = 'Authorize sanitization of an isolated copy';
$string['sanitizedclone%3Amanageprofiles'] = 'Manage sanitization profiles';
$string['sanitizedclone%3Aview'] = 'View sanitized clone dashboard';
$string['sanitizedclone:authorize'] = 'Authorise sanitization of an isolated clone';
$string['sanitizedclone:manageprofiles'] = 'Manage sanitization profiles';
$string['sanitizedclone:view'] = 'View the clone sanitization dashboard';
$string['source'] = 'Data source';
$string['stage'] = 'Sanitizer stage';
$string['stageassignments'] = 'Assignment text';
$string['stagecredentials'] = 'Sessions and access tokens';
$string['stagecustomfields'] = 'Custom user fields';
$string['stagefiles'] = 'Sensitive attachments';
$string['stageforums'] = 'Forum posts';
$string['stagelogs'] = 'Activity logs';
$string['stagemessages'] = 'Messages';
$string['stageoptions'] = 'Stages included in this profile (disabled stages block clone approval)';
$string['stagesecrets'] = 'Known configuration secrets';
$string['stageusers'] = 'User profiles';
$string['status'] = 'Status';
$string['unreviewed'] = 'Not analyzed (blocks approval)';
$string['unsafeenvironment'] = 'Execution refused: the target cannot be attested as isolated.';
