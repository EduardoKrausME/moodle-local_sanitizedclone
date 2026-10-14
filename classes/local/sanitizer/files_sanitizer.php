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
 * Delete sensitive attachments using File API, preserving ordinary course assets.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local\sanitizer;


use local_sanitizedclone\local\batch_processor;
use local_sanitizedclone\local\mapper;

/**
 * files sanitizer implementation.
 */
final class files_sanitizer implements sanitizer_interface {
    /** @var array<string, string[]> Deliberately narrow file-area allowlist. */
    public const AREAS = [
        'assignsubmission_file' => ['submission_files'],
        'mod_forum' => ['attachment'],
        'user' => ['private', 'draft', 'profile'],
    ];

    /**
     * Returns the sanitization stage name.
     */
    public function name(): string {
        return 'files';
    }

    /**
     * Lists the tables handled by this stage.
     */
    public function tables(): array {
        return [];
    }

    /**
     * Executes the sanitization stage.
     */
    public function execute(int $operationid, mapper $mapper): void {
        global $DB;
        $storage = get_file_storage();
        // We intentionally do not manipulate contenthash or file-pool directories directly.
        $checkpoint = batch_processor::checkpoint($operationid, 'files:attachments');
        if ($checkpoint->state === 'done') {
            return;
        }
        $lastid = (int) $checkpoint->lastid;
        do {
            $records = $DB->get_records_select('files', 'id > :id', ['id' => $lastid],
                'id ASC', 'id, component, filearea, filename', 0, 100);
            if (!$records) {
                break;
            }
            foreach ($records as $record) {
                $lastid = (int) $record->id;
                if ($record->filename === '.' ||
                        !in_array($record->filearea, self::AREAS[$record->component] ?? [], true)) {
                    continue;
                }
                // A checkpoint advances only after a successful File API deletion. Repeated calls are safe.
                $file = $storage->get_file_by_id($record->id);
                if ($file && !$file->delete()) {
                    throw new \coding_exception('File API refused an attachment deletion');
                }
                $checkpoint->processed++;
                $checkpoint->lastid = $lastid;
                $checkpoint->timemodified = time();
                $DB->update_record('local_sanitizedclone_checkpoint', $checkpoint);
            }
            $checkpoint->lastid = $lastid;
            $checkpoint->timemodified = time();
            $DB->update_record('local_sanitizedclone_checkpoint', $checkpoint);
        } while (count($records) === 100);
        $checkpoint->state = 'done';
        $checkpoint->timemodified = time();
        $DB->update_record('local_sanitizedclone_checkpoint', $checkpoint);
    }
}
