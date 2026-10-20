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
 * Non-sensitive execution audit log.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;


/**
 * audit implementation.
 */
final class audit {
    /**
     * Records an audit event without personal data.
     */
    public static function record(int $operationid, string $event, string $step = '',
            int $quantity = 0, int $durationms = 0): void {
        global $DB;
        $DB->insert_record('local_sanitizedclone_audit', (object) [
            'operationid' => $operationid,
            'eventtype' => substr($event, 0, 40),
            'step' => substr($step, 0, 100),
            'quantity' => $quantity,
            'durationms' => max(0, $durationms),
            'timecreated' => time(),
        ]);
    }
}
