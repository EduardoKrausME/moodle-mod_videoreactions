<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videoreactions;

use context_module;
use local_video_bridge\progress\manager as bridge_progress;
use moodle_exception;
use stdClass;

/**
 * Reaction storage, playback verification and aggregate queries.
 *
 * @package mod_videoreactions
 */
class reaction_manager {
    /** Seconds represented by one visual reaction bucket. */
    public const REACTION_BUCKET_SECONDS = 5;

    /** Maximum age of the most recent playback heartbeat accepted for a reaction. */
    public const SESSION_TTL = 12;

    /**
     * Return configured reactions.
     *
     * @param stdClass $activity Activity.
     * @return array
     */
    public static function enabled_reactions(stdClass $activity): array {
        $catalog = ['👏', '❤️', '😂', '😮', '🤔', '❓'];
        $decoded = json_decode((string)$activity->reactions, true);
        if (!is_array($decoded)) {
            return $catalog;
        }
        return array_values(array_intersect($decoded, $catalog));
    }

    /**
     * Record a playback heartbeat with server-side plausibility checks.
     *
     * The record is intentionally short-lived and is not a second analytics system. Video Bridge owns
     * long-term viewing progress; this state only makes one-off arbitrary reaction POSTs insufficient.
     *
     * @param stdClass $activity Activity.
     * @param int $userid User id.
     * @param float $position Current playback position.
     * @param float $duration Duration.
     * @return array
     */
    public static function track_watch(stdClass $activity, int $userid, float $position, float $duration): array {
        global $DB;

        $duration = max(0.0, min(604800.0, $duration));
        $position = max(0.0, $position);
        if ($duration <= 0 || $position > $duration + 2) {
            throw new moodle_exception('error:invalidtime', 'videoreactions');
        }

        $now = time();
        $session = $DB->get_record('videoreactions_session', [
            'activityid' => $activity->id,
            'userid' => $userid,
        ]);

        $bucket = bridge_progress::bucket_for_position((int)floor($position), (int)ceil($duration));
        $watched = [];
        $streak = 1;

        if ($session) {
            $watched = json_decode((string)$session->watchedmap, true);
            if (!is_array($watched)) {
                $watched = [];
            }

            $rawelapsed = $now - (int)$session->timemodified;
            $elapsed = max(1, min(30, $rawelapsed));
            $delta = $position - (float)$session->lastposition;
            $plausible = $rawelapsed >= 1
                && $rawelapsed <= 30
                && $delta >= 0.05
                && $delta <= (($elapsed * 4.0) + 4.0);
            $streak = $plausible ? min(100, (int)$session->streak + 1) : 1;
        }

        if ($bucket > 0) {
            $watched[$bucket] = $bucket;
        }
        ksort($watched, SORT_NUMERIC);
        $watched = array_values($watched);

        if (!$session) {
            $session = (object)[
                'activityid' => $activity->id,
                'userid' => $userid,
                'timecreated' => $now,
            ];
        }
        $session->duration = $duration;
        $session->lastposition = $position;
        $session->lastbucket = $bucket;
        $session->streak = $streak;
        $session->watchedmap = json_encode($watched, JSON_THROW_ON_ERROR);
        $session->timemodified = $now;

        if (empty($session->id)) {
            $session->id = $DB->insert_record('videoreactions_session', $session);
        } else {
            $DB->update_record('videoreactions_session', $session);
        }

        return ['streak' => $streak, 'bucket' => $bucket];
    }

