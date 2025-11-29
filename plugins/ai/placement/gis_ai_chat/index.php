<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

require_once(__DIR__ . '/../../../config.php');

$context = context_system::instance();
require_login();
require_capability('aiplacement/gis_ai_chat:generate_text', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/ai/placement/gis_ai_chat/index.php'));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'aiplacement_gis_ai_chat'));
$PAGE->set_heading(get_string('pluginname', 'aiplacement_gis_ai_chat'));

// Boot a simple demo AMD to exercise the placement AJAX end-to-end.
$PAGE->requires->js_call_amd('aiplacement_gis_ai_chat/demo', 'init', ['.gis-ai-chat-full', ['contextid' => $context->id]]);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('aiplacement_gis_ai_chat/full_chat', []);

echo $OUTPUT->footer();
