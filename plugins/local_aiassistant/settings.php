<?php
defined('MOODLE_INTERNAL') || die();

// This check is crucial to prevent crashes during CLI installation.
if (isset($settings)) {

    // The name 'local_aiassistant' here MUST match the 'section' parameter in db/admin.php's moodle_url.
    $page = new admin_settingpage(
        'local_aiassistant',
        get_string('pluginname', 'local_aiassistant')
    );

    $page->add(new admin_setting_configpasswordunmask(
        'local_aiassistant/openai_api_key',
        get_string('openai_api_key', 'local_aiassistant'),
        get_string('openai_api_key_desc', 'local_aiassistant'),
        ''
    ));

    $page->add(new admin_setting_configtext(
        'local_aiassistant/rust_binary_path',
        get_string('rust_binary_path', 'local_aiassistant'),
        get_string('rust_binary_path_desc', 'local_aiassistant'),
        '/app/moodle-ai-processor',
        PARAM_PATH
    ));

    $settings->add($page);
}