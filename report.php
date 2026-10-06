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

/**
 * report.php
 *
 * @package   mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoreactions\reaction_manager;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('videoreactions', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('videoreactions', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videoreactions:viewreport', $context);

$PAGE->set_url('/mod/videoreactions/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('report', 'videoreactions'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$report = reaction_manager::report_data($activity, $cm, $context, (int)$USER->id);
$viewurl = new moodle_url('/mod/videoreactions/view.php', ['id' => $cm->id]);

foreach ($report['moments'] as &$moment) {
    $url = new moodle_url($viewurl, ['time' => $moment['time']]);
    $url->set_anchor('videoreactions-player');
    $moment['url'] = $url->out(false);
}
unset($moment);

foreach ($report['density'] as &$point) {
    $url = new moodle_url($viewurl, ['time' => $point['time']]);
    $url->set_anchor('videoreactions-player');
    $point['url'] = $url->out(false);
}
unset($point);

$data = [
    'name' => format_string($activity->name),
    'total' => $report['total'],
    'types' => $report['types'],
    'hastypes' => (bool)$report['types'],
    'moments' => $report['moments'],
    'hasmoments' => (bool)$report['moments'],
    'users' => $report['users'],
    'hasusers' => (bool)$report['users'],
    'density' => $report['density'],
    'hasdensity' => (bool)$report['density'],
    'viewurl' => $viewurl->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'videoreactions') . ': ' . format_string($activity->name));
groups_print_activity_menu($cm, $PAGE->url);
echo $OUTPUT->render_from_template('mod_videoreactions/report', $data);
echo $OUTPUT->footer();
