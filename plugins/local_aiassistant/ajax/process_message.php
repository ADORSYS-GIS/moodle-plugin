<?php
define('AJAX_SCRIPT', true); // Tells Moodle this is an AJAX endpoint.
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/aiassistant/classes/rust_process_manager.php');

// Standard Moodle security checks.
require_login();
require_sesskey();

// Use the capability we defined in access.php to protect this endpoint.
if (!has_capability('local/aiassistant:use', context_system::instance())) {
    http_response_code(403); // Forbidden
    die(json_encode(['success' => false, 'error' => 'Access denied.']));
}

header('Content-Type: application/json'); // Set the response type to JSON.

// --- Safely get parameters from the AJAX request ---
$message = required_param('message', PARAM_TEXT);
$context_json = optional_param('context', '{}', PARAM_RAW);
$context = json_decode($context_json, true);
// ... (parameter validation for history, etc.)

try {
    // Get the singleton instance of our process manager.
    $manager = \local_aiassistant\rust_process_manager::get_instance();
    
    // Call the manager to process the request.
    $response = $manager->process_request(
        $USER->id,
        $message,
        $context,
        [], // Placeholder for conversation history
        null // Placeholder for large text
    );

    // Check if the Rust process returned an error.
    if (!empty($response['error'])) {
        throw new \Exception($response['error']);
    }

    // Send a successful, structured response back to the JavaScript.
    echo json_encode([
        'success' => true,
        'ai_response_text' => $response['ai_response_text'],
    ]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    debugging('AI Assistant: Fatal error in AJAX handler: ' . $e->getMessage(), DEBUG_CRITICAL);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}