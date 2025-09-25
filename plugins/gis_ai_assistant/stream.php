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
 * Streaming endpoint for AI responses.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_gis_ai_assistant\api\inference_service;
use local_gis_ai_assistant\exceptions\ai_exception;

require_login();

$context = context_system::instance();
require_capability('local/gis_ai_assistant:use', $context);

$sessionid = required_param('session', PARAM_ALPHANUMEXT);

// Set headers for Server-Sent Events.
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
// CORS headers are not required for same-origin SSE and can be risky for authenticated endpoints.

// Disable output buffering.
if (ob_get_level()) {
    ob_end_clean();
}

// Function to send SSE data.
function send_sse_data($data) {
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

try {
    // Get session data from cache.
    $cache = cache::make('local_gis_ai_assistant', 'stream_sessions');
    $sessiondata = $cache->get('stream_' . $sessionid);
    
    if (!$sessiondata) {
        send_sse_data(['type' => 'error', 'error' => 'Invalid or expired session']);
        exit;
    }
    
    // Verify user.
    if ($sessiondata['userid'] != $USER->id) {
        send_sse_data(['type' => 'error', 'error' => 'Unauthorized']);
        exit;
    }
    
    // Check if session is too old (configured TTL in seconds).
    $ttl = (int) get_config('local_gis_ai_assistant', 'stream_session_ttl');
    if ($ttl <= 0) {
        $ttl = 300; // Fallback default.
    }
    if (time() - $sessiondata['created'] > $ttl) {
        send_sse_data(['type' => 'error', 'error' => 'Session expired']);
        exit;
    }
    
    // Create inference service.
    $service = new inference_service();
    
    // Callback function for streaming chunks.
    $callback = function($content) {
        send_sse_data(['type' => 'content', 'content' => $content]);
    };
    
    // Make streaming request.
    $result = $service->chat_completion_stream(
        $sessiondata['message'],
        $callback,
        $sessiondata['options']
    );
    
    // Send completion data.
    send_sse_data([
        'type' => 'done',
        'usage' => $result['usage'] ?? [],
        'finish_reason' => $result['finish_reason'] ?? ''
    ]);
    
    // Clean up session data.
    $cache->delete('stream_' . $sessionid);
    
} catch (ai_exception $e) {
    send_sse_data(['type' => 'error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    send_sse_data(['type' => 'error', 'error' => 'An unexpected error occurred']);
    error_log('AI streaming error: ' . $e->getMessage());
}

exit;
