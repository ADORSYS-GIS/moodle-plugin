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
    
    // Rate limiting settings.
    $settings->add(new \admin_setting_heading(
        'aiprovider_gis_ai/rate_limiting',
        get_string('ratelimiting', 'aiprovider_gis_ai'),
        get_string('ratelimiting_desc', 'aiprovider_gis_ai')
    ));
    
    // Requests per hour.
    $settings->add(new \admin_setting_configtext(
        'aiprovider_gis_ai/requests_per_hour',
        get_string('requestsperhour', 'aiprovider_gis_ai'),
        get_string('requestsperhour_desc', 'aiprovider_gis_ai'),
        '60',
        PARAM_INT
    ));
    
    // Tokens per hour.
    $settings->add(new \admin_setting_configtext(
        'aiprovider_gis_ai/tokens_per_hour',
        get_string('tokensperhour', 'aiprovider_gis_ai'),
        get_string('tokensperhour_desc', 'aiprovider_gis_ai'),
        '40000',
        PARAM_INT
    ));
    
    // Enable streaming.
    $settings->add(new \admin_setting_configcheckbox(
        'aiprovider_gis_ai/enable_streaming',
        get_string('enablestreaming', 'aiprovider_gis_ai'),
        get_string('enablestreaming_desc', 'aiprovider_gis_ai'),
        1
    ));
    
    // Enable conversation persistence.
    $settings->add(new \admin_setting_configcheckbox(
        'aiprovider_gis_ai/enable_conversations',
        get_string('enableconversations', 'aiprovider_gis_ai'),
        get_string('enableconversations_desc', 'aiprovider_gis_ai'),
        1
    ));
    
    // Advanced settings.
    $settings->add(new \admin_setting_heading(
        'aiprovider_gis_ai/advanced',
        get_string('advanced', 'aiprovider_gis_ai'),
        get_string('advanced_desc', 'aiprovider_gis_ai')
    ));
    
    // Request timeout.
    $settings->add(new \admin_setting_configtext(
        'aiprovider_gis_ai/request_timeout',
        get_string('requesttimeout', 'aiprovider_gis_ai'),
        get_string('requesttimeout_desc', 'aiprovider_gis_ai'),
        '30',
        PARAM_INT
    ));
    
    // Max tokens per request.
    $settings->add(new \admin_setting_configtext(
        'aiprovider_gis_ai/max_tokens',
        get_string('maxtokens', 'aiprovider_gis_ai'),
        get_string('maxtokens_desc', 'aiprovider_gis_ai'),
        '2000',
        PARAM_INT
    ));
    
    // Temperature.
    $settings->add(new \admin_setting_configtext(
        'aiprovider_gis_ai/temperature',
        get_string('temperature', 'aiprovider_gis_ai'),
        get_string('temperature_desc', 'aiprovider_gis_ai'),
        '0.7',
        PARAM_FLOAT
    ));

    // Healthcheck status display (read-only).
    $cache = \cache::make('aiprovider_gis_ai', 'analytics');
    $health = $cache->get('healthcheck_status');
    if ($health === false) {
        $provider = new \aiprovider_gis_ai\provider();
        $health = $provider->healthcheck();
        $cache->set('healthcheck_status', $health);
    }
    $statushtml = $health['ok']
        ? '<span class="text-success">✓ ' . s($health['message']) . '</span>'
        : '<span class="text-danger">✗ ' . s($health['message']) . '</span>';
    $settings->add(new \admin_setting_heading(
        'aiprovider_gis_ai/healthcheck',
        get_string('healthcheck', 'aiprovider_gis_ai'),
        $statushtml
    ));

    // Attempt to place under AI category; fallback to localplugins if category does not exist.
    $category = $ADMIN->locate('ai') ? 'ai' : 'localplugins';
    $ADMIN->add($category, $settings);

    // External admin page linking to the analytics dashboard.
    $page = new \admin_externalpage(
        'aiprovider_gis_ai_analytics',
        get_string('analytics', 'aiprovider_gis_ai'),
        new \moodle_url('/ai/provider/gis_ai/analytics.php'),
        'aiprovider/gis_ai:viewanalytics'
    );
    $ADMIN->add($category, $page);
}
