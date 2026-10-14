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
 * Exclusive command-line entrypoint: no HTTP destructive execution.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'operation' => 0,
    'attestation' => '',
    'confirm-isolated' => false,
    'manifest' => '',
], ['h' => 'help']);

if ($unrecognised || $options['help'] || !$options['operation'] || !$options['attestation']) {
    $help = get_string('clihelp', 'local_sanitizedclone');
    cli_writeln($help);
    exit($options['help'] ? 0 : 2);
}

try {
    // Validate manifest destination before any destructive sanitization begins.
    if ($options['manifest']) {
        $directory = realpath(dirname($options['manifest']));
        $root = realpath($CFG->dirroot);
        $data = realpath($CFG->dataroot);
        if (!$directory || $directory === $root || $directory === $data ||
                str_starts_with($directory, $root . DIRECTORY_SEPARATOR) ||
                str_starts_with($directory, $data . DIRECTORY_SEPARATOR) ||
                file_exists($options['manifest']) || is_link($options['manifest'])) {
            throw new \moodle_exception('invalidmanifestpath', 'local_sanitizedclone');
        }
    }
    $report = \local_sanitizedclone\local\executor::run((int) $options['operation'],
        $options['attestation'], (bool) $options['confirm-isolated']);
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if ($options['manifest']) {
        $oldmask = umask(0077);
        try {
            $handle = fopen($options['manifest'], 'x');
            if (!$handle) {
                throw new \moodle_exception('manifestwritefailed', 'local_sanitizedclone');
            }
            try {
                if (fwrite($handle, $json) !== strlen($json)) {
                    throw new \moodle_exception('manifestwritefailed', 'local_sanitizedclone');
                }
            } finally {
                fclose($handle);
            }
        } finally {
            umask($oldmask);
        }
    }
    cli_writeln($json);
    exit($report['approved'] ? 0 : 3);
} catch (\Throwable $e) {
    // Avoid emitting exception messages, which can include sensitive database values.
    cli_error(get_string('clifailed', 'local_sanitizedclone', get_class($e)), 1);
}
