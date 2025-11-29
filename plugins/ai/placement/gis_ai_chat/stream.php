<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

define('NO_MOODLE_COOKIES', true); // No cookies for SSE
define('NO_UPGRADE_CHECK', true);  // No upgrade checks for performance

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');

use core\session\manager;
use aiprovider_gis_ai\helpers\logger;

// Stream ID parameter is required
$streamid = required_param('id', PARAM_ALPHANUMEXT);

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

// Prevent output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Get stream cache
$cache = \cache::make('aiplacement_gis_ai_chat', 'streams');

// Function to send SSE message
function send_sse_message($type, $data = null) {
    $message = "data: " . json_encode(['type' => $type, 'content' => $data]) . "\n\n";
    echo $message;
    flush();
}

// Function to send SSE comment (heartbeat)
function send_heartbeat() {
    echo ": heartbeat\n\n";
    flush();
}

try {
    // Get stream data
    $streamData = $cache->get($streamid);
    
    if (!$streamData) {
        send_sse_message('error', 'Stream not found or expired');
        exit;
    }

    // Validate stream ownership
    if (empty($streamData['userid']) || $streamData['userid'] != $USER->id) {
        send_sse_message('error', 'Access denied');
        exit;
    }

    // Send initial connection message
    send_sse_message('connected', ['streamid' => $streamid]);

    // Stream processing loop
    $startTime = time();
    $timeout = 300; // 5 minutes timeout
    $heartbeatInterval = 15; // Heartbeat every 15 seconds
    $lastHeartbeat = time();

    while (connection_aborted() === false && (time() - $startTime) < $timeout) {
        // Refresh stream data from cache
        $streamData = $cache->get($streamid);
        
        if (!$streamData) {
            send_sse_message('error', 'Stream lost');
            break;
        }

        // Check if stream is completed
        if ($streamData['status'] === 'completed') {
            if (!empty($streamData['content'])) {
                // Stream the complete content in chunks for better UX
                $content = $streamData['content'];
                $chunkSize = 50; // Characters per chunk
                $contentLength = strlen($content);
                
                for ($i = 0; $i < $contentLength; $i += $chunkSize) {
                    $chunk = substr($content, $i, $chunkSize);
                    send_sse_message('chunk', $chunk);
                    
                    // Small delay between chunks for streaming effect
                    usleep(50000); // 50ms
                }
            }
            send_sse_message('done');
            break;
        }

        // Check for errors
        if ($streamData['status'] === 'error') {
            $errorMessage = $streamData['error'] ?? 'Unknown error occurred';
            send_sse_message('error', $errorMessage);
            break;
        }

        // Check for partial content (if streaming is active)
        if ($streamData['status'] === 'streaming' && !empty($streamData['partial_content'])) {
            send_sse_message('chunk', $streamData['partial_content']);
            // Clear partial content after sending
            $streamData['partial_content'] = '';
            $cache->set($streamid, $streamData);
        }

        // Send heartbeat to keep connection alive
        if (time() - $lastHeartbeat >= $heartbeatInterval) {
            send_heartbeat();
            $lastHeartbeat = time();
        }

        // Sleep to prevent busy waiting
        sleep(1);
    }

    // Cleanup expired stream after timeout
    if (time() - $startTime >= $timeout) {
        $cache->delete($streamid);
        send_sse_message('error', 'Stream timeout');
    }

} catch (\Throwable $e) {
    logger::exception($e, 'Streaming endpoint error');
    send_sse_message('error', 'Internal server error');
}

// Ensure connection is properly closed
if (connection_aborted() === false) {
    send_sse_message('close');
}
