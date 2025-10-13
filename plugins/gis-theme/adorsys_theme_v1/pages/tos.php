<?php
require_once(__DIR__ . '/../../../config.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/adorsys_theme_v1/pages/tos.php'));
$PAGE->set_pagelayout('standard'); 
$PAGE->set_title(get_string('terms_of_service', 'theme_adorsys_theme_v1'));
$PAGE->set_heading(get_string('terms_of_service', 'theme_adorsys_theme_v1'));

$content = get_config('theme_adorsys_theme_v1', 'toscontent');

$templatecontext = [
    'heading' => get_string('terms_of_service', 'theme_adorsys_theme_v1')
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_adorsys_theme_v1/tos', $templatecontext);

echo $OUTPUT->footer();
