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
 *
 * @module     block_learningtimecheck
 * @package    blocks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// jshint unused: true, undef:true
define(['jquery', 'core/log'], function ($, log) {

    var blocklearningtimecheck = {

        init: function() {
            $('.ltc-name-filter').bind('change', this.submit_filter);
            log.debug("ADM Block Learningtimecheck initialized");
        },

        submit_filter: function() {
            var that = $(this);

            var cmid = that.attr('data-cmid');
            var formid = '#ltc-filterform-' + cmid;
            $(formid).submit();
        }
    };

    return blocklearningtimecheck;
});
