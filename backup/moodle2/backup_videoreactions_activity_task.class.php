<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videoreactions/backup/moodle2/backup_videoreactions_stepslib.php');

/**
 * Backup task.
 *
 * @package mod_videoreactions
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
