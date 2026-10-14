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
 * Report generation and strict approval gate.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;

defined('MOODLE_INTERNAL') || die();

final class validator {
    /** @return array Non-sensitive, JSON-serialisable manifesto. */
    public static function verify(int $operationid): array {
        global $DB;
        $operation = $DB->get_record('local_sanitizedclone_operation', ['id' => $operationid], '*', MUST_EXIST);
        $inventory = inventory::collect();
        $checkpoints = $DB->get_records('local_sanitizedclone_checkpoint', ['operationid' => $operationid], 'step ASC');
        $stages = [];
        $incomplete = 0;
        foreach ($checkpoints as $checkpoint) {
            $stages[] = ['stage' => $checkpoint->step, 'state' => $checkpoint->state,
                'processed' => (int) $checkpoint->processed];
            if ($checkpoint->state !== 'done') {
                $incomplete++;
            }
        }
        // Explicitly include every declared sanitizer stage, not just checkpoints that exist.
        foreach (registry::all() as $sanitizer) {
            $found = false;
            foreach ($stages as $stage) {
                if (str_starts_with($stage['stage'], $sanitizer->name() . ':')) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $incomplete++;
            }
        }
        $integrity = integrity::check();
        $sanitized = 0;
        $deleted = 0;
        foreach ($stages as $stage) {
            if (preg_match('/^(logs|credentials|files):/', $stage['stage'])) {
                $deleted += $stage['processed'];
            } else {
                $sanitized += $stage['processed'];
            }
        }
        return [
            'schema' => 1,
            'operation_id' => (int) $operation->id,
            'destination_id' => $operation->destinationid,
            'finished_at' => time(),
            'stages' => $stages,
            'sanitized' => $sanitized,
            'deleted' => $deleted,
            'preserved' => 'relational_identifiers_and_academic_structures',
            'not_analysed' => $inventory['unknown'],
            'files' => $inventory['files'],
            'integrity' => $integrity,
            'blocking_sources' => count($inventory['unknown']) + $incomplete + $integrity['violations'],
            'approved' => !$inventory['unknown'] && $incomplete === 0 && $integrity['violations'] === 0,
        ];
    }
}
