<?php
// CLI script to exercise the external function path (same as the UI uses).
// Usage:
//   /opt/bitnami/php/bin/php /bitnami/moodle/local/gis_ai_assistant/cli/test_ajax_send.php "Your message here"

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/gis_ai_assistant/classes/external/chat_api.php');

// Use admin for capability/rate-limiting context in CLI.
global $USER;
$USER = get_admin();

$message = $argv[1] ?? 'Hello from AJAX external test!';

try {
    $res = \local_gis_ai_assistant\external\chat_api::send_message($message, '', 0.7, 0);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Exception: " . $e->getMessage() . "\n");
    if (isset($e->debuginfo) && $e->debuginfo) {
        fwrite(STDERR, "Debug: " . $e->debuginfo . "\n");
    }
    exit(1);
}
