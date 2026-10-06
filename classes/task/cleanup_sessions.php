<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videoreactions\task;

/**
 * Remove expired playback verification state.
 *
 * @package mod_videoreactions
 */
class cleanup_sessions extends \core\task\scheduled_task {
    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('tasks:cleanupsessions', 'videoreactions');
    }

    /**
     * Execute cleanup.
     *
     * Verification sessions are not analytics history and should not accumulate indefinitely.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $DB->delete_records_select(
            'videoreactions_session',
            'timemodified < :cutoff',
            ['cutoff' => time() - DAYSECS]
        );
    }
}
