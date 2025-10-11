<?php
declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

function local_gis_ai_assistant1_before_footer(): string {
    global $PAGE;
    if (!isloggedin() || isguestuser()) { return ''; }
    if (!has_capability('local/gis_ai_assistant1:use', \context_system::instance())) { return ''; }
    $PAGE->requires->js_call_amd('local_gis_ai_assistant1/chat_widget', 'init');
    return \html_writer::tag('button', 'AI', [
        'id' => 'gis-ai-assistant1-fab',
        'class' => 'gis-ai-fab',
        'type' => 'button',
        'aria-label' => get_string('togglechat', 'local_gis_ai_assistant1'),
    ]);
}
