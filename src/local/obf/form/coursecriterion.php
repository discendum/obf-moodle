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
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

class obf_coursecriterion_form extends local_obf_form_base {
    private $criteriatype;
    private $courseid;
    private $course;
    private $criterioncourse;

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $this->criterioncourse = $this->_customdata['criterioncourse'];
        if ($this->criterioncourse->exists()) {
            $this->criteriatype = $this->criterioncourse->get_criteriatype();
        } else {
            $this->criteriatype = array_key_exists('criteriatype', $this->_customdata) ?
                    $this->_customdata['criteriatype'] : $this->criterioncourse->get_criteriatype();
        }

        $this->criterioncourse->get_options($mform, $this);
        $this->criterioncourse->get_form_config($mform, $this);

        $this->criterioncourse->get_form_after_save_options($mform, $this);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                        get_string('savechanges'));

        if ($this->criterioncourse->exists()) {
            $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton',
                            get_string('deletecriterion', 'local_obf'));
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
