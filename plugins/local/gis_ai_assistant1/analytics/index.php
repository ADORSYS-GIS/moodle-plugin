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

// Optional filters from query string.
$datefrom = optional_param('date_from', 0, PARAM_INT);
$dateto   = optional_param('date_to', 0, PARAM_INT);
$userid   = optional_param('user_id', 0, PARAM_INT);

$filters = [];
if ($datefrom) { $filters['date_from'] = $datefrom; }
if ($dateto) { $filters['date_to'] = $dateto; }
if ($userid) { $filters['user_id'] = $userid; }

// Generate analytics rows.
$rows = \local_gis_ai_assistant1\analytics\report_generator::generate($filters);

// Render analytics using renderable + mustache template.
$renderable = new \local_gis_ai_assistant1\output\analytics($rows);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_gis_ai_assistant1/analytics', $renderable->export_for_template($OUTPUT));
echo $OUTPUT->footer();
