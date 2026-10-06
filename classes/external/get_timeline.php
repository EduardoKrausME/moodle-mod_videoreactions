<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videoreactions\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use mod_videoreactions\reaction_manager;

/**
 * Aggregated timeline endpoint.
 *
 * @package mod_videoreactions
 */
class get_timeline extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'mode' => new external_value(PARAM_ALPHA, 'mine, class or hidden'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid Course module id.
     * @param string $mode Display mode.
     * @return array
     */
    public static function execute(int $cmid, string $mode): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'mode'));
        if (!in_array($params['mode'], ['mine', 'class', 'hidden'], true)) {
            throw new invalid_parameter_exception('Invalid reaction display mode');
        }

        $cm = get_coursemodule_from_id('videoreactions', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoreactions:view', $context);

        $activity = $DB->get_record('videoreactions', ['id' => $cm->instance], '*', MUST_EXIST);
        $rows = reaction_manager::timeline($activity, $cm, $context, (int)$USER->id, $params['mode']);

        return [
            'rows' => array_map(static fn($row): array => [
                'timebucket' => (int)$row->timebucket,
                'reaction' => (string)$row->reaction,
                'count' => (int)$row->reactioncount,
            ], $rows),
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'rows' => new external_multiple_structure(new external_single_structure([
                'timebucket' => new external_value(PARAM_INT, 'Timeline bucket'),
                'reaction' => new external_value(PARAM_TEXT, 'Reaction'),
                'count' => new external_value(PARAM_INT, 'Aggregated count'),
            ])),
        ]);
    }
}
