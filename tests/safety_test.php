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
 * Safety guard tests.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone;

defined('MOODLE_INTERNAL') || die();

final class safety_test extends \advanced_testcase {
    public function test_missing_explicit_confirmation_refuses_execution(): void {
        $this->expectException(\moodle_exception::class);
        \local_sanitizedclone\local\environment_guard::assert_safe('/tmp/missing.json', 'destination1234', false);
    }

    public function test_attestation_cannot_be_missing_even_when_confirmed(): void {
        $this->expectException(\moodle_exception::class);
        \local_sanitizedclone\local\environment_guard::assert_safe('/tmp/missing.json', 'destination1234', true);
    }
}
