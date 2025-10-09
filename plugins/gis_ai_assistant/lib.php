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
    global $PAGE;

    $cfg = local_gis_ai_assistant_get_config();
    if (empty($cfg['enabled'])) {
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

    // Load floating widget on allowed page types.
    $allowedpagetypes = ['course-view', 'mod-', 'my-index', 'site-'];
    $pagetype = $PAGE->pagetype;
    foreach ($allowedpagetypes as $p) {
        if (strpos($pagetype, $p) === 0) {
            // Ensure code highlighting styles are available.
            $PAGE->requires->css('/local/gis_ai_assistant/styles/highlight.css');
            
            $PAGE->requires->js_call_amd('local_gis_ai_assistant/chat_widget', 'init');
            break;
        }
    }
}

/**
 * Get plugin configuration: ENV for credentials and endpoint/model, DB for the rest.
 *
 * @return array
 */
function local_gis_ai_assistant_get_config() {
    // Credentials from ENV only.
    $getenvn = function(string $name) {
        return getenv($name) !== false ? getenv($name) : ($_SERVER[$name] ?? null);
    };

    $apiKey = $getenvn('OPENAI_API_KEY');
    // Environment-only endpoint and model (treat as credentials). Fallback to safe defaults.
    $envBaseUrl = $getenvn('OPENAI_BASE_URL');
    $envModel = $getenvn('OPENAI_MODEL');
    $baseUrl = ($envBaseUrl !== null && $envBaseUrl !== '') ? $envBaseUrl : 'https://api.openai.com/v1';
    $model = ($envModel !== null && $envModel !== '') ? $envModel : 'gpt-4o-mini';

    $enabled = (int) get_config('local_gis_ai_assistant', 'enabled');
    $maxTokens = (int) get_config('local_gis_ai_assistant', 'max_tokens');
    $temperature = (float) get_config('local_gis_ai_assistant', 'temperature');
    $systemPrompt = (string) get_config('local_gis_ai_assistant', 'system_prompt');
    $rateReq = (int) get_config('local_gis_ai_assistant', 'rate_limit_requests');
    $rateTok = (int) get_config('local_gis_ai_assistant', 'rate_limit_tokens');
    $cacheTtl = (int) get_config('local_gis_ai_assistant', 'cache_ttl');
    $enableCache = (int) get_config('local_gis_ai_assistant', 'enable_cache');
    $enableAnalytics = (int) get_config('local_gis_ai_assistant', 'enable_analytics');
    $streamTtl = (int) get_config('local_gis_ai_assistant', 'stream_session_ttl');

    return [
        'api_key' => $apiKey,
        'base_url' => $baseUrl,
        'model' => $model,
        'enabled' => (bool) $enabled,
        'max_tokens' => $maxTokens ?: 2048,
        'temperature' => $temperature ?: 0.7,
        'system_prompt' => $systemPrompt ?: '',
        'rate_limit_requests' => $rateReq ?: 100,
        'rate_limit_tokens' => $rateTok ?: 50000,
        'cache_ttl' => $cacheTtl ?: 3600,
        'enable_cache' => (bool) $enableCache,
        'enable_analytics' => (bool) $enableAnalytics,
        'stream_session_ttl' => $streamTtl ?: 300,
    ];
}

/**
 * Check if AI service is properly configured.
 *
 * @return bool True if configured, false otherwise
 */
