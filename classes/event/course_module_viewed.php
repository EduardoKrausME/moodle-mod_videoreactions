<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videoreactions\event;

/**
 * Activity viewed event.
 *
 * @package mod_videoreactions
 */
class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Initialize event.
     */
    protected function init(): void {
        $this->data['objecttable'] = 'videoreactions';
        parent::init();
    }

    /**
     * Name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventcoursemoduleviewed', 'videoreactions');
    }
}
