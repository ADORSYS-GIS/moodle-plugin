<?php
/**
 * Admin settings navigation file for the AI Assistant plugin.
 *
 * @package    local_aiassistant
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// This line adds our settings page to the main "Plugins" admin category.
// It will appear under the "Local plugins" heading.
$ADMIN->add('localplugins', new admin_category('local_aiassistant_settings', get_string('pluginname', 'local_aiassistant')));

// This line defines the actual settings page.
// The URL should point to the system settings page and specify our plugin's unique section name.
$settings->add(new admin_externalpage(
    'local_aiassistant_settings_page', // A unique name for this link/node
    get_string('pluginname', 'local_aiassistant'), // The text that will be displayed for the link
    new moodle_url('/admin/settings.php', ['section' => 'local_aiassistant']), // The destination URL
    'moodle/site:config' // The capability required to see this link (site administrators)
));