function local_gis_ai_assistant_is_configured() {
    $config = local_gis_ai_assistant_get_config();
    // Require credentials (ENV) and enabled flag (DB).
    return !empty($config['api_key']) && !empty($config['base_url']) && !empty($config['model']) && !empty($config['enabled']);
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
    
    // Normalize line endings and preserve newlines while trimming excessive spaces/tabs.
    $input = str_replace(["\r\n", "\r"], "\n", $input);
    // Collapse runs of spaces/tabs but keep newlines as-is.
    $input = preg_replace('/[ \t\x0B\f]+/', ' ', $input);
    // Trim trailing spaces at end of lines.
    $input = preg_replace('/[ \t]+\n/', "\n", $input);
    // Reduce 3+ blank lines to max 2 in a row.
    $input = preg_replace("/\n{3,}/", "\n\n", $input);
    $input = trim($input);
    
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
    
    // Always persist conversations so history is available across views and reloads.
    
    $record = new stdClass();
    $record->userid = (int)$userid;
    // Ensure model is a string and fits char(50).
    $record->model = substr((string)$model, 0, 50);
    // Store required fields according to install.xml schema.
    $record->message = (string)$message;
    $record->response = (string)$response;
    $record->tokens_used = isset($usage['total_tokens']) ? (int)$usage['total_tokens'] : 0;
    $record->response_time = (int)round((float)$responsetime);
    $record->timecreated = (int)time();
    
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
    
    $windowstart = (int)(floor(time() / 3600) * 3600); // Start of current hour.
    
    // Determine caller IP (fits char(45), supports IPv6). Fallback to placeholder.
    $ip = function_exists('getremoteaddr') ? (string)getremoteaddr() : '';
    if ($ip === '' || $ip === false) { $ip = '0.0.0.0'; }
    
    // Get or create rate limit record.
    $record = $DB->get_record('local_gis_ai_assistant_rate_limits', [
        'userid' => (int)$userid,
        'window_start' => (int)$windowstart
    ]);
    
    if (!$record) {
        // Build insert record using existing columns only (handles older schemas gracefully).
        $columns = $DB->get_columns('local_gis_ai_assistant_rate_limits');
        $newrec = new stdClass();
        if (isset($columns['userid']))        { $newrec->userid = (int)$userid; }
        if (isset($columns['ip_address']))    { $newrec->ip_address = substr($ip, 0, 45); }
        if (isset($columns['requests_count'])){ $newrec->requests_count = (int)0; }
        if (isset($columns['tokens_count']))  { $newrec->tokens_count = (int)0; }
        if (isset($columns['window_start']))  { $newrec->window_start = (int)$windowstart; }
        if (isset($columns['timemodified']))  { $newrec->timemodified = (int)time(); }
        $newrec->id = $DB->insert_record('local_gis_ai_assistant_rate_limits', $newrec);
        $record = $newrec;
    }
    
    // Check limits.
    $newrequests = (int)$record->requests_count + 1;
    $newtokens = (int)$record->tokens_count + (int)$tokens;
    
    if ($newrequests > $maxrequests || $newtokens > $maxtokens) {
        return [
            'allowed' => false,
            'reset_time' => (int)($windowstart + 3600),
            'requests_remaining' => (int)max(0, (int)$maxrequests - (int)$record->requests_count),
            'tokens_remaining' => (int)max(0, (int)$maxtokens - (int)$record->tokens_count)
        ];
    }
    
    return [
        'allowed' => true,
        'reset_time' => (int)($windowstart + 3600),
        'requests_remaining' => (int)((int)$maxrequests - (int)$newrequests),
        'tokens_remaining' => (int)((int)$maxtokens - (int)$newtokens)
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
    
    $windowstart = (int)(floor(time() / 3600) * 3600);
    
    $record = $DB->get_record('local_gis_ai_assistant_rate_limits', [
        'userid' => (int)$userid,
        'window_start' => (int)$windowstart
    ]);
    
    if ($record) {
        $columns = $DB->get_columns('local_gis_ai_assistant_rate_limits');
        if (isset($columns['requests_count'])) {
            $record->requests_count = (int)$record->requests_count + 1;
        }
        if (isset($columns['tokens_count'])) {
            $record->tokens_count = (int)$record->tokens_count + (int)$tokens;
        }
        if (isset($columns['timemodified'])) {
            $record->timemodified = (int)time();
        }
        $DB->update_record('local_gis_ai_assistant_rate_limits', $record);
    } else {
        // Create if missing (e.g., first call in this window via alternate path).
        $ip = function_exists('getremoteaddr') ? (string)getremoteaddr() : '';
        if ($ip === '' || $ip === false) { $ip = '0.0.0.0'; }
        $columns = $DB->get_columns('local_gis_ai_assistant_rate_limits');
        $new = new stdClass();
        if (isset($columns['userid']))         { $new->userid = (int)$userid; }
        if (isset($columns['ip_address']))     { $new->ip_address = substr($ip, 0, 45); }
        if (isset($columns['requests_count'])) { $new->requests_count = 1; }
        if (isset($columns['tokens_count']))   { $new->tokens_count = (int)$tokens; }
        if (isset($columns['window_start']))   { $new->window_start = (int)$windowstart; }
        if (isset($columns['timemodified']))   { $new->timemodified = (int)time(); }
        $DB->insert_record('local_gis_ai_assistant_rate_limits', $new);
    }
}
