<?php
defined('MOODLE_INTERNAL') || die();

if (is_siteadmin()) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettings' . $THEME->name, get_string('configtitle', 'theme_' . $THEME->name));
    $page = new admin_settingpage('theme_' . $THEME->name . '_colors', get_string('colorsettings', 'theme_' . $THEME->name));
    // Add color settings here.
    $settings->add($page);
    $ADMIN->add('themes', $settings);
}