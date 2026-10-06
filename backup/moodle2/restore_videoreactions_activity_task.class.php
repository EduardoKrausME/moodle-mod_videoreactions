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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videoreactions/backup/moodle2/restore_videoreactions_stepslib.php');

/**
 * Restore task.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_videoreactions_activity_task extends restore_activity_task {
    /**
     * Settings.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Steps.
     */
    protected function define_my_steps(): void {
        $this->add_step(
            new restore_videoreactions_activity_structure_step('videoreactions_structure', 'videoreactions.xml')
        );
    }

    /**
     * Decode intro content.
     *
     * @return array
     */
    public static function define_decode_contents(): array {
        return [new restore_decode_content('videoreactions', ['intro'], 'videoreactions')];
    }

    /**
     * Decode activity links.
     *
     * @return array
     */
    public static function define_decode_rules(): array {
        return [
            new restore_decode_rule(
                'VIDEOREACTIONSVIEWBYID',
                '/mod/videoreactions/view.php?id=$1',
                'course_module'
            ),
        ];
    }
}
