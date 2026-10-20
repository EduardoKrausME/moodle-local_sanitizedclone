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
 * Fixed, conservative policies for component sanitizers.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;


/**
 * Defines the immutable, versioned sanitization profile policy.
 *
 * Every required stage must be explicitly present in a profile.
 */
final class policy {
    /** @var string[] Fixed set of sanitization stages. */
    public const STAGES = ['users', 'customfields', 'messages', 'forums', 'assignments', 'logs', 'credentials', 'secrets', 'files'];

    /**
     * Returns enabled stages by default.
     *
     * @return array<string, bool>
     */
    public static function defaults(): array {
        return array_fill_keys(self::STAGES, true);
    }

    /**
     * Normalizes stage settings.
     *
     * @param array<string, mixed> $settings
     * @return array<string, bool>
     */
    public static function normalize(array $settings): array {
        $result = [];
        foreach (self::STAGES as $stage) {
            $result[$stage] = !empty($settings[$stage]);
        }
        return $result;
    }

    /**
     * Enforces the required sanitization stages.
     *
     * @param array<string, bool> $settings
     */
    public static function enforce(array $settings): void {
        foreach (self::STAGES as $stage) {
            if (!array_key_exists($stage, $settings)) {
                throw new \moodle_exception('allstagesrequired', 'local_sanitizedclone');
            }
        }
    }
}
