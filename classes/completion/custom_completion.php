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

namespace mod_videoreactions\completion;

use core_completion\activity_custom_completion;
use mod_videoreactions\reaction_manager;

/**
 * Custom completion rule.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Completion state for the minimum reactions rule.
     *
     * @param string $rule Rule.
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);
        $required = (int)($this->cm->customdata['customcompletionrules']['completionreactions'] ?? 0);
        if ($required <= 0) {
            return COMPLETION_COMPLETE;
        }

        $activityid = (int)$this->cm->instance;
        if (!$DB->record_exists('videoreactions', ['id' => $activityid])) {
            return COMPLETION_INCOMPLETE;
        }

        return reaction_manager::count_for_user($activityid, (int)$this->userid) >= $required
            ? COMPLETION_COMPLETE
            : COMPLETION_INCOMPLETE;
    }

    /**
     * Defined rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionreactions'];
    }

    /**
     * Descriptions.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $required = (int)($this->cm->customdata['customcompletionrules']['completionreactions'] ?? 0);
        return [
            'completionreactions' => get_string('completiondetail:reactions', 'videoreactions', $required),
        ];
    }

    /**
     * Sort order.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return ['completionreactions'];
    }
}
