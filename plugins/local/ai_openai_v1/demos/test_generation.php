<?php
define('CLI_SCRIPT', true);
require(__DIR__.'/../../../config.php');

echo "--- AI Provider Test Script ---\n";

global $CFG, $USER;
$USER = get_admin();

try {
    echo "1. Getting AI manager instance...\n";
    $manager = \core\di::get(\core_ai\manager::class);

    echo "2. Creating 'generate_text' action...\n";
    $prompt = "Write a short, three-line poem about coding.";
    $action = new \core_ai\aiactions\generate_text(
        contextid: context_system::instance()->id,
        userid: $USER->id,
        prompttext: $prompt
    );

    echo "3. Processing action (PHP -> Rust -> OpenAI -> Rust -> PHP)...\n";
    $response = $manager->process_action($action);

    echo "4. Checking response...\n";

    // Use the CORRECT method names from the debug output
    if ($response->get_success()) {
        echo "\nSUCCESS!\n";
        echo "========================================\n";
        echo "Prompt: {$prompt}\n";
        echo "----------------------------------------\n";
        echo "Response data:\n";
        var_dump($response->get_response_data());
        echo "========================================\n";
    } else {
        echo "\nFAILURE!\n";
        echo "Error: " . $response->get_errormessage() . "\n";
        echo "Error code: " . $response->get_errorcode() . "\n";
    }

} catch (Exception $e) {
    echo "\nFATAL ERROR!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n--- Test Complete ---\n";