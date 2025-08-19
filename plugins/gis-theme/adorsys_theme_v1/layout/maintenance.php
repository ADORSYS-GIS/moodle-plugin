<?php
defined('MOODLE_INTERNAL') || die();

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'maincontent' => $OUTPUT->main_content(),
    'hasnavbar' => false,
    'hasfooter' => false,
    'standardendhtml' => $OUTPUT->standard_end_of_body_html(),
];

echo $OUTPUT->render_from_template('theme_adorsys_theme_v1/maintenance', $templatecontext);
