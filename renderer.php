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

require_once($CFG->dirroot.'/mod/learningtimecheck/locallib.php');

class block_learningtimecheck_renderer extends plugin_renderer_base {

    public function userline($auser, $learningtimecheck, $cm) {
        global $COURSE, $OUTPUT;

        $str = '';

        $reporturl = new moodle_url('/mod/learningtimecheck/report.php', array('id' => $cm->id));
        $str .= '<div class="ltc-progress-line">';
        $str .= '<div class="ltc-progressbar">';
        $str .= '<div class="ltc-progressbar-outer">';
        $str .= learningtimecheck_class::print_user_progressbar($learningtimecheck->id, $auser->id, '100%', false, true);
        $str .= '</div>';
        $str .= '</div>';
        $str .= '<div class="ltc-userpicture">';
        $link = '<a href="'.$reporturl->out(true, array('studentid' => $auser->id)).'" >';
        $str .= $link.$OUTPUT->user_picture($auser, array('size' => 30, 'courseid' => $COURSE->id, 'class' => ''));
        $str .= '</a></div>';
        $str .= '<div class="ltc-userlink">'.$link.fullname($auser).'</a></div>';
        if ($auser->longtimenosee > 0) {
            $str .= '<div class="ltc-usernosee">';
            $title = get_string('notseenfor', 'block_learningtimecheck', $auser->longtimenosee);
            $pixurl = $OUTPUT->pix_url('lazy', 'block_learningtimecheck');
            $str .= '<img title="'.$title.'" width="32" height="32" src="'.$pixurl.'" />';
            $str .= '</div>';
        } else if ($auser->longtimenosee == -1) {
            $str .= '<div class="ltc-usernosee">';
            $title = get_string('neverseen', 'block_learningtimecheck', $auser->longtimenosee);
            $pixurl = $OUTPUT->pix_url('neverseen', 'block_learningtimecheck');
            $str .= '<img title="'.$title.'" width="32" height="32" src="'.$pixurl.'" />';
            $str .= '</div>';
        }
        $str .= '</div>';

        return $str;
    }
}