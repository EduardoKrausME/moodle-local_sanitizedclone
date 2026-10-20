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
 * Built-in and component-provided sanitizers.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;


use local_sanitizedclone\local\sanitizer\field_sanitizer;
use local_sanitizedclone\local\sanitizer\purge_sanitizer;
use local_sanitizedclone\local\sanitizer\files_sanitizer;
use local_sanitizedclone\local\sanitizer\secrets_sanitizer;
use local_sanitizedclone\local\sanitizer\sanitizer_interface;

/**
 * registry implementation.
 */
final class registry {
    /**
     * Returns registered sanitizer implementations.
     *
     * @return sanitizer_interface[]
     */
    public static function all(): array {
        $sanitizers = [
            new field_sanitizer('users', ['user' => [
                'username', 'password', 'auth', 'firstname', 'lastname', 'email', 'idnumber', 'lastip', 'secret',
                'phone1', 'phone2', 'address', 'city', 'description', 'institution',
                'department', 'alternatename', 'firstnamephonetic', 'lastnamephonetic',
                'middlename', 'skype', 'aim', 'url', 'country',
            ]]),
            new field_sanitizer('customfields', ['user_info_data' => ['data']]),
            new field_sanitizer('messages', [
                'messages' => ['subject', 'fullmessage', 'smallmessage', 'fullmessagehtml'],
                'message_read' => ['subject', 'fullmessage', 'smallmessage', 'fullmessagehtml'],
            ]),
            new field_sanitizer('forums', ['forum_posts' => ['subject', 'message']]),
            new field_sanitizer('assignments', [
                'assignsubmission_onlinetext' => ['onlinetext'],
                'assignfeedback_comments' => ['commenttext'],
            ]),
            new purge_sanitizer('logs', [
                'logstore_standard_log', 'logstore_legacy_log', 'user_lastaccess',
            ]),
            new purge_sanitizer('credentials', [
                'sessions', 'external_tokens', 'user_private_key', 'user_preferences',
                'user_devices', 'oauth2_access_token', 'oauth2_refresh_token',
            ]),
            new secrets_sanitizer(),
            new files_sanitizer(),
        ];
        // An optional plugin extension class must implement the explicit contract.
        // Unrecognised plugins remain blockers in the inventory, never silently approved.
        foreach (\core_component::get_plugin_types() as $type => $path) {
            foreach (\core_component::get_plugin_list($type) as $name => $plugindir) {
                $component = $type . '_' . $name;
                if ($component === 'local_sanitizedclone') {
                    continue;
                }
                $class = '\\' . $component . '\\local_sanitizedclone\\sanitizer';
                if (class_exists($class)) {
                    $instance = new $class();
                    if (!$instance instanceof sanitizer_interface) {
                        throw new \coding_exception('Invalid sanitizer contract for ' . $component);
                    }
                    $sanitizers[] = $instance;
                }
            }
        }
        return $sanitizers;
    }
}
