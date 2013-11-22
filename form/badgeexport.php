<?php
require_once($CFG->libdir . '/formslib.php');

class obf_badge_export_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $badges = $this->_customdata['badges'];

        $mform->addElement('header', 'header_badgeselect', get_string('selectbadgestoexport', 'local_obf'));

        foreach ($badges as $badge) {
            $label = print_badge_image($badge, $badge->get_context()) . ' ' . s($badge->name);
            $mform->addElement('checkbox', 'toexport[' . $badge->id . ']', '', $label);
        }

        $mform->addElement('header', 'header_disablebadges', get_string('exportextrasettings', 'local_obf'));
        $mform->addElement('advcheckbox', 'makedrafts', '', get_string('makeexporteddrafts', 'local_obf'));
        $mform->addElement('advcheckbox', 'disablemoodlebadges', '', get_string('disablemoodlebadges', 'local_obf'));
        $mform->setDefault('makedrafts', true);
        $mform->setDefault('disablemoodlebadges', true);

        $this->add_action_buttons(false, get_string('saveconfiguration', 'local_obf'));
    }
}
