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

class block_learningtimecheck extends block_base {

    public function init() {
        $this->title = get_string('title', 'block_learningtimecheck');
    }

    public function specialization() {
        if (!empty($this->config) && !empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_config() {
        return true;
    }

    public function applicable_formats() {
        return array('course' => true, 'course-category' => false, 'site' => true);
    }

    public function get_content() {
        global $CFG, $USER, $DB, $PAGE, $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->text = '';

        if (!isloggedin()) {
            return $this->content;
        }

        $renderer = $PAGE->get_renderer('block_learningtimecheck');

        if (empty($this->config->learningtimecheckid)) {
            $this->content->text .= $OUTPUT->notification(get_string('nolearningtimecheck', 'block_learningtimecheck'));
            return $this->content;
        }

        if (!$learningtimecheck = $DB->get_record('learningtimecheck', array('id' => $this->config->learningtimecheckid))) {
            $this->content->text .= get_string('nolearningtimecheck', 'block_learningtimecheck');
            return $this->content;
        }

        if (!$cm = get_coursemodule_from_instance('learningtimecheck', $learningtimecheck->id, $learningtimecheck->course)) {
            $this->content->items = array('Error - course module not found');
            return $this->content;
        }
        $context = context_module::instance($cm->id);

        $viewallreports = has_capability('mod/learningtimecheck:viewreports', $context);
        $viewmenteereports = has_capability('mod/learningtimecheck:viewmenteereports', $context);

        if ($viewallreports || $viewmenteereports) {
            $orderby = 'ORDER BY firstname ASC';
            $ausers = false;

            $showgroup = 0;

            // Add the groups selector to the footer.

            $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',picture,imagealt,email';
            $cap = 'mod/learningtimecheck:updateown';
            if ($COURSE->groupmode != NOGROUPS) {
                $this->content->footer = $this->get_groups_menu($cm);
                $showgroup = $this->get_selected_group($cm);
                $users = get_users_by_capability($context, $cap, $fields, '', '', '', $showgroup, '', false);
            } else {
                $users = get_users_by_capability($context, $cap, $fields, '', '', '', 0, '', false);
            }

            if ($users) {
                $users = array_keys($users);
                if (!$viewallreports) { // Can only see reports for their mentees.
                    $users = learningtimecheck_class::filter_mentee_users($users);
                }
                if (!empty($users)) {
                    $sql = '
                        SELECT
                            u.id,
                            '.get_all_user_name_fields(true, 'u').',
                            picture,
                            imagealt,
                            email
                        FROM
                            {user} u
                        WHERE
                            u.id
                        IN
                            ('.implode(',', $users).')
                    '.$orderby;
                    $ausers = $DB->get_records_sql($sql);
                }
            }

            if ($ausers) {
                $this->content->text .= '<div class="ltc-progress-table">';
                foreach ($ausers as $auser) {

                    $auser->longtimenosee = false;
                    if (!empty($this->config->longtimenosee)) {
                        if ($maxlogstamp = $this->get_last_log_in_course($learningtimecheck->course, $auser->id)) {
                            if ($maxlogstamp < (time() - $this->config->longtimenosee * 7 * DAYSECS)) {
                                $auser->longtimenosee = $this->config->longtimenosee;
                            }
                        } else {
                            // Never seen.
                            $auser->longtimenosee = -1;
                        }
                    }

                    $this->content->text .= $renderer->userline($auser, $learningtimecheck, $cm);
                }
                $this->content->text .= '</div>';
            } else {
                $this->content->text = get_string('nousers', 'block_learningtimecheck');
            }
        } else {
            $viewurl = new moodle_url('/mod/learningtimecheck/view.php', array('id' => $cm->id));
            $USER->longtimenosee = 0;
            $this->content->text .= $renderer->userline($USER, $learningtimecheck, $cm);
        }

        return $this->content;
    }

    public function import_learningtimecheck_plugin() {
        global $CFG, $DB;

        if (!file_exists($CFG->dirroot.'/mod/learningtimecheck/locallib.php')) {
            return false;
        }

        require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');
        return true;
    }

    public function get_groups_menu($cm) {
        global $COURSE, $OUTPUT, $USER;

        if (!$groupmode = groups_get_activity_groupmode($cm)) {
            $this->get_selected_group($cm, null, true, true); // Make sure all users can be seen.
            return '';
        }

        $context = context_module::instance($cm->id);
        $aag = has_capability('moodle/site:accessallgroups', $context);

        if ($groupmode == VISIBLEGROUPS or $aag) {
            $seeall = true;
            $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid); // Any group in grouping.
        } else {
            $seeall = false;
            $allowedgroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid); // Only assigned groups.
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

    public function get_selected_group($cm, $allowedgroups = null, $seeall = false, $forceall = false) {
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
                // No groups defined, but we can see all groups - return 0 => all users.
                $SESSION->learningtimecheckgroup[$cm->id] = 0;
            } else {
                // No groups defined and we can't access groups outside out own - return -1 => no users.
                $SESSION->learningtimecheckgroup[$cm->id] = -1;
            }
        }

        return $SESSION->learningtimecheckgroup[$cm->id];
    }

    public function get_last_log_in_course($courseid, $userid) {
        global $DB;

        $logmanager = get_log_manager();
        $readers = $logmanager->get_readers('\core\log\sql_select_reader');
        $reader = reset($readers);

        if (empty($reader)) {
            return false; // No log reader found.
        }

        $logupdate = 0;

        if ($reader instanceof \logstore_standard\log\store) {
            $select = "courseid = ? AND userid = ? ";
            $maxlogstamp = $DB->get_field_select('logstore_standard_log', 'MAX(timecreated)', $select, array($courseid, $userid));
        } else if ($reader instanceof \logstore_legacy\log\store) {
            $select = "course = ? AND userid = ? ";
            $maxlogstamp = $DB->get_field_select('log', 'MAX(timecreated)', $select, array($courseid, $userid));
        } else {
            return false;
        }

        return $maxlogstamp;
    }
}
