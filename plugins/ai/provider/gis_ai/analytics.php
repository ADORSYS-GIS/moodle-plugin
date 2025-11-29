<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

require_once(__DIR__ . '/../../../config.php');

// This page is restricted to site administrators and users with the dedicated capability.
// If you want managers (with the capability) to access this page without being site admins,
// remove the require_admin() call below.
$context = context_system::instance();
require_login();
require_admin();
require_capability('aiprovider/gis_ai:viewanalytics', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/ai/provider/gis_ai/analytics.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('analytics', 'aiprovider_gis_ai'));
$PAGE->set_heading(get_string('analytics', 'aiprovider_gis_ai'));

// Require AMD to fetch and render analytics into the container rendered by mustache.
$PAGE->requires->js_call_amd('aiprovider_gis_ai/analytics_dashboard', 'init', ['.aiprovider-gis-analytics', []]);

// Add real-time refresh capability.
$PAGE->requires->js_call_amd('aiprovider_gis_ai/realtime_analytics', 'init');

// Add date range selector.
$daterange = optional_param('daterange', '7days', PARAM_ALPHA);
$customstart = optional_param('start', null, PARAM_INT);
$customend = optional_param('end', null, PARAM_INT);

// Build filter options.
$filters = [
    'daterange' => $daterange,
    'customstart' => $customstart,
    'customend' => $customend,
];

// Add analytics controls.
$controls = new \aiprovider_gis_ai\output\analytics_controls($filters);

echo $OUTPUT->header();

echo $OUTPUT->render($controls);

$renderable = new \aiprovider_gis_ai\output\analytics($filters);
echo $OUTPUT->render($renderable);

// Add rate limit management section.
if (has_capability('moodle/site:config', $context)) {
    $ratelimits = new \aiprovider_gis_ai\output\rate_limit_management();
    echo $OUTPUT->render($ratelimits);
}

echo $OUTPUT->footer();