    /**
     * Store a verified reaction.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $cm Course module.
     * @param context_module $context Module context.
     * @param int $userid User id.
     * @param string $reaction Reaction.
     * @param float $videotime Video position.
     * @param float $duration Video duration.
     * @return stdClass Inserted row.
     */
    public static function add(
        stdClass $activity,
        stdClass $cm,
        context_module $context,
        int $userid,
        string $reaction,
        float $videotime,
        float $duration
    ): stdClass {
        global $DB;

        if (!in_array($reaction, self::enabled_reactions($activity), true)) {
            throw new moodle_exception('error:reactiondisabled', 'videoreactions');
        }

        $session = $DB->get_record('videoreactions_session', [
            'activityid' => $activity->id,
            'userid' => $userid,
        ]);
        $now = time();
        if (!$session || ($now - (int)$session->timemodified) > self::SESSION_TTL || (int)$session->streak < 2) {
            throw new moodle_exception('error:playbacknotverified', 'videoreactions');
        }

        $duration = max((float)$session->duration, $duration);
        if ($duration <= 0 || $videotime < 0 || $videotime > $duration + 2) {
            throw new moodle_exception('error:invalidtime', 'videoreactions');
        }

        $bucket = bridge_progress::bucket_for_position((int)floor($videotime), (int)ceil($duration));
        $watched = json_decode((string)$session->watchedmap, true);
        $watched = is_array($watched) ? array_map('intval', $watched) : [];
        $currentenough = abs($videotime - (float)$session->lastposition) <= 4.5;
        if ($bucket <= 0 || !in_array($bucket, $watched, true) || !$currentenough) {
            throw new moodle_exception('error:playbacknotverified', 'videoreactions');
        }

        if ((int)$activity->cooldown > 0) {
            $last = (int)$DB->get_field_sql(
                'SELECT MAX(timecreated) FROM {videoreactions_reaction} WHERE activityid = :activityid AND userid = :userid',
                ['activityid' => $activity->id, 'userid' => $userid]
            );
            if ($last && ($now - $last) < (int)$activity->cooldown) {
                throw new moodle_exception('error:cooldown', 'videoreactions');
            }
        }

        if ((int)$activity->maxperminute > 0) {
            $recent = $DB->count_records_select(
                'videoreactions_reaction',
                'activityid = :activityid AND userid = :userid AND timecreated >= :since',
                ['activityid' => $activity->id, 'userid' => $userid, 'since' => $now - 60]
            );
            if ($recent >= (int)$activity->maxperminute) {
                throw new moodle_exception('error:ratelimit', 'videoreactions');
            }
        }

        $groupid = self::user_group($cm, $userid);
        $record = (object)[
            'activityid' => $activity->id,
            'userid' => $userid,
            'groupid' => $groupid,
            'reaction' => $reaction,
            'videotime' => round($videotime, 3),
            'timebucket' => (int)(floor($videotime / self::REACTION_BUCKET_SECONDS) * self::REACTION_BUCKET_SECONDS),
            'timecreated' => $now,
        ];
        $record->id = $DB->insert_record('videoreactions_reaction', $record);

        $event = event\reaction_added::create([
            'objectid' => $record->id,
            'context' => $context,
            'userid' => $userid,
            'other' => [
                'activityid' => (int)$activity->id,
                'reaction' => $reaction,
                'videotime' => (float)$record->videotime,
            ],
        ]);
        $event->trigger();

        videoreactions_update_grades($activity, $userid, false);
        $completion = new \completion_info(get_course($activity->course));
        $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);

