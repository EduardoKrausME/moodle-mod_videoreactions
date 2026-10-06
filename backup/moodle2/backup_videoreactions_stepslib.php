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
 * Backup structure.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_videoreactions_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('videoreactions', ['id'], [
            'course', 'name', 'intro', 'introformat', 'videosource', 'sourceconfig', 'videourl',
            'reactions', 'cooldown', 'maxperminute', 'showanimations', 'gradetarget',
            'completionreactions', 'grade', 'timecreated', 'timemodified',
        ]);

        $reactions = new backup_nested_element('reaction_items');
        $reaction = new backup_nested_element('reaction', ['id'], [
            'userid', 'groupid', 'reaction', 'videotime', 'timebucket', 'timecreated',
        ]);

        $sessions = new backup_nested_element('sessions');
        $session = new backup_nested_element('session', ['id'], [
            'userid', 'duration', 'lastposition', 'lastbucket', 'streak', 'watchedmap',
            'timecreated', 'timemodified',
        ]);

        $activity->add_child($reactions);
        $reactions->add_child($reaction);
        $activity->add_child($sessions);
        $sessions->add_child($session);

        $activity->set_source_table('videoreactions', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $reaction->set_source_table('videoreactions_reaction', ['activityid' => backup::VAR_ACTIVITYID]);
            $session->set_source_table('videoreactions_session', ['activityid' => backup::VAR_ACTIVITYID]);
            $reaction->annotate_ids('user', 'userid');
            $reaction->annotate_ids('group', 'groupid');
            $session->annotate_ids('user', 'userid');
        }

        $activity->annotate_files('mod_videoreactions', 'intro', null);
        $activity->annotate_files('local_video_bridge', 'video', 0);

        return $this->prepare_activity_structure($activity);
    }
}
