<?php
defined('MOODLE_INTERNAL') || die();

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID)]),
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'hasnavbar' => empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar(),
    'hasfooter' => empty($PAGE->layout_options['nofooter']),
    'navbar' => $OUTPUT->navbar(),
    'pageheading' => $PAGE->heading,
    'courseheader' => $OUTPUT->course_header(),
    'coursecontentheader' => $OUTPUT->course_content_header(),
    'maincontent' => $OUTPUT->main_content(),
    'coursecontentfooter' => $OUTPUT->course_content_footer(),
    'coursefooter' => $OUTPUT->course_footer(),
    'footer' => $OUTPUT->standard_footer_html(),
    'standardendhtml' => $OUTPUT->standard_end_of_body_html(),
];

echo $OUTPUT->render_from_template('theme_adorsys_theme_v1/default', $templatecontext);
