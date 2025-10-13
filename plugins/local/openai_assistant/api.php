<?php
// Disable all output buffering and error display
define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/api_client.php');

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');

// Disable all error output to prevent HTML in response
ini_set('display_errors', '0');
error_reporting(0);

// Function to send JSON and exit
function send_json($data) {
    echo json_encode($data);
    exit;
}

try {
    // Check if user is logged in
    if (!isloggedin() || isguestuser()) {
        send_json([
            'success' => false,
            'error' => 'Authentication required'
        ]);
    }
    
    // Get POST data
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_json([
            'success' => false,
            'error' => 'Invalid JSON: ' . json_last_error_msg()
        ]);
    }
    
    // Validate session key (accept from JSON body or GET query as fallback)
    $sess = null;
    if (isset($input['sesskey'])) {
        $sess = $input['sesskey'];
    } else if (isset($_GET['sesskey'])) {
        $sess = $_GET['sesskey'];
    }

    if (!$sess || !confirm_sesskey($sess)) {
        send_json([
            'success' => false,
            'error' => 'Invalid session key'
        ]);
    }
    
    // Validate input
    if (!isset($input['action']) || !isset($input['message'])) {
        send_json([
            'success' => false,
            'error' => 'Missing required parameters'
        ]);
    }
    
    $action = clean_param($input['action'], PARAM_ALPHA);
    $message = clean_param($input['message'], PARAM_TEXT);
    
    if (empty($message)) {
        send_json([
            'success' => false,
            'error' => 'Message cannot be empty'
        ]);
    }
    
    // Create API client
    $client = new \local_openai_assistant\api_client();
    
    // Process request
    switch ($action) {
        case 'chat':
            $response = $client->chat($message, null, $USER->id);
            break;
        case 'summarize':
            $response = $client->summarize($message, $USER->id);
            break;
        case 'analyze':
            $response = $client->analyze($message, $USER->id);
            break;
        default:
            send_json([
                'success' => false,
                'error' => 'Invalid action: ' . $action
            ]);
    }
    
    send_json($response);
    
} catch (Exception $e) {
    send_json([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
