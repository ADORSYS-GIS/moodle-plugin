<?php
/**
 * Main library file for the AI Assistant local plugin.
 *
 * This file is loaded by Moodle on every page request.
 * We use it to set up our plugin's integration with the Moodle page lifecycle.
 *
 * @package    local_aiassistant
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// REMOVED: The line "$plugin->component = 'local_aiassistant';" was here.
// It is not needed in lib.php and causes the error.

/**
 * This is the modern, correct way in Moodle to add assets (CSS/JS) to every page.
 * We create a class that observes the page setup process.
 */
class local_aiassistant_observer {
    /**
     * This is a Moodle "event observer" function. It is automatically called
     * very early in the page-building process, right before the <head> is printed.
     * This is the perfect place to require our CSS and JS.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function on_before_header(\core\event\base $event): void {
        global $PAGE;

        // First, check if the user has permission to use the assistant.
        if (!has_capability('local_aiassistant:use', context_system::instance())) {
            return;
        }

        // 1. Require our SCSS stylesheet.
        $PAGE->requires->scss('local_aiassistant', 'scss/ai_assistant.scss');

        // 2. Require our JavaScript AMD module.
        $PAGE->requires->js_call_amd('local_aiassistant/aiassistant', 'init');
    }
}

/**
 * This function adds the HTML for our chat UI placeholders to the page.
 * It is called just before the footer, which is the correct time to add HTML content.
 *
 * @return string The HTML for the initial UI placeholders.
 */
function local_aiassistant_before_footer(): string {
    // We still check the capability here before outputting any HTML.
    if (!has_capability('local_aiassistant:use', context_system::instance())) {
        return '';
    }

    // Render the Floating Action Button.
    $fab = \html_writer::tag(
        'button',
        '✨',
        [
            'id' => 'ai-assistant-fab',
            'class' => 'ai-fab',
            'aria-label' => get_string('togglechat', 'local_aiassistant')
        ]
    );

    // Render the empty container where our JS will place the chat window.
    $container = \html_writer::div('', 'ai-chat-window-container');

    return $fab . $container;
}