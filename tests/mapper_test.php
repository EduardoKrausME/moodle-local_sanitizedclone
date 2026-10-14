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
 * Deterministic mapper tests.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone;

defined('MOODLE_INTERNAL') || die();

final class mapper_test extends \advanced_testcase {
    public function test_identifiers_are_stable_unique_and_use_reserved_domain(): void {
        $mapper = new \local_sanitizedclone\local\mapper(str_repeat('x', 48));
        $this->assertSame($mapper->username(10), $mapper->username(10));
        $this->assertNotSame($mapper->username(10), $mapper->username(11));
        $this->assertNotSame($mapper->token('role', 10), $mapper->token('user', 10));
        $this->assertStringEndsWith('@example.invalid', $mapper->email(10));
        $this->assertSame($mapper->email(10), $mapper->email(10));
    }

    public function test_destination_key_is_required(): void {
        $this->expectException(\coding_exception::class);
        new \local_sanitizedclone\local\mapper('short');
    }
}
