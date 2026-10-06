<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videoreactions/backup/moodle2/restore_videoreactions_stepslib.php');

/**
 * Restore task.
 *
 * @package mod_videoreactions
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
