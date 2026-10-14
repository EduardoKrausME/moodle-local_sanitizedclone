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
 * Fail-closed inventory of known and unreviewed data sources.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;

defined('MOODLE_INTERNAL') || die();

use local_sanitizedclone\local\sanitizer\files_sanitizer;

final class inventory {
    /**
     * Whole-table coverage is intentionally NOT inferred from a few handled columns.
     * @return array{known: array, unknown: array, files: array, blockers: int}
     */
    public static function collect(): array {
        global $DB;
        $covered = [];
        foreach (registry::all() as $sanitizer) {
            foreach ($sanitizer->tables() as $table) {
                $covered[$table] = $sanitizer->name();
            }
        }
        $known = [];
        $unknown = [];
        foreach ($DB->get_tables() as $table) {
            if (str_starts_with($table, 'local_sanitizedclone_')) {
                continue;
            }
            $count = (int) $DB->count_records($table);
            if ($count === 0) {
                continue;
            }
            if (isset($covered[$table])) {
                $known[] = ['table' => $table, 'stage' => $covered[$table], 'rows' => $count,
                    'coverage' => 'partial_column_review'];
                // Extra columns can contain personal data, so known tables still require review.
                $unknown[] = ['source' => 'columns:' . $table, 'rows' => $count,
                    'reason' => 'partial_column_review'];
            } else if ($table !== 'files') {
                $unknown[] = ['source' => 'table:' . $table, 'rows' => $count,
                    'reason' => 'not_analyzed'];
            }
        }
        $files = [];
        if ($DB->get_manager()->table_exists(new \xmldb_table('files'))) {
            $sql = "SELECT component, filearea, COUNT(1) AS quantity
                      FROM {files}
                     WHERE filename <> :directory
                  GROUP BY component, filearea";
            // Moodle may reject non-unique first-column grouping keys; use a recordset instead.
            $files = [];
            $rs = $DB->get_recordset_sql($sql, ['directory' => '.']);
            foreach ($rs as $area) {
                $knownarea = in_array($area->filearea, files_sanitizer::AREAS[$area->component] ?? [], true);
                $files[] = ['component' => $area->component, 'filearea' => $area->filearea,
                    'rows' => (int) $area->quantity, 'action' => $knownarea ? 'delete' : 'unreviewed'];
                if (!$knownarea) {
                    $unknown[] = ['source' => 'files:' . $area->component . '/' . $area->filearea,
                        'rows' => (int) $area->quantity, 'reason' => 'binary_or_unreviewed'];
                }
            }
            $rs->close();
        }
        // Even after sanitization, secrets embedded in config tables require outside review.
        $unknown[] = ['source' => 'external_services_and_secrets', 'rows' => 0,
            'reason' => 'manual_attestation_required'];
        return ['known' => $known, 'unknown' => $unknown, 'files' => $files,
            'blockers' => count($unknown)];
    }
}
