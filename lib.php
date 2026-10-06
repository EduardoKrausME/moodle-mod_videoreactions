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

use local_video_bridge\source\manager as source_manager;
use mod_videoreactions\reaction_manager;

/**
 * Core callbacks for Video Reactions.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Declare supported Moodle features.
 *
 * @param string $feature Feature constant.
 * @return bool|string|null
 */
function videoreactions_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Add an activity instance.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_videoreactions_mod_form|null $mform Form.
 * @return int
 */
function videoreactions_add_instance(stdClass $data, ?mod_videoreactions_mod_form $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    (new source_manager())->normalise_record($data);

    $data->id = $DB->insert_record('videoreactions', $data);
    $context = context_module::instance((int)$data->coursemodule);
    (new source_manager())->save_files($data, $context);
    videoreactions_grade_item_update($data);
    return (int)$data->id;
}

/**
 * Update an activity instance.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_videoreactions_mod_form|null $mform Form.
 * @return bool
 */
function videoreactions_update_instance(stdClass $data, ?mod_videoreactions_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $previoussource = (string)$DB->get_field('videoreactions', 'videosource', ['id' => $data->id], MUST_EXIST);

    (new source_manager())->normalise_record($data);
    $result = $DB->update_record('videoreactions', $data);

    $context = context_module::instance((int)$data->coursemodule);
    (new source_manager())->save_files($data, $context, $previoussource);
    videoreactions_grade_item_update($data);
    videoreactions_update_grades($data);
    return $result;
}

/**
 * Delete an activity instance and user data.
 *
 * @param int $id Instance id.
 * @return bool
 */
function videoreactions_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('videoreactions', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videoreactions', $id, $activity->course, false, IGNORE_MISSING);
    if ($cm) {
        (new source_manager())->delete_files(context_module::instance($cm->id));
    }

    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('videoreactions_reaction', ['activityid' => $id]);
    $DB->delete_records('videoreactions_session', ['activityid' => $id]);
    $DB->delete_records('local_video_bridge_progress', [
        'component' => 'mod_videoreactions',
        'itemid' => $id,
    ]);
    $DB->delete_records('videoreactions', ['id' => $id]);
    $transaction->allow_commit();

    videoreactions_grade_item_delete($activity);
    return true;
}

/**
 * Create or update gradebook item.
 *
 * @param stdClass $activity Activity.
 * @param array|null $grades Optional grade rows.
 * @return int
 */
function videoreactions_grade_item_update(stdClass $activity, ?array $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $maximum = max(0.0, (float)$activity->grade);
    $item = [
        'itemname' => clean_param($activity->name, PARAM_NOTAGS),
        'gradetype' => $maximum > 0 ? GRADE_TYPE_VALUE : GRADE_TYPE_NONE,
        'grademin' => 0,
        'grademax' => max(1.0, $maximum),
    ];

    return grade_update(
        'mod/videoreactions',
        $activity->course,
        'mod',
        'videoreactions',
        $activity->id,
        0,
        $grades,
        $item
    );
}

/**
 * Publish participation grades.
 *
 * One grouped query calculates counts for every learner when a full refresh is requested.
 *
 * @param stdClass $activity Activity.
 * @param int $userid Optional user id.
 * @param bool $nullifnone Send a null grade when the target user has no reactions.
 * @return void
 */
function videoreactions_update_grades(stdClass $activity, int $userid = 0, bool $nullifnone = true): void {
    global $DB;

    if ((float)$activity->grade <= 0) {
        videoreactions_grade_item_update($activity);
        return;
    }

    $params = ['activityid' => $activity->id];
    $usersql = '';
    if ($userid > 0) {
        $usersql = ' AND userid = :userid';
        $params['userid'] = $userid;
    }

    $rows = $DB->get_records_sql(
        "SELECT userid, COUNT(1) AS reactioncount
           FROM {videoreactions_reaction}
          WHERE activityid = :activityid{$usersql}
       GROUP BY userid",
        $params
    );

    $target = max(1, (int)$activity->gradetarget);
    $maximum = max(0.0, (float)$activity->grade);
    $grades = [];
    foreach ($rows as $row) {
        $grades[(int)$row->userid] = (object)[
            'userid' => (int)$row->userid,
            'rawgrade' => min($maximum, ((int)$row->reactioncount / $target) * $maximum),
        ];
    }

    if ($userid > 0 && !$grades && $nullifnone) {
        $grades[$userid] = (object)[
            'userid' => $userid,
            'rawgrade' => null,
        ];
    }

    videoreactions_grade_item_update($activity, $grades);
}

