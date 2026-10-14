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
 * Purging disposable security material and raw logs.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local\sanitizer;

defined('MOODLE_INTERNAL') || die();

use local_sanitizedclone\local\batch_processor;
use local_sanitizedclone\local\mapper;

final class purge_sanitizer implements sanitizer_interface {
    /** @var string */
    private string $stage;
    /** @var string[] */
    private array $tables;

    /** @param string[] $tables */
    public function __construct(string $stage, array $tables) {
        $this->stage = $stage;
        $this->tables = $tables;
    }

    public function name(): string {
        return $this->stage;
    }

    public function tables(): array {
        return $this->tables;
    }

    public function execute(int $operationid, mapper $mapper): void {
        foreach ($this->tables as $table) {
            batch_processor::clear($operationid, $this->stage . ':' . $table, $table);
        }
    }
}
