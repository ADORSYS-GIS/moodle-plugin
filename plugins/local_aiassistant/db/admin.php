<?php
/**
 * Admin settings navigation file for the AI Assistant plugin.
 *
 * This file is automatically included by Moodle to build the Site Administration navigation tree.
 *
 * @package    local_aiassistant
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We only want to add nodes to the admin tree if it's being built.
// $ADMIN is the global admin tree root object.
if ($ADMIN) {

    // --- Step 1: Create a new category for our plugin under the 'Plugins' tab. ---
    // The first parameter is a unique key for the category.
    // The second is the visible name, fetched from our language file.
    // The third parameter tells Moodle this is a 'Plugins' category.
    $ADMIN->add('plugins', new admin_category('local_aiassistant_category', get_string('pluginname', 'local_aiassistant')));

    // --- Step 2: Create the actual link to our settings page. ---
    // The first parameter is a unique key for this specific settings page link.
    // The second is the visible name of the link.
    // The third parameter is the URL to the settings page. Moodle settings pages all
    // use 'settings.php' and are differentiated by the 'section' parameter.
    // The section name must match the name we gave our admin_settingpage in settings.php.
    $settings = new admin_externalpage(
        'local_aiassistant_settings',
        get_string('settings', 'local_aiassistant'), // We'll need to add a 'settings' string
        new moodle_url('/admin/settings.php', ['section' => 'local_aiassistant'])
    );

    // --- Step 3: Add our settings page link inside the category we just created. ---
    $ADMIN->add('local_aiassistant_category', $settings);
}