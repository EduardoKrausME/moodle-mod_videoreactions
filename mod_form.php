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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity settings form.
 *
 * @package mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videoreactions_mod_form extends moodleform_mod {
    /**
     * Define the form.
     */
    public function definition(): void {
        $mform = $this->_form;
        $manager = new source_manager();
        $sources = $manager->get_options(['tracking']);
        if (!$sources) {
            throw new moodle_exception('nosourceplugins', 'videoreactions');
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videoreactionsname', 'videoreactions'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'sourceheader', get_string('sourceheader', 'videoreactions'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'videoreactions'), $sources);
        $mform->setType('videosource', PARAM_PLUGIN);
        $mform->setDefault('videosource', $manager->get_default_source());
        $manager->add_form_elements($mform, 'videosource');

        $mform->addElement('header', 'reactionsheader', get_string('reactionsheader', 'videoreactions'));
        $selector = $mform->addElement(
            'select',
            'reactionlist',
            get_string('availablereactions', 'videoreactions'),
            self::reaction_options(),
            ['size' => 6]
        );
        $selector->setMultiple(true);
        $mform->setDefault('reactionlist', array_keys(self::reaction_options()));
        $mform->addHelpButton('reactionlist', 'availablereactions', 'videoreactions');

        $cooldowns = [];
        foreach ([0, 1, 2, 3, 5, 10, 15] as $seconds) {
            $cooldowns[$seconds] = $seconds === 0
                ? get_string('unlimited', 'videoreactions')
                : get_string('seconds', 'videoreactions', $seconds);
        }
        $mform->addElement('select', 'cooldown', get_string('cooldown', 'videoreactions'), $cooldowns);
        $mform->setDefault('cooldown', 2);
        $mform->addHelpButton('cooldown', 'cooldown', 'videoreactions');

        $mform->addElement('text', 'maxperminute', get_string('maxperminute', 'videoreactions'), ['size' => 5]);
        $mform->setType('maxperminute', PARAM_INT);
        $mform->setDefault('maxperminute', 10);
        $mform->addHelpButton('maxperminute', 'maxperminute', 'videoreactions');

        $mform->addElement('selectyesno', 'showanimations', get_string('showanimations', 'videoreactions'));
        $mform->setDefault('showanimations', 1);
        $mform->addHelpButton('showanimations', 'showanimations', 'videoreactions');

        $mform->addElement('header', 'participationheader', get_string('participationheader', 'videoreactions'));
        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', 0);
        $mform->addElement('static', 'gradingnote', '', get_string('gradingnote', 'videoreactions'));
        $mform->addElement('text', 'gradetarget', get_string('gradetarget', 'videoreactions'), ['size' => 5]);
        $mform->setType('gradetarget', PARAM_INT);
        $mform->setDefault('gradetarget', 5);
        $mform->addHelpButton('gradetarget', 'gradetarget', 'videoreactions');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Validate activity data.
     *
     * @param array $data Submitted values.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $manager = new source_manager();
        $errors += $manager->validation($data, $files);

        $selected = array_values(array_intersect(
            array_map('strval', (array)($data['reactionlist'] ?? [])),
            array_keys(self::reaction_options())
        ));
        if (!$selected) {
            $errors['reactionlist'] = get_string('reactionrequired', 'videoreactions');
        }
        if ((int)($data['maxperminute'] ?? 0) < 0 || (int)($data['maxperminute'] ?? 0) > 120) {
            $errors['maxperminute'] = get_string('invaliddata', 'error');
        }
        if ((int)($data['gradetarget'] ?? 0) < 1) {
            $errors['gradetarget'] = get_string('invaliddata', 'error');
        }

        $completionfield = $this->completion_field();
        if (isset($data[$completionfield]) && ((int)$data[$completionfield] < 0 || (int)$data[$completionfield] > 100000)) {
            $errors[$completionfield] = get_string('invaliddata', 'error');
        }
        return $errors;
    }

    /**
     * Prepare stored values for the form.
     *
     * @param array $defaultvalues Default values.
     */
    public function data_preprocessing(&$defaultvalues): void {
        $defaultvalues['reactionlist'] = json_decode((string)($defaultvalues['reactions'] ?? '[]'), true)
            ?: array_keys(self::reaction_options());
        if (array_key_exists('completionreactions', $defaultvalues)) {
            $defaultvalues[$this->completion_field()] = $defaultvalues['completionreactions'];
        }
        if (!empty($this->current->instance)) {
            (new source_manager())->prepare_form_data($defaultvalues, $this->context);
        }
    }

    /**
     * Add custom completion rule.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $field = $this->completion_field();
        $this->_form->addElement('text', $field, get_string('completionreactions', 'videoreactions'), ['size' => 5]);
        $this->_form->setType($field, PARAM_INT);
        $this->_form->setDefault($field, 0);
        return [$field];
    }

    /**
     * Whether custom completion is enabled.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data[$this->completion_field()]);
    }

    /**
     * Return normalized submitted data.
     *
     * @return stdClass|false
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }

        $allowed = array_keys(self::reaction_options());
        $selected = array_values(array_intersect(array_map('strval', (array)$data->reactionlist), $allowed));
        $data->reactions = json_encode($selected, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        unset($data->reactionlist);

        $field = $this->completion_field();
        if (property_exists($data, $field)) {
            $data->completionreactions = (int)$data->{$field};
            unset($data->{$field});
        }
        return $data;
    }

    /**
     * Reaction choices.
     *
     * @return array
     */
    private static function reaction_options(): array {
        return [
            '👏' => '👏',
            '❤️' => '❤️',
            '😂' => '😂',
            '😮' => '😮',
            '🤔' => '🤔',
            '❓' => '❓',
        ];
    }

    /**
     * Completion field name.
     *
     * @return string
     */
    private function completion_field(): string {
        return 'completionreactions_videoreactions';
    }
}
