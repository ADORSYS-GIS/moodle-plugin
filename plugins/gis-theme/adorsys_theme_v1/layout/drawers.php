<?php
defined('MOODLE_INTERNAL') || die();

$sidepreblockshtml = $OUTPUT->blocks('side-pre');
$sidepostblockshtml = $OUTPUT->blocks('side-post');
$hasblocks = (strpos($sidepreblockshtml, 'data-block=') !== false || strpos($sidepostblockshtml, 'data-block=') !== false);

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'hasblocks' => $hasblocks,
    'sidepreblocks' => $sidepreblockshtml,
    'sidepostblocks' => $sidepostblockshtml,
    'maincontent' => $OUTPUT->main_content(),
    'navbar' => $OUTPUT->navbar(),
    'pageheading' => $PAGE->heading,
    'hasnavbar' => empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar(),
    'hasfooter' => empty($PAGE->layout_options['nofooter']),
    'footer' => $OUTPUT->standard_footer_html(),
    'standardendhtml' => $OUTPUT->standard_end_of_body_html(),
];

echo $OUTPUT->render_from_template('theme_adorsys_theme_v1/drawers', $templatecontext);
