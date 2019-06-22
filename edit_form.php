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
 * @package    block_learningtimecheck
 * @category   blocks
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_learningtimecheck_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $COURSE;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_learningtimecheck'));
        $mform->setType('config_title', PARAM_MULTILANG);

        $options = array();
        $learningtimechecks = $DB->get_records('learningtimecheck', array('course' => $COURSE->id));
        foreach ($learningtimechecks as $learningtimecheck) {
            $options[$learningtimecheck->id] = s($learningtimecheck->name);
        }
        $label = get_string('chooselearningtimecheck', 'block_learningtimecheck');
        $mform->addElement('select', 'config_learningtimecheckid', $label, $options);

        $pgoptions = [
            PROGRESSBAR_ITEMS => get_string('items', 'block_learningtimecheck'),
            PROGRESSBAR_TIME => get_string('time', 'block_learningtimecheck'),
            PROGRESSBAR_BOTH => get_string('both', 'block_learningtimecheck'),
        ];

        $label = get_string('progressbars', 'block_learningtimecheck');
        $mform->addElement('select', 'config_progressbars', $label, $pgoptions);

        $label = get_string('mandatories', 'block_learningtimecheck');
        $mform->addElement('advcheckbox', 'config_mandatories', $label);
        $mform->setDefault('config_mandatories', 1);

        $label = get_string('optionals', 'block_learningtimecheck');
        $mform->addElement('advcheckbox', 'config_optionals', $label);
        $mform->setDefault('config_optionals', 0);

        $label = get_string('all', 'block_learningtimecheck');
        $mform->addElement('advcheckbox', 'config_all', $label);
        $mform->setDefault('config_all', 1);

        $mform->addElement('header', 'teacherhdr', get_string('teacherhdr', 'block_learningtimecheck'));

        $options = array(0 => get_string('allparticipants'));
        $groups = $DB->get_records('groups', array('courseid' => $COURSE->id));
        foreach ($groups as $group) {
            $options[$group->id] = s($group->name);
        }
        $mform->addElement('select', 'config_groupid', get_string('choosegroup', 'block_learningtimecheck'), $options);

        $noseeoptions = array('0' => get_string('nosignal', 'block_learningtimecheck'),
            '1' => get_string('oneweeknosee', 'block_learningtimecheck'),
            '2' => get_string('twoweeksnosee', 'block_learningtimecheck'),
            '3' => get_string('threeweeksnosee', 'block_learningtimecheck'),
            '4' => get_string('onemonthnosee', 'block_learningtimecheck'));
        $mform->addElement('select', 'config_longtimenosee', get_string('longtimenosee', 'block_learningtimecheck'), $noseeoptions);
    }

    public function set_data($defaults, &$files = null) {

        if (!$this->block->user_can_edit() && !empty($this->block->config->title)) {
            // If a title has been set but the user cannot edit it format it nicely.
            $title = $this->block->config->title;
            $defaults->config_title = format_string($title, true, $this->page->context);
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->title);
        }

        parent::set_data($defaults);
        if (isset($title)) {
            // Reset the preserved title.
            $this->block->config->title = $title;
        }
    }
}
