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
 * Explicit column-level sanitization, avoiding serialized and binary columns.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local\sanitizer;


use local_sanitizedclone\local\batch_processor;
use local_sanitizedclone\local\mapper;

/**
 * field sanitizer implementation.
 */
final class field_sanitizer implements sanitizer_interface {
    /** @var string */
    private string $stage;
    /** @var array<string, string[]> */
    private array $spec;

    /**
     * Initializes the component configuration.
     *
     * @param array<string, string[]> $spec
     */
    public function __construct(string $stage, array $spec) {
        $this->stage = $stage;
        $this->spec = $spec;
    }

    /**
     * Returns the sanitization stage name.
     */
    public function name(): string {
        return $this->stage;
    }

    /**
     * Lists the tables handled by this stage.
     */
    public function tables(): array {
        return array_keys($this->spec);
    }

    /**
     * Executes the sanitization stage.
     */
    public function execute(int $operationid, mapper $mapper): void {
        foreach ($this->spec as $table => $fields) {
            $actual = batch_processor::columns($table);
            if (!$actual) {
                continue;
            }
            $target = array_values(array_intersect($fields, $actual));
            batch_processor::walk($operationid, $this->stage . ':' . $table, $table,
                static function(\stdClass $record) use ($target, $mapper, $table): ?\stdClass {
                    if (!$target) {
                        return null;
                    }
                    $update = (object) ['id' => $record->id];
                    foreach ($target as $field) {
                        if ($table === 'user') {
                            $token = $mapper->token('user', (int) $record->id);
                            $update->$field = match ($field) {
                                'username' => (int) $record->id === 1 ? 'guest' : $mapper->username((int) $record->id),
                                'email' => $mapper->email((int) $record->id),
                                'firstname' => 'Test' . substr($token, 0, 8),
                                'lastname' => 'Student' . substr($token, 8, 8),
                                'auth' => 'nologin',
                                'lastip', 'secret' => '',
                                'password' => '*',
                                'country' => '',
                                'lang' => 'en',
                                default => 'sc_' . substr($token, 0, 16),
                            };
                        } else {
                            // Stable fictional replacement for recognised free-text fields only.
                            $update->$field = 'Sanitized test content';
                        }
                    }
                    return $update;
                });
        }
    }
}
