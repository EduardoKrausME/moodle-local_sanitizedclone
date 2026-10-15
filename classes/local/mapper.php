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
 * Deterministic, collision-resistant pseudonyms.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;


/**
 * mapper implementation.
 */
final class mapper {
    /** @var string */
    private string $key;

    /**
     * Initializes the component configuration.
     */
    public function __construct(string $key) {
        if (strlen($key) < 32) {
            throw new \coding_exception('Insufficient destination-only pseudonymisation key');
        }
        $this->key = $key;
    }

    /**
     * Generates a deterministic pseudonym token.
     *
     * The token is stable across resumptions with the same destination key.
     *
     * @param string $scope Token namespace.
     * @param int $id Original record identifier.
     * @return string Pseudonym token.
     */
    public function token(string $scope, int $id): string {
        return substr(hash_hmac('sha256', $scope . ':' . $id, $this->key), 0, 24);
    }

    /**
     * Generates a pseudonymous email using a reserved domain.
     *
     * The example.invalid domain is reserved under RFC 2606.
     *
     * @param int $id Original user identifier.
     * @return string Pseudonymous email.
     */
    public function email(int $id): string {
        return 'student-' . $this->token('user', $id) . '@example.invalid';
    }

    /**
     * Returns a fictional username.
     */
    public function username(int $id): string {
        return 'sc_' . $this->token('user', $id);
    }
}
