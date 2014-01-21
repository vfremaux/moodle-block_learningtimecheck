<?php

class block_learningtimecheck_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $DB, $COURSE;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $options = array();
        $learningtimechecks = $DB->get_records('learningtimecheck', array('course'=>$COURSE->id));
        foreach ($learningtimechecks as $learningtimecheck) {
            $options[$learningtimecheck->id] = s($learningtimecheck->name);
        }
        $mform->addElement('select', 'config_learningtimecheckid', get_string('chooselearningtimecheck', 'block_learningtimecheck'), $options);

        $options = array(0 => get_string('allparticipants'));
        $groups = $DB->get_records('groups', array('courseid'=>$COURSE->id));
        foreach ($groups as $group) {
            $options[$group->id] = s($group->name);
        }
        $mform->addElement('select', 'config_groupid', get_string('choosegroup', 'block_learningtimecheck'), $options);
    }

    function set_data($defaults) {
        parent::set_data($defaults);
    }
}
