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
 * videoreactions.php
 *
 * @package   mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['availablereactions'] = 'Available reactions';
$string['availablereactions_help'] = 'Choose which reaction types learners can use while watching.';
$string['classreactions'] = 'Class reactions';
$string['completiondetail:reactions'] = 'Make at least {$a} accepted reactions';
$string['completionreactions'] = 'Minimum accepted reactions';
$string['cooldown'] = 'Cooldown between reactions';
$string['cooldown_help'] = 'Minimum number of seconds a learner must wait between accepted reactions.';
$string['count'] = 'Reactions';
$string['density'] = 'Timeline density';
$string['error:cooldown'] = 'Wait a little before reacting again.';
$string['error:invalidtime'] = 'The requested video time is invalid.';
$string['error:playbacknotverified'] = 'The reaction could not be verified against recent playback.';
$string['error:ratelimit'] = 'The reaction limit for this minute was reached.';
$string['error:reactiondisabled'] = 'This reaction is not enabled for the activity.';
$string['eventcoursemoduleviewed'] = 'Video Reactions activity viewed';
$string['eventreactionadded'] = 'Video reaction added';
$string['gradetarget'] = 'Reactions for full participation grade';
$string['gradetarget_help'] = 'When grading is enabled, this many accepted reactions earns the maximum grade.';
$string['gradingnote'] = 'Set the grade to None to disable gradebook participation grading.';
$string['hidereactions'] = 'Hide reactions';
$string['learner'] = 'Learner';
$string['maxperminute'] = 'Maximum reactions per minute';
$string['maxperminute_help'] = 'Server-side rate limit per learner. Use 0 for no per-minute limit.';
$string['modulename'] = 'Video Reactions';
$string['modulenameplural'] = 'Video Reactions';
$string['moment'] = 'Moment';
$string['myreactions'] = 'Only my reactions';
$string['noreactions'] = 'No reactions yet.';
$string['nosourceplugins'] = 'No Video Bridge source with reliable tracking is available.';
$string['openmoment'] = 'Open video at this moment';
$string['participationheader'] = 'Participation';
$string['pluginname'] = 'Video Reactions';
$string['privacy:metadata'] = 'Video Reactions stores learner reactions and playback verification data.';
$string['privacy:metadata:reaction'] = 'Stores reactions made by learners at exact video times.';
$string['privacy:metadata:reaction:reaction'] = 'The selected reaction.';
$string['privacy:metadata:reaction:timecreated'] = 'When the reaction was stored.';
$string['privacy:metadata:reaction:userid'] = 'The learner who made the reaction.';
$string['privacy:metadata:reaction:videotime'] = 'The video position associated with the reaction.';
$string['privacy:metadata:session'] = 'Stores short-lived playback verification state.';
$string['privacy:metadata:session:lastposition'] = 'The most recently verified video position.';
$string['privacy:metadata:session:userid'] = 'The learner whose playback is being verified.';
$string['privacy:metadata:session:watchedmap'] = 'A normalized map of recently observed playback buckets.';
$string['react'] = 'React';
$string['reactiondistribution'] = 'Reactions by type';
$string['reactionrequired'] = 'Select at least one reaction.';
$string['reactionsheader'] = 'Reactions';
$string['report'] = 'Reaction report';
$string['resetreactions'] = 'Delete learner reactions and playback verification state';
$string['resetreactionsstatus'] = 'Deleted Video Reactions user data';
$string['seconds'] = '{$a} seconds';
$string['showanimations'] = 'Show subtle dense-moment animations';
$string['showanimations_help'] = 'When playback crosses a moment with many reactions, show a small restrained reaction hint.';
$string['sourceheader'] = 'Video source';
$string['timeline'] = 'Reaction timeline';
$string['topmoments'] = 'Most reacted moments';
$string['topstudents'] = 'Most active learners';
$string['totalreactions'] = 'Total reactions';
$string['unlimited'] = 'Unlimited';
$string['videoreactionsname'] = 'Activity name';
$string['videosource'] = 'Video source';
$string['viewreport'] = 'View reaction report';
