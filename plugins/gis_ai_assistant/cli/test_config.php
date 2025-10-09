<?php
// CLI script to verify AI plugin configuration and connectivity.
// Usage (inside container):
//   /opt/bitnami/php/bin/php /bitnami/moodle/local/gis_ai_assistant/cli/test_config.php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/gis_ai_assistant/lib.php');
require_once($CFG->dirroot . '/local/gis_ai_assistant/classes/api/inference_service.php');

// Ensure we have a user context for rate limiting and logging.
global $USER;
$USER = get_admin();

function mask($value, $show = 4) {
    if (!$value) { return '<empty>'; }
    $len = strlen($value);
    if ($len <= $show) { return str_repeat('*', max(0, $len - 1)) . substr($value, -1); }
    return str_repeat('*', max(0, $len - $show)) . substr($value, -$show);
}

// 1) Check environment variables directly
$env = [
    'OPENAI_API_KEY'  => getenv('OPENAI_API_KEY') ?: ($_SERVER['OPENAI_API_KEY'] ?? null),
    'OPENAI_BASE_URL' => getenv('OPENAI_BASE_URL') ?: ($_SERVER['OPENAI_BASE_URL'] ?? null),
    'OPENAI_MODEL'    => getenv('OPENAI_MODEL') ?: ($_SERVER['OPENAI_MODEL'] ?? null),
];

echo "== Environment variables ==\n";
echo "OPENAI_API_KEY  : " . mask($env['OPENAI_API_KEY']) . "\n";
echo "OPENAI_BASE_URL : " . ($env['OPENAI_BASE_URL'] ?: '<empty>') . "\n";
echo "OPENAI_MODEL    : " . ($env['OPENAI_MODEL'] ?: '<empty>') . "\n\n";

// 2) Check plugin-level resolved config
$cfg = local_gis_ai_assistant_get_config();
echo "== Plugin resolved config (post-merge) ==\n";
echo "enabled          : " . (int)$cfg['enabled'] . "\n";
echo "base_url         : " . $cfg['base_url'] . "\n";
echo "model            : " . $cfg['model'] . "\n";
echo "max_tokens       : " . (int)$cfg['max_tokens'] . "\n";
echo "temperature      : " . (float)$cfg['temperature'] . "\n";
echo "enable_cache     : " . (int)$cfg['enable_cache'] . "\n";
echo "enable_analytics : " . (int)$cfg['enable_analytics'] . "\n";
echo "stream_session_ttl: " . (int)$cfg['stream_session_ttl'] . "\n\n";

echo "== Configuration sanity ==\n";
echo "local_gis_ai_assistant_is_configured(): " . (local_gis_ai_assistant_is_configured() ? 'YES' : 'NO') . "\n\n";

// 3) Make a small test call via the inference service
try {
    echo "== Test API call (chat_completion) ==\n";
    $svc = new \local_gis_ai_assistant\api\inference_service();
    $result = $svc->chat_completion('Say hello from Moodle test script', [
        'max_tokens' => 32,
        'temperature' => 0,
    ]);
    echo "Status: OK\n";
    echo "Model : " . ($result['model'] ?? '') . "\n";
    echo "Finish: " . ($result['finish_reason'] ?? '') . "\n";
    echo "Usage : " . json_encode($result['usage'] ?? []) . "\n";
    echo "Content (truncated) :\n";
    $content = (string)($result['content'] ?? '');
    echo substr($content, 0, 500) . (strlen($content) > 500 ? "...\n" : "\n");
} catch (\Throwable $e) {
    echo "Status: ERROR\n";
    echo "Message: " . $e->getMessage() . "\n";
    if (isset($e->debuginfo) && $e->debuginfo) {
        echo "Debug  : " . $e->debuginfo . "\n";
    }
    echo "Trace (top):\n";
    $trace = $e->getTraceAsString();
    echo implode("\n", array_slice(explode("\n", $trace), 0, 10)) . "\n";
}
