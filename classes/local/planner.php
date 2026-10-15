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
 * Immutable read-only operation plans.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sanitizedclone\local;


/**
 * planner implementation.
 */
final class planner {
    /**
     * Creates an operation plan.
     *
     * @param array<string,bool> $settings
     * @return array
     */
    public static function create(array $settings): array {
        policy::enforce($settings);
        $inventory = inventory::collect();
        foreach ($settings as $stage => $enabled) {
            if (!$enabled) {
                $inventory['unknown'][] = ['source' => 'disabled:' . $stage, 'rows' => 0,
                    'reason' => 'disabled_by_profile'];
                $inventory['blockers']++;
            }
        }
        return ['policy' => $settings, 'inventory' => $inventory, 'schema' => 1];
    }

    /**
     * Calculates the immutable plan fingerprint.
     */
    public static function hash(array $plan): string {
        return hash('sha256', json_encode($plan, JSON_THROW_ON_ERROR));
    }

    /**
     * Authorizes the reviewed plan for a specified destination.
     *
     * The destination identifier is a public label, not a secret.
     *
     * @param int $profileid Sanitization profile ID.
     * @param string $destinationid Destination identifier.
     * @return int Authorized operation ID.
     */
    public static function authorize(int $profileid, string $destinationid): int {
        global $DB;
        $profile = $DB->get_record('local_sanitizedclone_profile', ['id' => $profileid], '*', MUST_EXIST);
        $settings = policy::normalize(json_decode($profile->configjson, true, 512, JSON_THROW_ON_ERROR));
        $plan = self::create($settings);
        $record = (object) [
            'profileid' => $profileid,
            'state' => 'pending',
            'planhash' => self::hash($plan),
            'destinationid' => $destinationid,
            'configjson' => json_encode($settings, JSON_THROW_ON_ERROR),
            'reportjson' => null,
            'timecreated' => time(),
            'timeexpires' => time() + HOURSECS,
            'timestarted' => null,
            'timefinished' => null,
        ];
        $id = $DB->insert_record('local_sanitizedclone_operation', $record);
        audit::record($id, 'approved', 'plan');
        \local_sanitizedclone\event\operation_authorised::create([
            'context' => \context_system::instance(), 'objectid' => $id,
        ])->trigger();
        return $id;
    }
}
