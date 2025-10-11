<?php
// ENV-only configuration. This page is informational only.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_gis_ai_assistant1', get_string('pluginname', 'local_gis_ai_assistant1'));
    $settings->add(new admin_setting_heading('local_gis_ai_assistant1/heading',
        get_string('configtitle', 'local_gis_ai_assistant1'),
        get_string('configdescription', 'local_gis_ai_assistant1')));
    $ADMIN->add('localplugins', $settings);
}
