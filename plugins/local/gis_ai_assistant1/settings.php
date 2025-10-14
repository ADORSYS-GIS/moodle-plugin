<?php
declare(strict_types=1);
// ENV-only configuration. This page is informational only.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_gis_ai_assistant1', get_string('pluginname', 'local_gis_ai_assistant1'));

    // Intro heading with short description and docs hint.
    $intro = html_writer::div(get_string('configdescription', 'local_gis_ai_assistant1'));
    $intro .= html_writer::div('See README.md in the plugin directory for full documentation.');
    $settings->add(new admin_setting_heading(
        'local_gis_ai_assistant1/heading',
        get_string('configtitle', 'local_gis_ai_assistant1'),
        $intro
    ));

    // Show presence/absence of essential environment variables (read-only) with coloured status.
    $envvars = [
        'OPENAI_API_KEY',
        'OPENAI_BASE_URL',
        'OPENAI_MODEL',
        'AI_RUST_MODE',
        'AI_RUST_ENDPOINT',
        'AI_TIMEOUT',
        'AI_DEBUG',
    ];

    $statusrows = [];
    foreach ($envvars as $key) {
        $present = (getenv($key) !== false);
        $label = $present ? get_string('env_present', 'local_gis_ai_assistant1') : get_string('env_missing', 'local_gis_ai_assistant1');
        $class = $present ? 'text-success' : 'text-danger';
        $status = html_writer::span(s($label), $class);
        $statusrows[] = html_writer::div(s($key) . ': ' . $status);
    }

    $settings->add(new admin_setting_heading(
        'local_gis_ai_assistant1/envstatus',
        get_string('envstatus', 'local_gis_ai_assistant1'),
        implode('', $statusrows)
    ));

    $ADMIN->add('localplugins', $settings);
}
