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
 * Fictional user and academic relational integrity tests.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone;


/**
 * Tests integration test behaviour.
 *
 * @covers \local_sanitizedclone\local\sanitizer\field_sanitizer
 */
final class integration_test extends \advanced_testcase {
    public function test_user_pseudonyms_preserve_primary_keys(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Fictional', 'lastname' => 'Example',
            'email' => 'fictional@example.invalid',
        ]);
        $mapper = new \local_sanitizedclone\local\mapper(str_repeat('A', 48));
        $profileid = $DB->insert_record('local_sanitizedclone_profile', (object) [
            'name' => 'Fixture', 'configjson' => '{}', 'timecreated' => time(),
        ]);
        $opid = $DB->insert_record('local_sanitizedclone_operation', (object) [
            'profileid' => $profileid, 'state' => 'pending',
            'planhash' => str_repeat('0', 64), 'destinationid' => 'destination1234',
            'configjson' => '{}', 'reportjson' => null,
            'timecreated' => time(), 'timeexpires' => time() + HOURSECS,
        ]);
        foreach (\local_sanitizedclone\local\registry::all() as $sanitizer) {
            if ($sanitizer->name() === 'users') {
                $sanitizer->execute($opid, $mapper);
                break;
            }
        }
        $updated = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        $this->assertSame((int) $user->id, (int) $updated->id);
        $this->assertSame($mapper->email((int) $user->id), $updated->email);
        $this->assertSame('nologin', $updated->auth);
        $this->assertSame('*', $updated->password);
        $this->assertLessThanOrEqual(20, strlen($updated->phone1));
        $this->assertLessThanOrEqual(20, strlen($updated->phone2));
    }
}
