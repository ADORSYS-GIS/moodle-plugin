<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Moodle requires this variable to be named $settings.
 * The variable is created by the Moodle admin pages.
 * We must check if it exists before using it, especially during CLI installation.
 */
if (isset($settings) && ($ADMIN->fulltree || get_context_instance(CONTEXT_SYSTEM))) {

    // Create a new settings page for our plugin.
    $page = new admin_settingpage(
        'local_aiassistant', // A unique name for this settings page
        get_string('pluginname', 'local_aiassistant') // The title of the page
    );

    // Add a setting for the API key.
    $page->add(new admin_setting_configpasswordunmask(
        'local_aiassistant/openai_api_key',
        get_string('openai_api_key', 'local_aiassistant'),
        get_string('openai_api_key_desc', 'local_aiassistant'),
        '' // Default value
    ));

    // Add a setting for the path to our compiled Rust binary.
    $page->add(new admin_setting_configtext(
        'local_aiassistant/rust_binary_path',
        get_string('rust_binary_path', 'local_aiassistant'),
        get_string('rust_binary_path_desc', 'local_aiassistant'),
        '/app/moodle-ai-processor', // Default path inside our Docker container
        PARAM_PATH
    ));

    // Finally, add our newly created page to the main settings object.
    $settings->add($page);
}