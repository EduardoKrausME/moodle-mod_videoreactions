<?php
// This file is part of Moodle - http://moodle.org/.

use local_video_bridge\source\manager as source_manager;
use mod_videoreactions\event\course_module_viewed;
use mod_videoreactions\reaction_manager;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$starttime = optional_param('time', 0, PARAM_FLOAT);

$cm = get_coursemodule_from_id('videoreactions', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videoreactions', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videoreactions:view', $context);

$PAGE->set_url('/mod/videoreactions/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_activity_record($activity);

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}

$event = course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videoreactions', $activity);
$event->trigger();

$manager = new source_manager();
$player = $manager->get_player_config($activity, $context);
$playerclient = $player;
unset($playerclient['sourcetemplate']);
$player['sourcehtml'] = $OUTPUT->render_from_template($player['sourcetemplate'], ['player' => $player]);

$canreact = has_capability('mod/videoreactions:react', $context);
$canviewclass = has_capability('mod/videoreactions:viewclassreactions', $context);
$reactions = array_map(
    static fn(string $reaction): array => ['value' => $reaction, 'label' => $reaction],
    reaction_manager::enabled_reactions($activity)
);

$config = [
    'cmid' => (int)$cm->id,
    'starttime' => max(0.0, (float)$starttime),
    'showanimations' => !empty($activity->showanimations),
    'canreact' => $canreact,
    'canviewclass' => $canviewclass,
    'player' => $playerclient,
];

$templatedata = [
    'name' => format_string($activity->name),
    'intro' => trim((string)$activity->intro) !== ''
        ? format_module_intro('videoreactions', $activity, $cm->id, false)
        : '',
    'hasintro' => trim((string)$activity->intro) !== '',
    'player' => $player,
    'reactions' => $reactions,
    'canreact' => $canreact,
    'canviewclass' => $canviewclass,
    'configjson' => json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    'canviewreport' => has_capability('mod/videoreactions:viewreport', $context),
    'reporturl' => (new moodle_url('/mod/videoreactions/report.php', ['id' => $cm->id]))->out(false),
];

$PAGE->requires->js_call_amd('mod_videoreactions/reactions', 'init');

echo $OUTPUT->header();
if ((int)$cm->groupmode !== NOGROUPS && $canviewclass) {
    groups_print_activity_menu($cm, $PAGE->url);
}
echo $OUTPUT->render_from_template('mod_videoreactions/view', $templatedata);
echo $OUTPUT->footer();
