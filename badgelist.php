<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once($CFG->libdir . '/badgeslib.php');

require_login();

$reload = optional_param('reload', false, PARAM_BOOL);

$PAGE->set_context(context_system::instance()); // TODO: context?
$PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . get_string('obfbadgelist', 'local_obf'));
$PAGE->set_heading(get_string('obfbadgelisttitle', 'local_obf'));
$PAGE->set_url(new moodle_url('/local/obf/badgelist.php'));
$PAGE->set_pagelayout('admin');

if (!has_capability('local/obf:viewallbadges', $PAGE->context)) {
    print_error('capabilityrequired', 'local_obf');
}

$output = $PAGE->get_renderer('local_obf');

echo $OUTPUT->header();

try {
    $badges = obf_get_badge_tree($reload);
    echo $OUTPUT->single_button(new moodle_url('badgelist.php', array('reload' => 1)), get_string('obfupdatebadges', 'local_obf'));
    echo $output->render($badges);
} catch (Exception $e) {
    echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
}

echo $OUTPUT->footer();
