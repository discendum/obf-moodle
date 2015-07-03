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
class obf_issue_event {

    private $id = -1;
    private $eventid = null;
    private $userid = -1;
    private $criterionid = -1;

    /**
     * Returns the issue event by id.
     *
     * @param integer $eventid Event id.
     * @param moodle_database $db The db instance.
     * @return \self|null Returns this object on success, null otherwise.
     */
    public function __construct($eventid = null, moodle_database $db = null) {
        if (!is_null($eventid) && !is_null($db)) {
            $record = $db->get_record('local_obf_issue_events',
                    array('event_id' => $eventid));

            if ($record !== false) {
                $this->set_id($record->id)->set_eventid($record->event_id);
                $this->set_criterionid($record->obf_criterion_id)->set_userid($record->user_id);
            } else {
                $this->set_eventid($eventid);
            }
        }
    }

    public function populate_from_record($record) {
        if ($record !== false) {
            $this->set_id($record->id)->set_eventid($record->event_id);
            $this->set_criterionid($record->obf_criterion_id)->set_userid($record->user_id);
        }
        return $this;
    }

    public static function get_events_in_course($courseid, moodle_database $db) {
        $ret = array();
        $sql = 'SELECT evt.* FROM {local_obf_issue_events} AS evt ' .
                'LEFT JOIN {local_obf_criterion_courses} AS cc ' .
                'ON (evt.obf_criterion_id=cc.obf_criterion_id) ' .
                'WHERE cc.courseid = (?) AND evt.obf_criterion_id IS NOT NULL';
        $params = array($courseid);
        $records = $db->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $obj = new self();
            $ret[] = $obj->populate_from_record($record);
        }
        return $ret;
    }

    /**
     * Saves this email instance.
     *
     * @param moodle_database $db
     */
    public function save(moodle_database $db) {
        $obj = new stdClass();
        $obj->user_id = $this->userid;
        $obj->obf_criterion_id = $this->criterionid;
        $obj->event_id = $this->eventid;

        if ($this->id > 0) {
            $obj->id = $this->id;
            $db->update_record('local_obf_issue_events', $obj);
        } else {
            $db->insert_record('local_obf_issue_events', $obj);
        }
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function get_eventid() {
        return $this->eventid;
    }

    public function set_eventid($eventid) {
        $this->eventid = $eventid;
        return $this;
    }

    public function get_userid() {
        return $this->userid;
    }

    public function set_userid($userid) {
        if (!empty($userid) && $userid > 0) {
            $this->criterionid = null;
        }
        $this->userid = $userid;
        return $this;
    }

    public function has_userid() {
        return !empty($this->userid) && $this->userid > 0;
    }

    public function get_criterionid() {
        return $this->criterionid;
    }

    public function set_criterionid($criterionid) {
        if (!empty($criterionid) && $criterionid > 0) {
            $this->userid = null;
        }
        $this->criterionid = $criterionid;
        return $this;
    }

    public function has_criterionid() {
        return !empty($this->criterionid) && $this->criterionid > 0;
    }

}
