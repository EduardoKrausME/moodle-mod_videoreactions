<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_videoreactions_track_watch' => [
        'classname' => 'mod_videoreactions\\external\\track_watch',
        'description' => 'Record a short-lived playback verification heartbeat.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'mod_videoreactions_save_reaction' => [
        'classname' => 'mod_videoreactions\\external\\save_reaction',
        'description' => 'Save a timestamped video reaction.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'mod_videoreactions_get_timeline' => [
        'classname' => 'mod_videoreactions\\external\\get_timeline',
        'description' => 'Return an aggregated reaction timeline.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
