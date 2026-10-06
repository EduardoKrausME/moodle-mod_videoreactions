<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videoreactions\event;

/**
 * Reaction added event.
 *
 * @package mod_videoreactions
 */
class reaction_added extends \core\event\base {
    /**
     * Initialize event.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videoreactions_reaction';
    }

    /**
     * Name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventreactionadded', 'videoreactions');
    }

    /**
     * URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/videoreactions/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' added a video reaction in activity '{$this->other['activityid']}'.";
    }

    /**
     * Other validation.
     *
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->other['activityid'], $this->other['reaction'], $this->other['videotime'])) {
            throw new \coding_exception('Reaction event requires activityid, reaction and videotime.');
        }
    }
}
