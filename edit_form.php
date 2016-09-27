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

defined('MOODLE_INTERNAL') || die();

/**
 * @package    block_learningtimecheck
 * @category   blocks
 * @copyright  Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_learningtimecheck_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $COURSE;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $options = array();
        $learningtimechecks = $DB->get_records('learningtimecheck', array('course' => $COURSE->id));
        foreach ($learningtimechecks as $learningtimecheck) {
            $options[$learningtimecheck->id] = s($learningtimecheck->name);
        }
        $mform->addElement('select', 'config_learningtimecheckid', get_string('chooselearningtimecheck', 'block_learningtimecheck'), $options);

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

    function set_data($defaults) {
        parent::set_data($defaults);
    }
}
