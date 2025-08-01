<?php
/**
 * Defines the settings page for the AI Assistant plugin.
 *
 * This file is automatically included by Moodle when building the admin settings tree.
 * By creating a new 'admin_settingpage' and then adding it to the '$ADMIN' tree,
 * Moodle will automatically create a clickable link to this page under
 * Site administration -> Plugins -> Local plugins.
 *
 * @package    local_aiassistant
 * @copyright  2024 Adorsys-GIS Moodle AI Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// This check is a Moodle standard to ensure the admin tree is being built.
// It is the replacement for the 'isset($settings)' check and is more reliable.
if ($ADMIN->fulltree) {

    // 1. Create the settings page object.
    //    The first parameter 'local_aiassistant' is the unique "section name" for this page.
    $settings = new admin_settingpage(
        'local_aiassistant',
        get_string('pluginname', 'local_aiassistant') // The page title.
    );

    // 2. Add the individual settings fields to our page object.

    // === [General Settings Section] ===
    $settings->add(new admin_setting_heading(
        'local_aiassistant/general_heading',
        get_string('general_settings_heading', 'local_aiassistant'),
        ''
    ));

    // Enable/disable the plugin
    $settings->add(new admin_setting_configcheckbox(
        'local_aiassistant/enabled',
        get_string('enabled', 'local_aiassistant'),
        get_string('enabled_desc', 'local_aiassistant'),
        1
    ));

    // === [API Settings Section] ===
    $settings->add(new admin_setting_heading(
        'local_aiassistant/api_heading',
        get_string('api_settings_heading', 'local_aiassistant'),
        ''
    ));

    // Add the OpenAI API Key setting.
    $settings->add(new admin_setting_configpasswordunmask(
        'local_aiassistant/openai_api_key',
        get_string('openai_api_key', 'local_aiassistant'),
        get_string('openai_api_key_desc', 'local_aiassistant'),
        '' // Default value.
    ));

    // === [Binary Settings Section] ===
    $settings->add(new admin_setting_heading(
        'local_aiassistant/binary_heading',
        get_string('binary_settings_heading', 'local_aiassistant'),
        ''
    ));

    // Rust binary path with basic validation (non-empty)
    $setting = new admin_setting_configtext(
        'local_aiassistant/rust_binary_path',
        get_string('rust_binary_path', 'local_aiassistant'),
        get_string('rust_binary_path_desc', 'local_aiassistant'),
        '/app/moodle-ai-processor',
        PARAM_PATH
    );
    $setting->set_validate_function(function ($value) {
        return empty($value) ? get_string('path_required', 'local_aiassistant') : true;
    });
    $settings->add($setting);

    // 3. Add our newly created page to the correct category in the main admin tree.
    //    This is the final step that makes the link appear.
    $ADMIN->add('localplugins', $settings);
}