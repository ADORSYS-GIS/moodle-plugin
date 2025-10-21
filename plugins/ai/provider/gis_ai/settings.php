<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use core_ai\admin\admin_settingspage_provider;

if ($hassiteconfig) {
    // Provider-specific settings page. Secrets prefer ENV but UI fallback is provided.
    if (class_exists('core_ai\\admin\\admin_settingspage_provider')) {
        $settings = new admin_settingspage_provider(
            'aiprovider_gis_ai',
            new lang_string('pluginname', 'aiprovider_gis_ai'),
            'moodle/site:config',
            true
        );
    } else {
        // Fallback for older builds without the AI admin class.
        $settings = new \admin_settingpage(
            'aiprovider_gis_ai',
            new lang_string('pluginname', 'aiprovider_gis_ai')
        );
    }

    $settings->add(new \admin_setting_heading(
        'aiprovider_gis_ai/intro',
        get_string('settingsheading', 'aiprovider_gis_ai'),
        get_string('settingsdesc', 'aiprovider_gis_ai')
    ));

    // Base URL (fallback to ENV OPENAI_BASE_URL at runtime).
    $settings->add(new \admin_setting_configtext(
        'aiprovider_gis_ai/baseurl',
        get_string('baseurl', 'aiprovider_gis_ai'),
        get_string('baseurl_desc', 'aiprovider_gis_ai'),
        'https://api.openai.com/v1',
        PARAM_URL
    ));

    // API key is env-only (OPENAI_API_KEY). No UI storage to avoid accidental persistence.

    // Default model.
    $settings->add(new \admin_setting_configtext(
        'aiprovider_gis_ai/model',
        get_string('model', 'aiprovider_gis_ai'),
        get_string('model_desc', 'aiprovider_gis_ai'),
        'gpt-4o',
        PARAM_TEXT
    ));

    // Attempt to place under AI category; fallback to localplugins if category does not exist.
    $category = $ADMIN->locate('ai') ? 'ai' : 'localplugins';
    $ADMIN->add($category, $settings);
}
