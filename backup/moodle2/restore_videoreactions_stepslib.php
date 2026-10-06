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
 * Restore structure.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_videoreactions_activity_structure_step extends restore_activity_structure_step {
    /**
     * Paths.
     *
     * @return array
     */
    protected function define_structure(): array {
        $paths = [
            new restore_path_element('videoreactions', '/activity/videoreactions'),
        ];

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'videoreactions_reaction',
                '/activity/videoreactions/reaction_items/reaction'
            );
            $paths[] = new restore_path_element(
                'videoreactions_session',
                '/activity/videoreactions/sessions/session'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore activity.
     *
     * @param array $data Data.
     * @return void
     */
    protected function process_videoreactions($data): void {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $newid = $DB->insert_record('videoreactions', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Restore reaction.
     *
     * @param array $data Data.
     * @return void
     */
    protected function process_videoreactions_reaction($data): void {
        global $DB;

        $data = (object)$data;
        $data->activityid = $this->get_new_parentid('videoreactions');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        $data->groupid = !empty($data->groupid)
            ? $this->get_mappingid('group', $data->groupid, 0)
            : 0;
        if ($data->userid) {
            $DB->insert_record('videoreactions_reaction', $data);
        }
    }

    /**
     * Restore playback verification state.
     *
     * @param array $data Data.
     * @return void
     */
    protected function process_videoreactions_session($data): void {
        global $DB;

        $data = (object)$data;
        $data->activityid = $this->get_new_parentid('videoreactions');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if ($data->userid) {
            $DB->insert_record('videoreactions_session', $data);
        }
    }

    /**
     * Restore files.
     *
     * @return void
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_videoreactions', 'intro', null);
        $this->add_related_files('local_video_bridge', 'video', 0);
    }
}
