<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle hooks and integration points for GIS AI Assistant.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib.php');

/**
 * Hook to inject AI chat widget into course pages.
 * 
 * This function is called by Moodle's output system to add content
 * to the page footer on course-related pages.
 */
function local_gis_ai_assistant_before_footer() {
    global $PAGE, $USER;
    
    // Only show on course pages and if user has capability
    if (!local_gis_ai_assistant_should_show_widget()) {
        return '';
    }
    
    // Add the chat widget JavaScript
    $PAGE->requires->js_call_amd('local_gis_ai_assistant/chat_widget', 'init');
    
    return '';
}

/**
 * Hook to add settings navigation.
 */
function local_gis_ai_assistant_extend_settings_navigation(settings_navigation $navigation, context $context) {
    global $USER;
    
    if (!has_capability('local/gis_ai_assistant:viewanalytics', $context)) {
        return;
    }
    
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        $node = $navigation->get('root');
        if ($node) {
            $node->add(
                get_string('analytics_title', 'local_gis_ai_assistant'),
                new moodle_url('/local/gis_ai_assistant/analytics.php'),
                navigation_node::TYPE_CUSTOM
            );
        }
    }
}

/**
 * Determine if the chat widget should be shown on current page.
 */
function local_gis_ai_assistant_should_show_widget() {
    global $PAGE, $USER;
    
    // Check if AI is enabled via plugin configuration
    $cfg = local_gis_ai_assistant_get_config();
    if (empty($cfg['enabled'])) { return false; }
    
    // Check user capability
    $context = context_system::instance();
    if (!has_capability('local/gis_ai_assistant:use', $context)) {
        return false;
    }
    
    // Show on course pages, activity pages, and dashboard
    $allowedpagetypes = [
        'course-view',
        'mod-*',
        'my-index',
        'site-index'
    ];
    
    foreach ($allowedpagetypes as $pagetype) {
        if (fnmatch($pagetype, $PAGE->pagetype)) {
            return true;
        }
    }
    
    return false;
}
