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

require_once($CFG->dirroot . '/mod/videoreactions/backup/moodle2/backup_videoreactions_stepslib.php');

/**
 * Backup task.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_videoreactions_activity_task extends backup_activity_task {
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
            new backup_videoreactions_activity_structure_step('videoreactions_structure', 'videoreactions.xml')
        );
    }

    /**
     * Encode activity links.
     *
     * @param string $content Content.
     * @return string
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot . '/mod/videoreactions', '#');
        return preg_replace(
            "#{$base}/view\.php\?id=([0-9]+)#",
            '$@VIDEOREACTIONSVIEWBYID*$1@$',
            $content
        );
    }
}
