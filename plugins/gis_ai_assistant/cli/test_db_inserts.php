<?php
// Minimal DB insert tests for the AI plugin.
// Usage:
//   /opt/bitnami/php/bin/php /bitnami/moodle/local/gis_ai_assistant/cli/test_db_inserts.php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/gis_ai_assistant/lib.php');

// Run as admin to ensure capability and valid user ID.
$USER = get_admin();

function print_rec($title, $rec) {
    echo $title . ": " . json_encode($rec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

// 1) Rate limit record creation
try {
    echo "== Rate limit check ==\n";
    $status = local_gis_ai_assistant_check_rate_limit($USER->id, 12);
    print_rec('check_rate_limit', $status);
    echo "OK: local_gis_ai_assistant_check_rate_limit()\n\n";
} catch (Throwable $e) {
    echo "ERROR in check_rate_limit: " . $e->getMessage() . "\n";
    if (isset($e->debuginfo) && $e->debuginfo) { echo "Debug: " . $e->debuginfo . "\n"; }
    echo "Trace (top):\n" . implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 10)) . "\n";
    exit(1);
}

// 2) Log a conversation row
try {
    echo "== Conversation insert ==\n";
    $usage = ['total_tokens' => 34];
    local_gis_ai_assistant_log_request($USER->id, 'adorsys', $usage, 123, 'success', null, 'test message', 'test response');
    echo "OK: local_gis_ai_assistant_log_request()\n\n";
} catch (Throwable $e) {
    echo "ERROR in log_request: " . $e->getMessage() . "\n";
    if (isset($e->debuginfo) && $e->debuginfo) { echo "Debug: " . $e->debuginfo . "\n"; }
    echo "Trace (top):\n" . implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 10)) . "\n";
    exit(1);
}

echo "All DB insert tests passed.\n";
