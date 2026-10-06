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

namespace mod_videoreactions\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection Collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videoreactions_reaction', [
            'userid' => 'privacy:metadata:reaction:userid',
            'groupid' => 'privacy:metadata:reaction:groupid',
            'reaction' => 'privacy:metadata:reaction:reaction',
            'videotime' => 'privacy:metadata:reaction:videotime',
            'timecreated' => 'privacy:metadata:reaction:timecreated',
        ], 'privacy:metadata:reaction');

        $collection->add_database_table('videoreactions_session', [
            'userid' => 'privacy:metadata:session:userid',
            'lastposition' => 'privacy:metadata:session:lastposition',
            'watchedmap' => 'privacy:metadata:session:watchedmap',
            'timemodified' => 'privacy:metadata:session:timemodified',
        ], 'privacy:metadata:session');

        return $collection;
    }

    /**
     * Return module contexts containing data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $base = "FROM {context} ctx
                  JOIN {course_modules} cm
                    ON cm.id = ctx.instanceid
                   AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m
                    ON m.id = cm.module
                   AND m.name = :modname
                  JOIN {videoreactions} v
                    ON v.id = cm.instance";

        $contextlist->add_from_sql(
            "SELECT ctx.id
               {$base}
               JOIN {videoreactions_reaction} r
                 ON r.activityid = v.id
                AND r.userid = :userid",
            [
                'contextlevel' => CONTEXT_MODULE,
                'modname' => 'videoreactions',
                'userid' => $userid,
            ]
        );

        $contextlist->add_from_sql(
            "SELECT ctx.id
               {$base}
               JOIN {videoreactions_session} s
                 ON s.activityid = v.id
                AND s.userid = :userid",
            [
                'contextlevel' => CONTEXT_MODULE,
                'modname' => 'videoreactions',
                'userid' => $userid,
            ]
        );

        return $contextlist;
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist Context list.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('videoreactions', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            $reactions = $DB->get_records(
                'videoreactions_reaction',
                ['activityid' => $cm->instance, 'userid' => $userid],
                'timecreated ASC'
            );
            $session = $DB->get_record('videoreactions_session', [
                'activityid' => $cm->instance,
                'userid' => $userid,
            ]);

            $exportedreactions = array_map(static fn($row): array => [
                'reaction' => $row->reaction,
                'videotime' => (float)$row->videotime,
                'groupid' => (int)$row->groupid,
                'timecreated' => transform::datetime((int)$row->timecreated),
            ], array_values($reactions));

            writer::with_context($context)->export_data([], (object)[
                'reactions' => $exportedreactions,
                'playbackverification' => $session ? [
                    'lastposition' => (float)$session->lastposition,
                    'watchedmap' => json_decode((string)$session->watchedmap, true) ?: [],
                    'timemodified' => transform::datetime((int)$session->timemodified),
                ] : null,
            ]);
        }
    }

    /**
     * Delete all user data in a module context.
     *
     * @param context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $CFG, $DB;

        if (!$context instanceof context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('videoreactions', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $activity = $DB->get_record('videoreactions', ['id' => $cm->instance], '*', IGNORE_MISSING);
        $DB->delete_records('videoreactions_reaction', ['activityid' => $cm->instance]);
        $DB->delete_records('videoreactions_session', ['activityid' => $cm->instance]);

        if ($activity) {
            require_once($CFG->libdir . '/gradelib.php');
            grade_update(
                'mod/videoreactions',
                $activity->course,
                'mod',
                'videoreactions',
                $activity->id,
                0,
                null,
                ['reset' => true]
            );
            $completion = new \completion_info(get_course($activity->course));
            $modinfo = get_fast_modinfo($activity->course);
            if (isset($modinfo->cms[$cm->id])) {
                $completion->reset_all_state($modinfo->get_cm($cm->id));
            }
        }
    }

    /**
     * Delete one user's data in approved contexts.
     *
     * @param approved_contextlist $contextlist Context list.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/videoreactions/lib.php');

        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('videoreactions', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            $activity = $DB->get_record('videoreactions', ['id' => $cm->instance], '*', IGNORE_MISSING);
            $DB->delete_records('videoreactions_reaction', [
                'activityid' => $cm->instance,
                'userid' => $userid,
            ]);
            $DB->delete_records('videoreactions_session', [
                'activityid' => $cm->instance,
                'userid' => $userid,
            ]);

            if ($activity) {
                \videoreactions_update_grades($activity, $userid, true);
                $completion = new \completion_info(get_course($activity->course));
                $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
            }
        }
    }
}
