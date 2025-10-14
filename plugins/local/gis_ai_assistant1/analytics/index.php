<?php
// Analytics page for GIS AI Assistant.

require_once(__DIR__ . '/../../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/gis_ai_assistant1:viewanalytics', $context);

$PAGE->set_url(new moodle_url('/local/gis_ai_assistant1/analytics/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('analytics', 'local_gis_ai_assistant1'));
$PAGE->set_heading(get_string('analytics', 'local_gis_ai_assistant1'));

// Render analytics using renderable + mustache template.
$rows = [];
$renderable = new \local_gis_ai_assistant1\output\analytics($rows);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_gis_ai_assistant1/analytics', $renderable->export_for_template($OUTPUT));
echo $OUTPUT->footer();
