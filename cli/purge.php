<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * CLI cleanup for expired, finished operation checkpoints/audit entries.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unknown] = cli_get_params([
    'operation' => 0, 'attestation' => '', 'confirm-isolated' => false,
    'confirm-purge' => false,
], []);
if ($unknown || !$options['operation'] || !$options['attestation'] ||
        !$options['confirm-isolated'] || !$options['confirm-purge']) {
    cli_error(get_string('purgeusage', 'local_sanitizedclone'));
}
try {
    $id = (int) $options['operation'];
    $operation = $DB->get_record('local_sc_operation', ['id' => $id], '*', MUST_EXIST);
    \local_sanitizedclone\local\environment_guard::assert_safe(
        $options['attestation'], $operation->destinationid, true);
    if (!in_array($operation->state, ['approved', 'blocked', 'failed'], true) ||
            (int) $operation->timecreated > time() - 30 * DAYSECS) {
        throw new \moodle_exception('purgenotallowed', 'local_sanitizedclone');
    }
    $factory = \core\lock\lock_config::get_lock_factory('local_sanitizedclone');
    $lock = $factory->get_lock('sanitization', 5);
    if (!$lock) {
        throw new \moodle_exception('alreadyrunning', 'local_sanitizedclone');
    }
    try {
        $tx = $DB->start_delegated_transaction();
        try {
        $DB->delete_records('local_sc_checkpoint', ['operationid' => $id]);
        $DB->delete_records('local_sc_audit', ['operationid' => $id]);
        $tx->allow_commit();
        } catch (\Throwable $e) {
            $tx->rollback($e);
        }
        cli_writeln(get_string('purged', 'local_sanitizedclone', $id));
    } finally {
        $lock->release();
    }
} catch (\Throwable $exception) {
    cli_error(get_string('clifailed', 'local_sanitizedclone', get_class($exception)), 1);
}
