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
 * Namespaced secret-setting scrubber (does not inspect/log setting values).
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local\sanitizer;


use local_sanitizedclone\local\batch_processor;
use local_sanitizedclone\local\mapper;

/**
 * secrets sanitizer implementation.
 */
final class secrets_sanitizer implements sanitizer_interface {
    /**
     * Returns the sanitization stage name.
     */
    public function name(): string {
        return 'secrets';
    }

    /**
     * Lists the tables handled by this stage.
     */
    public function tables(): array {
        return ['config', 'config_plugins', 'oauth2_issuer'];
    }

    /**
     * Executes the sanitization stage.
     */
    public function execute(int $operationid, mapper $mapper): void {
        foreach (['config', 'config_plugins'] as $table) {
            // These are legitimate Moodle keys; only known key shapes are cleared.
            // Unknown/misnamed secrets are validation blockers requiring manual review.
            batch_processor::walk($operationid, 'secrets:' . $table, $table,
                static function(\stdClass $record): ?\stdClass {
                    if (!isset($record->name) || !preg_match(
                            '/secret|password|passwd|token|api.?key|private.?key|webhook|credential|smtp|client.?key/i',
                            $record->name)) {
                        return null;
                    }
                    return (object) ['id' => $record->id, 'value' => ''];
                });
        }
        if (batch_processor::exists('oauth2_issuer')) {
            $cols = batch_processor::columns('oauth2_issuer');
            $fields = array_intersect(['clientsecret', 'clientid'], $cols);
            batch_processor::walk($operationid, 'secrets:oauth2_issuer', 'oauth2_issuer',
                static function(\stdClass $record) use ($fields): ?\stdClass {
                    $update = (object) ['id' => $record->id];
                    foreach ($fields as $field) {
                        $update->$field = '';
                    }
                    return $fields ? $update : null;
                });
        }
    }
}
