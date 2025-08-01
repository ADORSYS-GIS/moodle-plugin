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

    // Add the OpenAI API Key setting.
    $settings->add(new admin_setting_configpasswordunmask(
        'local_aiassistant/openai_api_key',
        get_string('openai_api_key', 'local_aiassistant'),
        get_string('openai_api_key_desc', 'local_aiassistant'),
        '' // Default value.
    ));

    // Add the path to the Rust binary.
    $settings->add(new admin_setting_configtext(
        'local_aiassistant/rust_binary_path',
        get_string('rust_binary_path', 'local_aiassistant'),
        get_string('rust_binary_path_desc', 'local_aiassistant'),
        '/app/moodle-ai-processor', // Default path for our Docker environment.
        PARAM_PATH
    ));

    // 3. Add our newly created page to the correct category in the main admin tree.
    //    This is the final step that makes the link appear.
    $ADMIN->add('localplugins', $settings);
}