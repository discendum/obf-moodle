<?php
require_once($CFG->libdir . '/formslib.php');

abstract class obfform extends moodleform {

    public function render() {
        ob_start();
        $this->display();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    public function setExpanded(&$mform, $header) {
        // Moodle 2.2 doesn't have setExpanded
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($header);
        }
    }

}
