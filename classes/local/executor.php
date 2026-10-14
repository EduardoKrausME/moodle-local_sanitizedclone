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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Locked CLI-only executor with fail-closed completion status.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;


/**
 * executor implementation.
 */
final class executor {
    /**
     * Executes authorised sanitization.
     *
     * @return array Technical manifest without record data.
     */
    public static function run(int $operationid, string $attestationfile, bool $confirmed): array {
        global $DB;
        $operation = $DB->get_record('local_sanitizedclone_operation', ['id' => $operationid], '*', MUST_EXIST);
        // No destructive call before environment proof has been checked.
        $key = environment_guard::assert_safe($attestationfile, $operation->destinationid, $confirmed);
        if (!in_array($operation->state, ['pending', 'failed', 'blocked'], true)) {
            throw new \moodle_exception('invalidstate', 'local_sanitizedclone');
        }
        if ($operation->state === 'pending' && $operation->timeexpires < time()) {
            throw new \moodle_exception('expiredplan', 'local_sanitizedclone');
        }
        $factory = \core\lock\lock_config::get_lock_factory('local_sanitizedclone');
        $lock = $factory->get_lock('sanitization', 5);
        if (!$lock) {
            throw new \moodle_exception('alreadyrunning', 'local_sanitizedclone');
        }
        try {
            // Re-fetch inside lock, avoid two CLI processes racing against a stale state.
            $operation = $DB->get_record('local_sanitizedclone_operation', ['id' => $operationid], '*', MUST_EXIST);
            if (!in_array($operation->state, ['pending', 'failed', 'blocked'], true)) {
                throw new \moodle_exception('invalidstate', 'local_sanitizedclone');
            }
            $settings = policy::normalize(json_decode($operation->configjson, true, 512, JSON_THROW_ON_ERROR));
            policy::enforce($settings);
            if ($operation->state === 'pending' &&
                    !hash_equals($operation->planhash, planner::hash(planner::create($settings)))) {
                throw new \moodle_exception('planchanged', 'local_sanitizedclone');
            }
            $operation->state = 'running';
            if (!$operation->timestarted) {
                $operation->timestarted = time();
            }
            $DB->update_record('local_sanitizedclone_operation', $operation);
            audit::record($operationid, 'started');
            \local_sanitizedclone\event\operation_started::create([
                'context' => \context_system::instance(), 'objectid' => $operationid,
            ])->trigger();
            try {
                $mapper = new mapper($key);
                foreach (registry::all() as $sanitizer) {
                    if (array_key_exists($sanitizer->name(), $settings) && !$settings[$sanitizer->name()]) {
                        audit::record($operationid, 'skipped', $sanitizer->name());
                        continue;
                    }
                    $sanitizer->execute($operationid, $mapper);
                    audit::record($operationid, 'stage_finished', $sanitizer->name());
                }
                $report = validator::verify($operationid);
                $operation->state = $report['approved'] ? 'approved' : 'blocked';
                $operation->reportjson = json_encode($report, JSON_THROW_ON_ERROR);
                $operation->timefinished = time();
                $DB->update_record('local_sanitizedclone_operation', $operation);
                audit::record($operationid, $operation->state);
                \local_sanitizedclone\event\operation_finished::create([
                    'context' => \context_system::instance(), 'objectid' => $operationid,
                ])->trigger();
                return $report;
            } catch (\Throwable $exception) {
                // Exception messages may include row data, paths, or secrets: never persist them.
                $operation->state = 'failed';
                $operation->reportjson = json_encode(['error_class' => get_class($exception)], JSON_THROW_ON_ERROR);
                $DB->update_record('local_sanitizedclone_operation', $operation);
                audit::record($operationid, 'failed');
                throw $exception;
            }
        } finally {
            $lock->release();
        }
    }
}
