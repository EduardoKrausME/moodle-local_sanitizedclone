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
 * Transactional, keyset-based execution and checkpoint persistence.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;

/**
 * batch processor implementation.
 */
final class batch_processor {
    /** @var int Maximum records per batch. */
    private const LIMIT = 100;

    /**
     * Checks whether a table exists.
     *
     * @return bool Whether the Moodle table exists.
     */
    public static function exists(string $table): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new \xmldb_table($table));
    }

    /**
     * Returns existing table columns.
     *
     * @return string[] Columns as strings.
     */
    public static function columns(string $table): array {
        global $DB;
        if (!self::exists($table)) {
            return [];
        }
        return array_keys($DB->get_columns($table));
    }

    /**
     * Process records by immutable PK; callable returns a reduced update record or null.
     * Checkpoint and DML writes are committed in the same delegated transaction.
     * @param callable(\stdClass): (\stdClass|null) $transform
     */
    public static function walk(int $operationid, string $step, string $table, callable $transform): void {
        global $DB;
        if (!self::exists($table)) {
            audit::record($operationid, 'missing_table', $step);
            return;
        }
        $checkpoint = self::checkpoint($operationid, $step);
        if ($checkpoint->state === 'done') {
            return;
        }
        $lastid = (int) $checkpoint->lastid;
        do {
            $rows = $DB->get_records_select($table, 'id > :lastid', ['lastid' => $lastid],
                'id ASC', '*', 0, self::LIMIT);
            if (!$rows) {
                $checkpoint->state = 'done';
                $checkpoint->timemodified = time();
                $DB->update_record('local_sanitizedclone_checkpoint', $checkpoint);
                break;
            }
            $started = microtime(true);
            // A nested rollback invalidates the caller's Moodle delegated transaction.
            // The owner of an existing transaction must handle its rollback.
            $tx = $DB->is_transaction_started() ? null : $DB->start_delegated_transaction();
            try {
                $changed = 0;
                foreach ($rows as $record) {
                    $update = $transform($record);
                    if ($update !== null) {
                        if (!isset($update->id) || (int) $update->id !== (int) $record->id) {
                            throw new \coding_exception('Sanitizer must preserve primary key');
                        }
                        $DB->update_record($table, $update);
                        $changed++;
                    }
                    $lastid = (int) $record->id;
                }
                $checkpoint->lastid = $lastid;
                $checkpoint->processed += $changed;
                $checkpoint->timemodified = time();
                $DB->update_record('local_sanitizedclone_checkpoint', $checkpoint);
                audit::record($operationid, 'batch', $step, $changed, (int) ((microtime(true) - $started) * 1000));
                if ($tx !== null) {
                    $tx->allow_commit();
                }
            } catch (\Throwable $e) {
                if ($tx !== null) {
                    $tx->rollback($e);
                }
                throw $e;
            }
        } while (count($rows) === self::LIMIT);
        if ($checkpoint->state !== 'done') {
            $checkpoint->state = 'done';
            $checkpoint->timemodified = time();
            $DB->update_record('local_sanitizedclone_checkpoint', $checkpoint);
        }
    }

    /**
     * Deletes records idempotently with checkpoint tracking.
     *
     * This operation is reserved for non-academic ephemeral data only.
     *
     * @param int $operationid Sanitization operation identifier.
     * @param string $step Checkpoint stage name.
     * @param string $table Table to clear.
     */
    public static function clear(int $operationid, string $step, string $table): void {
        global $DB;
        if (!self::exists($table)) {
            audit::record($operationid, 'missing_table', $step);
            return;
        }
        $checkpoint = self::checkpoint($operationid, $step);
        if ($checkpoint->state === 'done') {
            return;
        }
        $started = microtime(true);
        // A nested rollback invalidates the caller's Moodle delegated transaction.
        // The owner of an existing transaction must handle its rollback.
        $tx = $DB->is_transaction_started() ? null : $DB->start_delegated_transaction();
        try {
            $count = $DB->count_records($table);
            $DB->delete_records($table);
            $checkpoint->processed += $count;
            $checkpoint->state = 'done';
            $checkpoint->timemodified = time();
            $DB->update_record('local_sanitizedclone_checkpoint', $checkpoint);
            audit::record($operationid, 'deleted', $step, $count, (int) ((microtime(true) - $started) * 1000));
            if ($tx !== null) {
                $tx->allow_commit();
            }
        } catch (\Throwable $e) {
            if ($tx !== null) {
                $tx->rollback($e);
            }
            throw $e;
        }
    }

    /**
     * Loads or creates a checkpoint.
     */
    public static function checkpoint(int $operationid, string $step): \stdClass {
        global $DB;
        $record = $DB->get_record('local_sanitizedclone_checkpoint', ['operationid' => $operationid, 'step' => $step]);
        if ($record) {
            return $record;
        }
        $record = (object) [
            'operationid' => $operationid, 'step' => $step, 'lastid' => 0,
            'processed' => 0, 'state' => 'pending', 'timemodified' => time(),
        ];
        $record->id = $DB->insert_record('local_sanitizedclone_checkpoint', $record);
        return $record;
    }
}
