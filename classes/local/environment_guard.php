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
 * Independent, destination-specific CLI attestation.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;

defined('MOODLE_INTERNAL') || die();

final class environment_guard {
    /**
     * Attestation MUST live outside dirroot and dataroot and be generated on the clone.
     * @return string Destination-only secret; never persisted or logged.
     */
    public static function assert_safe(string $filepath, string $destinationid, bool $confirmed): string {
        global $CFG;
        if (PHP_SAPI !== 'cli' || !defined('CLI_SCRIPT') || !CLI_SCRIPT || !$confirmed) {
            throw new \moodle_exception('unsafeenvironment', 'local_sanitizedclone');
        }
        $file = realpath($filepath);
        $dirroot = realpath($CFG->dirroot);
        $dataroot = realpath($CFG->dataroot);
        if (!$file || !$dirroot || !$dataroot || !is_file($file) || is_link($filepath) ||
                str_starts_with($file, $dirroot . DIRECTORY_SEPARATOR) ||
                str_starts_with($file, $dataroot . DIRECTORY_SEPARATOR)) {
            throw new \moodle_exception('invalidattestation', 'local_sanitizedclone');
        }
        $mode = fileperms($file);
        if ($mode === false || ($mode & 0007) !== 0 || ($mode & 0020) !== 0) {
            throw new \moodle_exception('invalidattestation', 'local_sanitizedclone');
        }
        $json = file_get_contents($file);
        $record = is_string($json) ? json_decode($json, true) : null;
        $secret = getenv('SANITIZEDCLONE_DESTINATION_TOKEN');
        if (!is_array($record) || !is_string($secret) || strlen($secret) < 32 ||
                !preg_match('/^[a-zA-Z0-9_-]{12,64}$/D', $destinationid)) {
            throw new \moodle_exception('invalidattestation', 'local_sanitizedclone');
        }
        $required = ['destination_id', 'destination_dbname', 'destination_wwwroot',
            'destination_dataroot', 'source_dbname', 'source_wwwroot', 'source_dataroot', 'token_sha256'];
        foreach ($required as $field) {
            if (!isset($record[$field]) || !is_string($record[$field]) || $record[$field] === '') {
                throw new \moodle_exception('invalidattestation', 'local_sanitizedclone');
            }
        }
        if (!hash_equals($record['destination_id'], $destinationid) ||
                !hash_equals(strtolower($record['token_sha256']), hash('sha256', $secret)) ||
                $record['destination_dbname'] !== $CFG->dbname ||
                $record['destination_wwwroot'] !== $CFG->wwwroot ||
                realpath($record['destination_dataroot']) !== $dataroot ||
                $record['source_dbname'] === $CFG->dbname ||
                $record['source_wwwroot'] === $CFG->wwwroot ||
                realpath($record['source_dataroot']) === $dataroot ||
                !str_starts_with($CFG->wwwroot, 'https://')) {
            throw new \moodle_exception('unsafeenvironment', 'local_sanitizedclone');
        }
        // A credential proof is necessary, not a proof of physical/ACL isolation.
        return $secret;
    }
}
