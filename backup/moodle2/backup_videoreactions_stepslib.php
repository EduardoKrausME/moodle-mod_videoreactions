<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

/**
 * Backup structure.
 *
 * @package mod_videoreactions
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
