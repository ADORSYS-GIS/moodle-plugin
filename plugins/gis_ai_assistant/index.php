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
 * AI Assistant main page.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

// Load plugin library functions used below.
require_once($CFG->dirroot . '/local/gis_ai_assistant/lib.php');
// Defensive include in case $CFG path mapping differs in runtime/container.
if (!function_exists('local_gis_ai_assistant_is_configured')) {
    require_once(__DIR__ . '/lib.php');
}

$context = context_system::instance();
require_capability('local/gis_ai_assistant:use', $context);

// Check if AI is enabled via plugin configuration.
$cfg = local_gis_ai_assistant_get_config();
if (empty($cfg['enabled'])) {
    throw new moodle_exception('ai_disabled', 'local_gis_ai_assistant');
}

// Check if AI is configured.
if (!local_gis_ai_assistant_is_configured()) {
    throw new moodle_exception('no_api_key', 'local_gis_ai_assistant');
}

$PAGE->set_context($context);
$PAGE->set_url('/local/gis_ai_assistant/index.php');
$PAGE->set_title(get_string('chat_title', 'local_gis_ai_assistant'));
$PAGE->set_heading(get_string('chat_title', 'local_gis_ai_assistant'));
$PAGE->set_pagelayout('standard');

// Add CSS and JS.
$PAGE->requires->css('/local/gis_ai_assistant/styles.css');
// Highlight.js theme (if highlight.js is present on page, this ensures styled code blocks).
$PAGE->requires->css('/local/gis_ai_assistant/styles/highlight.css');

$PAGE->requires->js_call_amd('local_gis_ai_assistant/chat', 'init');

echo $OUTPUT->header();

// Render the chat interface.
echo $OUTPUT->render_from_template('local_gis_ai_assistant/chat', [
    'user_name' => fullname($USER),
    'placeholder' => get_string('chat_placeholder', 'local_gis_ai_assistant'),
    'send_text' => get_string('send', 'local_gis_ai_assistant'),
    'clear_text' => get_string('clear', 'local_gis_ai_assistant'),
    'thinking_text' => get_string('thinking', 'local_gis_ai_assistant'),
]);

echo $OUTPUT->footer();
