<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videoreactions\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videoreactions\reaction_manager;

/**
 * Playback verification heartbeat.
 *
 * @package mod_videoreactions
 */
class track_watch extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'position' => new external_value(PARAM_FLOAT, 'Current video position'),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid Course module id.
     * @param float $position Position.
     * @param float $duration Duration.
     * @return array
     */
    public static function execute(int $cmid, float $position, float $duration): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'position', 'duration'));
        $cm = get_coursemodule_from_id('videoreactions', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoreactions:react', $context);

        $activity = $DB->get_record('videoreactions', ['id' => $cm->instance], '*', MUST_EXIST);
        $result = reaction_manager::track_watch(
            $activity,
            (int)$USER->id,
            (float)$params['position'],
            (float)$params['duration']
        );
        return [
            'success' => true,
            'streak' => (int)$result['streak'],
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success'),
            'streak' => new external_value(PARAM_INT, 'Consecutive plausible heartbeats'),
        ]);
    }
}
