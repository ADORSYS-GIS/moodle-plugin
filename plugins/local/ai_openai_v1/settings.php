<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('local_ai_openai_v1_settings', get_string('pluginname', 'local_ai_openai_v1'));

    $settings->add(new admin_setting_configtext(
        'local_ai_openai_v1/api_key',
        get_string('api_key', 'local_ai_openai_v1'),
        get_string('api_key_desc', 'local_ai_openai_v1'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_ai_openai_v1/base_url',
        get_string('base_url', 'local_ai_openai_v1'),
        get_string('base_url_desc', 'local_ai_openai_v1'),
        '',
        PARAM_URL
    ));

    $default_path = __DIR__ . '/rust_processor/target/release/moodle_ai_processor_openai_v1';
    $settings->add(new admin_setting_configtext(
        'local_ai_openai_v1/rust_binary_path',
        get_string('rust_binary_path', 'local_ai_openai_v1'),
        get_string('rust_binary_path_desc', 'local_ai_openai_v1'),
        $default_path,
        PARAM_PATH
    ));

    $ADMIN->add('localplugins', $settings);
}