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

namespace mod_videoreactions\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videoreactions\reaction_manager;

/**
 * Save a reaction.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_reaction extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'reaction' => new external_value(PARAM_TEXT, 'Reaction'),
            'videotime' => new external_value(PARAM_FLOAT, 'Video time'),
            'duration' => new external_value(PARAM_FLOAT, 'Video duration'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $cmid Course module id.
     * @param string $reaction Reaction.
     * @param float $videotime Video time.
     * @param float $duration Duration.
     * @return array
     */
    public static function execute(int $cmid, string $reaction, float $videotime, float $duration): array {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            compact('cmid', 'reaction', 'videotime', 'duration')
        );
        $cm = get_coursemodule_from_id('videoreactions', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videoreactions:react', $context);

        $activity = $DB->get_record('videoreactions', ['id' => $cm->instance], '*', MUST_EXIST);
        $record = reaction_manager::add(
            $activity,
            $cm,
            $context,
            (int)$USER->id,
            $params['reaction'],
            (float)$params['videotime'],
            (float)$params['duration']
        );

        return [
            'success' => true,
            'id' => (int)$record->id,
            'timebucket' => (int)$record->timebucket,
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
            'id' => new external_value(PARAM_INT, 'Reaction id'),
            'timebucket' => new external_value(PARAM_INT, 'Aggregated timeline bucket'),
        ]);
    }
}
