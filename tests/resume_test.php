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
 * Transaction rollback, checkpoint, and resume tests.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone;


/**
 * Tests resume test behaviour.
 *
 * @covers \local_sanitizedclone\local\batch_processor
 */
final class resume_test extends \advanced_testcase {
    /**
     * Method test_batch_failure_rolls_back_and_retry_is_idempotent.
     *
     * @return void Return value.
     */
    public function test_batch_failure_rolls_back_and_retry_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest();
        // A deliberate rollback must not dispose PHPUnit's enclosing transaction.
        $this->preventResetByRollback();
        $profileid = $DB->insert_record('local_sanitizedclone_profile', (object) [
            'name' => 'Example', 'configjson' => '{}', 'timecreated' => time(),
        ]);
        $opid = $DB->insert_record('local_sanitizedclone_operation', (object) [
            'profileid' => $profileid, 'state' => 'pending',
            'planhash' => str_repeat('0', 64), 'destinationid' => 'destination1234',
            'configjson' => '{}', 'reportjson' => null,
            'timecreated' => time(), 'timeexpires' => time() + HOURSECS,
        ]);
        try {
            \local_sanitizedclone\local\batch_processor::walk($opid, 'test:profiles',
                'local_sanitizedclone_profile', static function(\stdClass $row): \stdClass {
                    throw new \RuntimeException('simulated failure');
                });
            $this->fail('Expected exception');
        } catch (\RuntimeException $expected) {
            $this->assertSame('Example', $DB->get_field('local_sanitizedclone_profile', 'name', ['id' => $profileid]));
        }
        \local_sanitizedclone\local\batch_processor::walk($opid, 'test:profiles',
            'local_sanitizedclone_profile', static function(\stdClass $row): \stdClass {
                return (object) ['id' => $row->id, 'name' => 'Pseudonym'];
            });
        $first = \local_sanitizedclone\local\batch_processor::checkpoint($opid, 'test:profiles');
        $this->assertSame('done', $first->state);
        $this->assertSame(1, (int) $first->processed);
        \local_sanitizedclone\local\batch_processor::walk($opid, 'test:profiles',
            'local_sanitizedclone_profile', static function(\stdClass $row): \stdClass {
                throw new \RuntimeException('must not rerun');
            });
        $this->assertSame('Pseudonym', $DB->get_field('local_sanitizedclone_profile', 'name', ['id' => $profileid]));
    }
}
