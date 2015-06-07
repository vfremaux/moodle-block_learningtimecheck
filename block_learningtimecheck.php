<?php

defined('MOODLE_INTERNAL') || die();

class block_learningtimecheck extends block_list {
    function init() {
        $this->title = get_string('learningtimecheck','block_learningtimecheck');
    }

    function instance_allow_multiple() {
        return true;
    }

    function has_config() {
        return false;
    }

    function instance_allow_config() {
        return true;
    }

    function applicable_formats() {
        return array('course' => true, 'course-category' => false, 'site' => true);
    }

    function specialization() {
        global $DB;

        if (!empty($this->config->learningtimecheckid)) {
            $learningtimecheck = $DB->get_record('learningtimecheck', array('id'=>$this->config->learningtimecheckid));
            if ($learningtimecheck) {
                $this->title = s($learningtimecheck->name);
            }
        }
    }

    function get_content() {
        global $CFG, $USER, $DB;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->icons = array();

        if (empty($this->config->learningtimecheckid)) {
            $this->content->items = array(get_string('nolearningtimecheck','block_learningtimecheck'));
            return $this->content;
        }

        if (!$learningtimecheck = $DB->get_record('learningtimecheck',array('id'=>$this->config->learningtimecheckid))) {
            $this->content->items = array(get_string('nolearningtimecheck', 'block_learningtimecheck'));
            return $this->content;
        }

        if (!$cm = get_coursemodule_from_instance('learningtimecheck', $learningtimecheck->id, $learningtimecheck->course)) {
            $this->content->items = array('Error - course module not found');
            return $this->content;
        }
        if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        } else {
            $context = context_module::instance($cm->id);
        }

        $viewallreports = has_capability('mod/learningtimecheck:viewreports', $context);
        $viewmenteereports = has_capability('mod/learningtimecheck:viewmenteereports', $context);

        if ($viewallreports || $viewmenteereports) {
            $orderby = 'ORDER BY firstname ASC';
            $ausers = false;

            // Add the groups selector to the footer.
            $this->content->footer = $this->get_groups_menu($cm);
            $showgroup = $this->get_selected_group($cm);

            if ($users = get_users_by_capability($context, 'mod/learningtimecheck:updateown', 'u.id,'.get_all_user_name_fields(true, 'u'), '', '', '', $showgroup, '', false)) {
                $users = array_keys($users);
                if (!$viewallreports) { // can only see reports for their mentees
                    $users = learningtimecheck_class::filter_mentee_users($users);
                }
                if (!empty($users)) {
                    $ausers = $DB->get_records_sql('SELECT u.id,'.get_all_user_name_fields(true, 'u').' FROM {user} u WHERE u.id IN ('.implode(',',$users).') '.$orderby);
                }
            }

            if ($ausers) {
                $this->content->items = array();
                $reporturl = new moodle_url('/mod/learningtimecheck/report.php', array('id'=>$cm->id));
                foreach ($ausers as $auser) {
                    $link = '<a href="'.$reporturl->out(true, array('studentid'=>$auser->id)).'" >&nbsp;';
                    $this->content->items[] = $link.fullname($auser).learningtimecheck_class::print_user_progressbar($learningtimecheck->id, $auser->id, '50px', false, true).'</a>';
                }
            } else {
                $this->content->items = array(get_string('nousers','block_learningtimecheck'));
            }

        } else {
            $viewurl = new moodle_url('/mod/learningtimecheck/view.php', array('id'=>$cm->id));
            $link = '<a href="'.$viewurl.'" >&nbsp;';
            $this->content->items = array($link.learningtimecheck_class::print_user_progressbar($learningtimecheck->id, $USER->id, '150px', false, true).'</a>');
        }

        return $this->content;
    }

    function import_learningtimecheck_plugin() {
        global $CFG, $DB;

        if (!file_exists($CFG->dirroot.'/mod/learningtimecheck/locallib.php')) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');
        return true;
    }

    function get_groups_menu($cm) {
        global $COURSE, $OUTPUT, $USER;

        if (!$groupmode = groups_get_activity_groupmode($cm)) {
            $this->get_selected_group($cm, null, true, true); // Make sure all users can be seen
            return '';
        }

        $context = context_module::instance($cm->id);
        $aag = has_capability('moodle/site:accessallgroups', $context);

        if ($groupmode == VISIBLEGROUPS or $aag) {
            $seeall = true;
            $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid); // any group in grouping
        } else {
            $seeall = false;
            $allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid); // only assigned groups
        }

        $selected = $this->get_selected_group($cm, $allowedgroups, $seeall);

        $groupsmenu = array();
        if (empty($allowedgroups) || $seeall) {
            $groupsmenu[0] = get_string('allparticipants');
        }
        if ($allowedgroups) {
            foreach ($allowedgroups as $group) {
                $groupsmenu[$group->id] = format_string($group->name);
            }
        }

        $baseurl = new moodle_url('/course/view.php', array('id' => $COURSE->id));
        if (count($groupsmenu) <= 1) {
            return '';
        }

        $select = new single_select($baseurl, 'group', $groupsmenu, $selected, null, 'selectgroup');
        $out = $OUTPUT->render($select);
        return html_writer::tag('div', $out, array('class' => 'groupselector'));
    }

    function get_selected_group($cm, $allowedgroups = null, $seeall = false, $forceall = false) {
        global $SESSION;

        if (!is_null($allowedgroups)) {
            if (!isset($SESSION->learningtimecheckgroup)) {
                $SESSION->learningtimecheckgroup = array();
            }
            $change = optional_param('group', -1, PARAM_INT);
            if ($change != -1) {
                $SESSION->learningtimecheckgroup[$cm->id] = $change;
            } else if (!isset($SESSION->learningtimecheckgroup[$cm->id])) {
                if (isset($this->config->groupid)) {
                    $SESSION->learningtimecheckgroup[$cm->id] = $this->config->groupid;
                } else {
                    $SESSION->learningtimecheckgroup[$cm->id] = 0;
                }
            }
            $groupok = (($SESSION->learningtimecheckgroup[$cm->id] == 0) && $seeall);
            $groupok = $groupok || array_key_exists($SESSION->learningtimecheckgroup[$cm->id], $allowedgroups);
            if (!$groupok) {
                $group = reset($allowedgroups);
                if ($group === false) {
                    unset($SESSION->learningtimecheckgroup[$cm->id]);
                } else {
                    $SESSION->learningtimecheckgroup[$cm->id] = $group->id;
                }
            }
        }
        if ($forceall || !isset($SESSION->learningtimecheckgroup[$cm->id])) {
            if ($seeall) {
                // No groups defined, but we can see all groups - return 0 => all users
                $SESSION->learningtimecheckgroup[$cm->id] = 0;
            } else {
                // No groups defined and we can't access groups outside out own - return -1 => no users
                $SESSION->learningtimecheckgroup[$cm->id] = -1;
            }
        }

        return $SESSION->learningtimecheckgroup[$cm->id];
    }
}
