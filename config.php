<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/form/config.php');
require_once(__DIR__ . '/form/badgeexport.php');

//admin_externalpage_setup('obfconfig');

$context = context_system::instance();
$url = new moodle_url('/local/obf/config.php');
$msg = optional_param('msg', '', PARAM_TEXT);
$action = optional_param('action', 'authenticate', PARAM_TEXT);

require_login();
require_capability('local/obf:configure', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$content = $OUTPUT->header();

switch ($action) {

    // Handle authentication.
    case 'authenticate':
        $form = new obf_config_form($FULLME);

        if (!is_null($data = $form->get_data())) {

            if (!empty($data->obfurl)) {
                set_config('obfurl', $data->obfurl, 'local_obf');
            }

            // OBF request token is set, (re)do authentication.
            if (!empty($data->obftoken)) {
                $client = obf_client::get_instance();

                try {
                    $client->authenticate($data->obftoken);

                    require_once($CFG->libdir . '/badgeslib.php');

                    $badges = array_merge(badges_get_badges(BADGE_TYPE_COURSE),
                            badges_get_badges(BADGE_TYPE_SITE));

                    // If there are existing (local) badges, redirect to export-page
                    if (count($badges) > 0) {
                        redirect(new moodle_url('/local/obf/config.php',
                                array('action' => 'exportbadges')));
                    }
                    // No local badges, no need to export
                    else {
                        redirect(new moodle_url('/local/obf/config.php',
                                array('msg' => get_string('authenticationsuccess', 'local_obf'))));
                    }
                } catch (Exception $e) {
                    $content .= $OUTPUT->notification($e->getMessage());
                }
            }
        } else {
            // Connection hasn't been made yet. Let's tell the user that, shall we?
            if (!get_config('local_obf', 'connectionestablished')) {
                $content .= $OUTPUT->notification(get_string('apierror496', 'local_obf'));
            }
        }

        if (!empty($msg)) {
            $content .= $OUTPUT->notification(s($msg), 'notifysuccess');
        }

        $content .= $PAGE->get_renderer('local_obf')->render($form);
        break;

    // Let the user select the badges that can be exported to OBF
    case 'exportbadges':

        $badges = array_merge(badges_get_badges(BADGE_TYPE_COURSE),
                badges_get_badges(BADGE_TYPE_SITE));
        $exportform = new obf_badge_export_form($FULLME, array('badges' => $badges));

        if (!is_null($data = $exportform->get_data())) {
            // At least one badge has been selected to be included in exporting.
            if (isset($data->toexport)) {
                // Export each selected badge separately.
                foreach ($data->toexport as $badgeid => $doexport) {
                    // Just to be sure the value of the checkbox is "1" and not "0", although
                    // technically that shouldn't be possible (those shouldn't be included).
                    if ($doexport) {
                        $badge = new badge($badgeid);

                        $email = new obf_email();
                        $email->set_body($badge->message);
                        $email->set_subject($badge->messagesubject);

                        $obfbadge = obf_badge::get_instance_from_array(array(
                                    'name' => $badge->name,
                                    'criteria_html' => '',
                                    'css' => '',
                                    'expires' => null,
                                    'id' => null,
                                    'tags' => array(),
                                    'ctime' => null,
                                    'description' => $badge->description,
                                    'image' => base64_encode(file_get_contents(moodle_url::make_pluginfile_url($badge->get_context()->id,
                                                            'badges', 'badgeimage', $badge->id, '/',
                                                            'f1', false))),
                                    'draft' => true
                        ));
                        $obfbadge->set_email($email);
                        $success = $obfbadge->export();

                        if (!$success) {
                            debugging('Exporting badge ' . $badge->name . ' failed.');
                            // Exporting badge probably failed. Do something?
                        }
                    }
                }
            }

            // Disable Moodle's own badge system
            if ($data->disablemoodlebadges) {
                set_config('enablebadges', 0);
            }
        }

        $content .= $PAGE->get_renderer('local_obf')->render_badge_exporter($exportform);
        break;
}

$content .= $OUTPUT->footer();
echo $content;
