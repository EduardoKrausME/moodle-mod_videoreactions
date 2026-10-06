<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videoreactions\completion;

use core_completion\activity_custom_completion;
use mod_videoreactions\reaction_manager;

/**
 * Custom completion rule.
 *
 * @package mod_videoreactions
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
