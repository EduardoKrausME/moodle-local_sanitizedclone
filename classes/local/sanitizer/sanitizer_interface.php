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
 * Third-party sanitizer extension contract.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local\sanitizer;


/**
 * sanitizer interface implementation.
 */
interface sanitizer_interface {
    /** Stable checkpoint/manifest stage name. */
    public function name(): string;

    /**
     * Must be resumable and idempotent. Do NOT log raw data.
     * @param int $operationid
     * @param \local_sanitizedclone\local\mapper $mapper
     */
    public function execute(int $operationid, \local_sanitizedclone\local\mapper $mapper): void;

    /** Tables for which this component takes full responsibility. */
    public function tables(): array;
}
