<?php
/**
 * Admin settings navigation file for the AI Assistant plugin.
 *
 * This file tells Moodle how to add a link to our plugin's settings page
 * in the main Site Administration block.
 *
 * @package    local_aiassistant
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// This is a special Moodle hook that runs when the admin navigation is being built.
// It checks if the settings page object '$ADMIN' has been created.
if (isset($ADMIN)) {

    // 1. Define a unique name for our settings page.
    //    This should match the first parameter of the admin_settingpage in settings.php.
    $settingspagename = 'local_aiassistant';

    // 2. Create the new settings page object.
    //    This object is what we created in settings.php. Moodle finds it by name.
    $settingspage = new admin_settingpage(
        $settingspagename,
        get_string('pluginname', 'local_aiassistant') // The text for the link.
    );

    // 3. Tell Moodle where to put the link.
    //    We want to add it under 'localplugins' in the 'plugins' tab.
    //    The variable `$ADMIN` is the root of the entire admin tree.
    $ADMIN->add('localplugins', $settingspage);
}