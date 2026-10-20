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
 * Read-only core relational checks, supporting Moodle 4.5/5.x schema differences.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;


/**
 * integrity implementation.
 */
final class integrity {
    /**
     * Checks the data integrity requirements.
     *
     * @return array{checks: array, violations: int}
     */
    public static function check(): array {
        global $DB;
        $relations = [
            ['user_enrolments', 'userid', 'user', 'id'],
            ['role_assignments', 'userid', 'user', 'id'],
            ['forum_posts', 'userid', 'user', 'id'],
            ['assign_submission', 'userid', 'user', 'id'],
            ['assign_grades', 'userid', 'user', 'id'],
            ['grade_grades', 'userid', 'user', 'id'],
            ['user_info_data', 'userid', 'user', 'id'],
            ['forum_posts', 'discussion', 'forum_discussions', 'id'],
            ['assign_submission', 'assignment', 'assign', 'id'],
            ['user_enrolments', 'enrolid', 'enrol', 'id'],
        ];
        $checks = [];
        $violations = 0;
        foreach ($relations as [$child, $field, $parent, $key]) {
            if (!batch_processor::exists($child) || !batch_processor::exists($parent) ||
                    !in_array($field, batch_processor::columns($child), true) ||
                    !in_array($key, batch_processor::columns($parent), true)) {
                continue;
            }
            // Identifiers are trusted hardcoded constants, never request parameters.
            $sql = "SELECT COUNT(1) FROM {{$child}} c LEFT JOIN {{$parent}} p ON c.$field = p.$key
                     WHERE c.$field IS NOT NULL AND p.$key IS NULL";
            $count = (int) $DB->count_records_sql($sql);
            $checks[] = ['reference' => $child . '.' . $field . ' -> ' . $parent . '.' . $key,
                'invalid' => $count];
            $violations += $count;
        }
        return ['checks' => $checks, 'violations' => $violations];
    }
}
