<?php
// This file defines settings for the Adorsys theme v2.

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Create tabbed settings container.
    $settings = new theme_boost_admin_settingspage_tabs(
        'themesettingadorsys_theme_v2',
        get_string('configtitle', 'theme_adorsys_theme_v2')
    );

    $page = new admin_settingpage(
        'theme_adorsys_theme_v2_general',
        get_string('generalsettings', 'theme_adorsys_theme_v2')
    );

    // Logo file setting.
    $name = 'theme_adorsys_theme_v2/logo';
    $title = get_string('logo', 'admin');
    $description = get_string('logodesc', 'admin');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $page->add($setting);

    // Custom CSS.
    $name = 'theme_adorsys_theme_v2/customcss';
    $title = get_string('customcss', 'theme_adorsys_theme_v2');
    $description = get_string('customcssdesc', 'theme_adorsys_theme_v2');
    $setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_RAW);
    $page->add($setting);

    // Add the page to the tabbed settings container.
    $settings->add($page);
}
