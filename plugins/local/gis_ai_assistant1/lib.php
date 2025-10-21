<?php
declare(strict_types=1);

/**
 * Core library for the GIS AI Assistant (local_gis_ai_assistant1)
 *
 * Lightweight helpers and integration hooks used by the plugin.
 * Heavier logic belongs in classes/ namespaces.
 *
 * @package   local_gis_ai_assistant1
 */

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

/**
 * Extend site navigation with an Analytics entry for site admins.
 *
 * @param global_navigation $nav
 * @return void
 */
function local_gis_ai_assistant1_extend_navigation(global_navigation $nav): void {
    if (!is_siteadmin()) {
        return;
    }
    $url = new \moodle_url('/local/gis_ai_assistant1/analytics/index.php');
    $nav->add(get_string('analytics', 'local_gis_ai_assistant1'), $url, navigation_node::TYPE_SETTING);
}

/**
 * Pluginfile handler. Not serving files yet.
 */
function local_gis_ai_assistant1_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    send_file_not_found();
}
