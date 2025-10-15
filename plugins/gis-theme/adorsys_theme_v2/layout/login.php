<?php
defined('MOODLE_INTERNAL') || die();

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'pageheading' => get_string('login'),
];

echo $OUTPUT->render_from_template('theme_adorsys_theme_v2/login', $templatecontext);
