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
 * Library functions for local_gis_ai_assistant plugin.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends navigation to add AI assistant link.
 *
 * @param global_navigation $navigation
 */
function local_gis_ai_assistant_extend_navigation(global_navigation $navigation) {
    global $USER, $PAGE;
    
    if (!get_config('local_gis_ai_assistant', 'enabled')) {
        return;
    }
    
    if (!has_capability('local/gis_ai_assistant:use', context_system::instance())) {
        return;
    }
    
    $node = $navigation->add(
        get_string('pluginname', 'local_gis_ai_assistant'),
        new moodle_url('/local/gis_ai_assistant/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_gis_ai_assistant',
        new pix_icon('i/completion-auto-enabled', '')
    );
    $node->showinflatnavigation = true;

    // Also ensure the floating widget is loaded for allowed page types.
    $allowedpagetypes = ['course-view', 'mod-', 'my-index', 'site-'];
    $pagetype = $PAGE->pagetype;
    foreach ($allowedpagetypes as $allowed) {
        if (strpos($pagetype, $allowed) === 0) {
            $PAGE->requires->js_call_amd('local_gis_ai_assistant/chat_widget', 'init');
            break;
        }
    }
}

/**
 * Adds AI assistant to the user menu.
 *
 * @param renderer_base $renderer
 * @return string
 */
function local_gis_ai_assistant_render_navbar_output(renderer_base $renderer) {
    global $USER, $PAGE;
    
    if (!get_config('local_gis_ai_assistant', 'enabled')) {
        return '';
    }
    
    if (!isloggedin() || isguestuser()) {
        return '';
    }
    
    if (!has_capability('local/gis_ai_assistant:use', context_system::instance())) {
        return '';
    }
    
    // Add AI chat button to pages where it makes sense.
    $allowedpagetypes = ['course-view', 'mod-', 'my-index', 'site-'];
    $pagetype = $PAGE->pagetype;
    
    foreach ($allowedpagetypes as $allowed) {
        if (strpos($pagetype, $allowed) === 0) {
            $PAGE->requires->js_call_amd('local_gis_ai_assistant/chat_widget', 'init');
            break;
        }
    }
    
    return '';
}

/**
 * Get AI configuration from environment variables.
 *
 * @return array Configuration array
 */
function local_gis_ai_assistant_get_config() {
    // Always compute fresh values to reflect runtime changes during tests.
    $enabled = (int) get_config('local_gis_ai_assistant', 'enabled');
    $enablecache = (int) get_config('local_gis_ai_assistant', 'enable_cache');
    $enableanalytics = (int) get_config('local_gis_ai_assistant', 'enable_analytics');

    return [
        'api_key' => getenv('OPENAI_API_KEY') ?: ($_SERVER['OPENAI_API_KEY'] ?? null),
        'base_url' => getenv('OPENAI_BASE_URL') ?: ($_SERVER['OPENAI_BASE_URL'] ?? 'https://api.openai.com/v1'),
        'model' => getenv('OPENAI_MODEL') ?: ($_SERVER['OPENAI_MODEL'] ?? (string) get_config('local_gis_ai_assistant', 'default_model')),
        'enabled' => (bool) $enabled,
        'max_tokens' => (int) get_config('local_gis_ai_assistant', 'max_tokens'),
        'temperature' => (float) get_config('local_gis_ai_assistant', 'temperature'),
        'system_prompt' => (string) get_config('local_gis_ai_assistant', 'system_prompt'),
        'rate_limit_requests' => (int) get_config('local_gis_ai_assistant', 'rate_limit_requests'),
        'rate_limit_tokens' => (int) get_config('local_gis_ai_assistant', 'rate_limit_tokens'),
        'cache_ttl' => (int) get_config('local_gis_ai_assistant', 'cache_ttl'),
        'enable_cache' => (bool) $enablecache,
        'enable_analytics' => (bool) $enableanalytics,
    ];
}

/**
 * Check if AI service is properly configured.
 *
 * @return bool True if configured, false otherwise
 */
function local_gis_ai_assistant_is_configured() {
    $config = local_gis_ai_assistant_get_config();
    return !empty($config['api_key']) && !empty($config['base_url']) && $config['enabled'];
}

/**
 * Sanitize user input for AI requests.
 *
 * @param string $input User input
 * @return string Sanitized input
 */
function local_gis_ai_assistant_sanitize_input($input) {
    // Remove potentially harmful content.
    $input = clean_text($input, FORMAT_PLAIN);
    
    // Limit length.
    $maxlength = 10000; // Reasonable limit for user input.
    if (strlen($input) > $maxlength) {
        $input = substr($input, 0, $maxlength);
    }
    
    // Remove excessive whitespace.
    $input = preg_replace('/\s+/', ' ', trim($input));
    
    return $input;
}

/**
 * Generate cache key for AI requests.
 *
 * @param string $prompt The prompt
 * @param array $params Additional parameters
 * @return string Cache key
 */
function local_gis_ai_assistant_generate_cache_key($prompt, $params = []) {
    $keydata = [
        'prompt' => $prompt,
        'model' => $params['model'] ?? '',
        'temperature' => $params['temperature'] ?? '',
        'max_tokens' => $params['max_tokens'] ?? '',
        'system_prompt' => $params['system_prompt'] ?? '',
    ];
    
    return 'ai_response_' . md5(json_encode($keydata));
}

/**
 * Log AI request for analytics.
 *
 * @param int $userid User ID
 * @param string $model Model used
 * @param array $usage Token usage data
 * @param int $responsetime Response time in milliseconds
 * @param string $status Request status
 * @param string $error Error message if any
 */
function local_gis_ai_assistant_log_request($userid, $model, $usage = [], $responsetime = 0, $status = 'success', $error = null, $message = '', $response = '') {
    global $DB;
    
    if (!get_config('local_gis_ai_assistant', 'enable_analytics')) {
        return;
    }
    
    $record = new stdClass();
    $record->userid = $userid;
    $record->model = $model;
    // Store required fields according to install.xml schema.
    $record->message = (string)$message;
    $record->response = (string)$response;
    $record->tokens_used = $usage['total_tokens'] ?? 0;
    $record->response_time = $responsetime;
    $record->timecreated = time();
    
    $DB->insert_record('local_gis_ai_assistant_conversations', $record);
}

/**
 * Check if user has exceeded rate limits.
 *
 * @param int $userid User ID
 * @param int $tokens Tokens for current request
 * @return array ['allowed' => bool, 'reset_time' => int]
 */
function local_gis_ai_assistant_check_rate_limit($userid, $tokens = 0) {
    global $DB;
    
    $config = local_gis_ai_assistant_get_config();
    $maxrequests = $config['rate_limit_requests'];
    $maxtokens = $config['rate_limit_tokens'];
    
    $windowstart = floor(time() / 3600) * 3600; // Start of current hour.
    
    // Get or create rate limit record.
    $record = $DB->get_record('local_gis_ai_assistant_rate_limits', [
        'userid' => $userid,
        'window_start' => $windowstart
    ]);
    
    if (!$record) {
        $record = new stdClass();
        $record->userid = $userid;
        $record->requests_count = 0;
        $record->tokens_count = 0;
        $record->window_start = $windowstart;
        $record->timemodified = time();
        $record->id = $DB->insert_record('local_gis_ai_assistant_rate_limits', $record);
    }
    
    // Check limits.
    $newrequests = $record->requests_count + 1;
    $newtokens = $record->tokens_count + $tokens;
    
    if ($newrequests > $maxrequests || $newtokens > $maxtokens) {
        return [
            'allowed' => false,
            'reset_time' => $windowstart + 3600,
            'requests_remaining' => max(0, $maxrequests - $record->requests_count),
            'tokens_remaining' => max(0, $maxtokens - $record->tokens_count)
        ];
    }
    
    return [
        'allowed' => true,
        'reset_time' => $windowstart + 3600,
        'requests_remaining' => $maxrequests - $newrequests,
        'tokens_remaining' => $maxtokens - $newtokens
    ];
}

/**
 * Update rate limit counters.
 *
 * @param int $userid User ID
 * @param int $tokens Tokens used
 */
function local_gis_ai_assistant_update_rate_limit($userid, $tokens = 0) {
    global $DB;
    
    $windowstart = floor(time() / 3600) * 3600;
    
    $record = $DB->get_record('local_gis_ai_assistant_rate_limits', [
        'userid' => $userid,
        'window_start' => $windowstart
    ]);
    
    if ($record) {
        $record->requests_count++;
        $record->tokens_count += $tokens;
        $record->timemodified = time();
        $DB->update_record('local_gis_ai_assistant_rate_limits', $record);
    }
}
