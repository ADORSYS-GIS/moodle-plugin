<?php
// This file defines settings for the Adorsys theme.

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Theme heading.
    $settings->add(new admin_setting_heading(
        'theme_adorsys_theme_v1/generalsettings',
        get_string('themeheading', 'theme_adorsys_theme_v1'),
        format_text(get_string('themedesc', 'theme_adorsys_theme_v1'), FORMAT_MARKDOWN)
    ));

    // Logo file setting.
    $settings->add(new admin_setting_configstoredfile(
        'theme_adorsys_theme_v1/logo',
        get_string('logo', 'admin'),
        get_string('logodesc', 'admin'),
        'logo'
    ));

    // Custom CSS.
    $settings->add(new admin_setting_configtextarea(
        'theme_adorsys_theme_v1/customcss',
        get_string('customcss', 'theme_adorsys_theme_v1'),
        get_string('customcssdesc', 'theme_adorsys_theme_v1'),
        '',
        PARAM_RAW
    ));
}