/**
 * Delete gradebook item.
 *
 * @param stdClass $activity Activity.
 * @return int
 */
function videoreactions_grade_item_delete(stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update(
        'mod/videoreactions',
        $activity->course,
        'mod',
        'videoreactions',
        $activity->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * Course module cache information.
 *
 * @param stdClass $cm Course module.
 * @return cached_cm_info|null
 */
function videoreactions_get_coursemodule_info(stdClass $cm): ?cached_cm_info {
    global $DB;

    $activity = $DB->get_record(
        'videoreactions',
        ['id' => $cm->instance],
        'id,name,intro,introformat,completionreactions'
    );
    if (!$activity) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videoreactions', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionreactions' => (int)$activity->completionreactions,
        ];
    }
    return $info;
}

/**
 * Completion rule descriptions.
 *
 * @param cached_cm_info $cm Course module cache.
 * @return array
 */
function videoreactions_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    $required = (int)($cm->customdata['customcompletionrules']['completionreactions'] ?? 0);
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC || $required <= 0) {
        return [];
    }
    return [get_string('completiondetail:reactions', 'videoreactions', $required)];
}

/**
 * Legacy completion callback.
 *
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @param int $userid User.
 * @param bool $type Requested state direction.
 * @return bool
 */
function videoreactions_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;

    $activity = $DB->get_record('videoreactions', ['id' => $cm->instance], '*', MUST_EXIST);
    if ((int)$activity->completionreactions <= 0) {
        return true;
    }
    return reaction_manager::count_for_user((int)$activity->id, $userid) >= (int)$activity->completionreactions;
}

/**
 * Define course reset option.
 *
 * @param MoodleQuickForm $mform Reset form.
 * @return void
 */
function videoreactions_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'videoreactionsheader', get_string('modulenameplural', 'videoreactions'));
    $mform->addElement('advcheckbox', 'reset_videoreactions', get_string('resetreactions', 'videoreactions'));
}

/**
 * Default reset form values.
 *
 * @param stdClass $course Course.
 * @return array
 */
function videoreactions_reset_course_form_defaults($course): array {
    return ['reset_videoreactions' => 1];
}

/**
 * Reset learner data for a course.
 *
 * @param stdClass $data Course reset data.
 * @return array
 */
function videoreactions_reset_userdata($data): array {
    global $DB;

    if (empty($data->reset_videoreactions)) {
        return [];
    }

    $activityids = $DB->get_fieldset_select('videoreactions', 'id', 'course = :course', ['course' => $data->courseid]);
    if (!$activityids) {
        return [];
    }

    [$insql, $params] = $DB->get_in_or_equal($activityids, SQL_PARAMS_NAMED, 'activity');
    $DB->delete_records_select('videoreactions_reaction', "activityid {$insql}", $params);
    $DB->delete_records_select('videoreactions_session', "activityid {$insql}", $params);
    $bridgeparams = ['component' => 'mod_videoreactions'] + $params;
    $DB->delete_records_select(
        'local_video_bridge_progress',
        "component = :component AND itemid {$insql}",
        $bridgeparams
    );

    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $activities = $DB->get_records_list('videoreactions', 'id', $activityids);
    $completion = new completion_info(get_course($data->courseid));
    $modinfo = get_fast_modinfo($data->courseid);
    foreach ($activities as $activity) {
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
        $cm = get_coursemodule_from_instance(
            'videoreactions',
            $activity->id,
            $activity->course,
            false,
            IGNORE_MISSING
        );
        if ($cm && isset($modinfo->cms[$cm->id])) {
            $completion->reset_all_state($modinfo->get_cm($cm->id));
        }
    }

    return [[
        'component' => get_string('modulenameplural', 'videoreactions'),
        'item' => get_string('resetreactionsstatus', 'videoreactions'),
        'error' => false,
    ]];
}
