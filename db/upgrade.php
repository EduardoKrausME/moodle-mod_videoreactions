<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

/**
 * Upgrade steps for Video Reactions.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_videoreactions_upgrade(int $oldversion): bool {
    return true;
}
