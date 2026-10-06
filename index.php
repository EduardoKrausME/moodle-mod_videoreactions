<?php
// This file is part of Moodle - http://moodle.org/.

require('../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);

$PAGE->set_url('/mod/videoreactions/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'videoreactions'));
$PAGE->set_heading(format_string($course->fullname));

$instances = get_all_instances_in_course('videoreactions', $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'videoreactions'));

if (!$instances) {
    echo $OUTPUT->notification(get_string('thereareno', 'moodle', get_string('modulenameplural', 'videoreactions')), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [get_string('name'), get_string('description')];
foreach ($instances as $instance) {
    $url = new moodle_url('/mod/videoreactions/view.php', ['id' => $instance->coursemodule]);
    $table->data[] = [
        html_writer::link($url, format_string($instance->name)),
        format_text($instance->intro, $instance->introformat, ['context' => context_module::instance($instance->coursemodule)]),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
