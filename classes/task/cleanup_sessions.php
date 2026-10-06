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

namespace mod_videoreactions\task;

/**
 * Remove expired playback verification state.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