        return $record;
    }

    /**
     * Return aggregated timeline rows without loading individual reactions.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $cm Course module.
     * @param context_module $context Context.
     * @param int $userid User id.
     * @param string $mode mine, class or hidden.
     * @return array
     */
    public static function timeline(
        stdClass $activity,
        stdClass $cm,
        context_module $context,
        int $userid,
        string $mode
    ): array {
        global $DB;

        if ($mode === 'hidden') {
            return [];
        }

        $conditions = ['activityid = :activityid'];
        $params = ['activityid' => $activity->id];

        if ($mode === 'mine') {
            $conditions[] = 'userid = :userid';
            $params['userid'] = $userid;
        } else {
            require_capability('mod/videoreactions:viewclassreactions', $context);
            $groupid = self::view_group($cm, $userid, $context);
            if ($groupid !== 0) {
                $conditions[] = 'groupid = :groupid';
                $params['groupid'] = $groupid;
            }
        }

        $where = implode(' AND ', $conditions);
        $sql = "SELECT timebucket, reaction, COUNT(1) AS reactioncount
                  FROM {videoreactions_reaction}
                 WHERE {$where}
              GROUP BY timebucket, reaction
              ORDER BY timebucket ASC, reaction ASC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Build report data using aggregate queries.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $cm Course module.
     * @param context_module $context Context.
     * @param int $viewerid Viewer id.
     * @return array
     */
    public static function report_data(
        stdClass $activity,
        stdClass $cm,
        context_module $context,
        int $viewerid
    ): array {
        global $DB;

        $conditions = ['activityid = :activityid'];
        $params = ['activityid' => $activity->id];
        $groupid = self::view_group($cm, $viewerid, $context);
        if ($groupid !== 0) {
            $conditions[] = 'groupid = :groupid';
            $params['groupid'] = $groupid;
        }
        $where = implode(' AND ', $conditions);

        $total = (int)$DB->count_records_select('videoreactions_reaction', $where, $params);

        $types = array_values($DB->get_records_sql(
            "SELECT reaction, COUNT(1) AS reactioncount
               FROM {videoreactions_reaction}
              WHERE {$where}
           GROUP BY reaction
           ORDER BY reactioncount DESC",
            $params
        ));

        $moments = array_values($DB->get_records_sql(
            "SELECT timebucket, COUNT(1) AS reactioncount
               FROM {videoreactions_reaction}
              WHERE {$where}
           GROUP BY timebucket
           ORDER BY reactioncount DESC, timebucket ASC",
            $params,
            0,
            20
        ));

        $usercounts = array_values($DB->get_records_sql(
            "SELECT userid, COUNT(1) AS reactioncount
               FROM {videoreactions_reaction}
              WHERE {$where}
           GROUP BY userid
           ORDER BY reactioncount DESC",
            $params,
            0,
            20
        ));
        $userids = array_map(static fn($row): int => (int)$row->userid, $usercounts);
        $users = $userids ? $DB->get_records_list('user', 'id', $userids) : [];

        $density = array_values($DB->get_records_sql(
            "SELECT timebucket, COUNT(1) AS reactioncount
               FROM {videoreactions_reaction}
              WHERE {$where}
           GROUP BY timebucket
           ORDER BY timebucket ASC",
            $params
        ));

        $maxdensity = 1;
        foreach ($density as $row) {
            $maxdensity = max($maxdensity, (int)$row->reactioncount);
        }

        return [
            'total' => $total,
            'types' => array_map(static fn($row): array => [
                'reaction' => $row->reaction,
                'count' => (int)$row->reactioncount,
            ], $types),
            'moments' => array_map(static fn($row): array => [
                'time' => (int)$row->timebucket,
                'timeformatted' => self::format_time((int)$row->timebucket),
                'count' => (int)$row->reactioncount,
            ], $moments),
            'users' => array_map(static function($row) use ($users): array {
                $user = $users[(int)$row->userid] ?? null;
                return [
                    'userid' => (int)$row->userid,
                    'fullname' => $user ? fullname($user) : get_string('deleteduser', 'core'),
                    'count' => (int)$row->reactioncount,
                ];
            }, $usercounts),
            'density' => array_map(static fn($row): array => [
                'time' => (int)$row->timebucket,
                'timeformatted' => self::format_time((int)$row->timebucket),
                'count' => (int)$row->reactioncount,
                'height' => max(4, (int)round(((int)$row->reactioncount / $maxdensity) * 100)),
            ], $density),
        ];
    }

    /**
     * Count accepted reactions for a user.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @return int
     */
    public static function count_for_user(int $activityid, int $userid): int {
        global $DB;
        return $DB->count_records('videoreactions_reaction', [
            'activityid' => $activityid,
            'userid' => $userid,
        ]);
    }

    /**
     * Resolve one group id without doing per-reaction membership lookups.
     *
     * @param stdClass $cm Course module.
     * @param int $userid User id.
     * @return int
     */
    private static function user_group(stdClass $cm, int $userid): int {
        if ((int)$cm->groupmode === NOGROUPS) {
            return 0;
        }

        $groups = groups_get_all_groups($cm->course, $userid, $cm->groupingid, 'g.id');
        if (!$groups) {
            return 0;
        }

        $selected = groups_get_activity_group($cm, true);
        if ($selected > 0 && isset($groups[$selected])) {
            return (int)$selected;
        }
        return (int)array_key_first($groups);
    }

    /**
     * Resolve the group whose aggregate data may be viewed.
     *
     * A selected group is respected for teachers and visible-group navigation. Without a selection,
     * separate groups restrict users lacking accessallgroups to one of their memberships, while visible
     * groups may legitimately show all participants.
     *
     * @param stdClass $cm Course module.
     * @param int $userid Viewer id.
     * @param context_module $context Context.
     * @return int Group id, or zero for an all-participants aggregate.
     */
    private static function view_group(stdClass $cm, int $userid, context_module $context): int {
        if ((int)$cm->groupmode === NOGROUPS) {
            return 0;
        }

        $selected = groups_get_activity_group($cm, true);
        if ($selected > 0) {
            return (int)$selected;
        }

        if ((int)$cm->groupmode === SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
            $groups = groups_get_all_groups($cm->course, $userid, $cm->groupingid, 'g.id');
            return $groups ? (int)array_key_first($groups) : -1;
        }

        return 0;
    }

    /**
     * Format seconds.
     *
     * @param int $seconds Seconds.
     * @return string
     */
    public static function format_time(int $seconds): string {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;
        return $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $secs)
            : sprintf('%02d:%02d', $minutes, $secs);
    }
}
