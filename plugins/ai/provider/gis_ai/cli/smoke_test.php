<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use aiprovider_gis_ai\provider as gis_provider;
use aiprovider_gis_ai\interfaces\api_interface;
use aiplacement_gis_ai_chat\external\send as chat_send_external;

list($options, $unrecognized) = cli_get_params([
    'run-action' => false,
    'prompt' => 'Hello from GIS AI smoke test',
    'help' => false,
], [
    'r' => 'run-action',
    'p' => 'prompt',
    'h' => 'help',
]);

if (!empty($options['help'])) {
    $help = <<<HELP
Moodle GIS AI Provider smoke test

Options:
  -r, --run-action         Attempt to run core_ai generate_text with admin user
  -p, --prompt="text"      Prompt text to use when running the action
  -h, --help               Show this help

Examples:
  php ai/provider/gis_ai/cli/smoke_test.php
  php ai/provider/gis_ai/cli/smoke_test.php --run-action --prompt="Say hello"
HELP;
    echo $help, PHP_EOL;
    exit(0);
}

$failures = 0;

function status(string $label, bool $ok, ?string $extra = null): void {
    $mark = $ok ? '[OK] ' : '[FAIL] ';
    echo $mark . $label . ($extra ? (': ' . $extra) : '') . PHP_EOL;
}

// 1) Provider configured + healthcheck.
try {
    $provider = new gis_provider();
    $configured = $provider->is_provider_configured();
    status('Provider is configured (env OPENAI_API_KEY)', $configured);
    if (!$configured) { $failures++; }

    $health = $provider->healthcheck();
    $ok = (bool)($health['ok'] ?? false);
    $msg = (string)($health['message'] ?? '');
    status('Provider healthcheck', $ok, $msg);
    if (!$ok) { $failures++; }
} catch (Throwable $e) {
    status('Provider checks', false, $e->getMessage());
    $failures++;
}

// 2) Interface implementation checks.
try {
    $httpok = false;
    $rustok = false;
    if (class_exists(\aiprovider_gis_ai\api\http_client::class)) {
        $http = new \aiprovider_gis_ai\api\http_client();
        $httpok = ($http instanceof api_interface);
    }
    status('http_client implements api_interface', $httpok);
    if (!$httpok) { $failures++; }

    if (class_exists(\aiprovider_gis_ai\api\rust_bridge::class)) {
        $rust = new \aiprovider_gis_ai\api\rust_bridge();
        $rustok = ($rust instanceof api_interface);
    }
    status('rust_bridge implements api_interface', $rustok);
    if (!$rustok) { $failures++; }
} catch (Throwable $e) {
    status('Interface checks', false, $e->getMessage());
    $failures++;
}

// 3) Actions available.
try {
    $actions = $provider->get_action_list();
    $hasgenerate = in_array(\core_ai\aiactions\generate_text::class, $actions, true);
    status('Provider exposes generate_text action', $hasgenerate, 'count=' . count($actions));
    if (!$hasgenerate) { $failures++; }
} catch (Throwable $e) {
    status('Provider action list', false, $e->getMessage());
    $failures++;
}

// 4) Placement external function presence (registration code exists).
try {
    $classexists = class_exists(chat_send_external::class);
    $hasexecute = $classexists && method_exists(chat_send_external::class, 'execute');
    status('Placement external exists (aiplacement_gis_ai_chat_send)', $hasexecute);
    if (!$hasexecute) { $failures++; }
} catch (Throwable $e) {
    status('Placement external presence', false, $e->getMessage());
    $failures++;
}

// 5) Optional: run core_ai generate_text to verify end-to-end processing.
if (!empty($options['run-action'])) {
    try {
        // Prepare admin session.
        \core\session\manager::init_empty_session();
        $admin = get_admin();
        if (!$admin) { throw new \moodle_exception('noadmins'); }
        \core\session\manager::set_user($admin);

        $sysctx = \context_system::instance();
        $prompt = (string)$options['prompt'];
        $action = new \core_ai\aiactions\generate_text(
            contextid: $sysctx->id,
            userid: (int)$admin->id,
            prompttext: $prompt
        );
        /** @var \core_ai\manager $manager */
        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);

        $snippet = '';
        if (is_object($response)) {
            if (method_exists($response, 'get_text')) {
                $snippet = (string)$response->get_text();
            } elseif (method_exists($response, '__toString')) {
                $snippet = (string)$response;
            } else {
                $snippet = 'Response class: ' . get_class($response);
            }
        } else {
            $snippet = 'Non-object response type: ' . gettype($response);
        }
        $snippet = mb_substr((string)$snippet, 0, 160);
        status('generate_text executed', true, $snippet);
    } catch (Throwable $e) {
        status('generate_text executed', false, $e->getMessage());
        $failures++;
    }
}

if ($failures > 0) {
    echo 'Smoke test completed with failures: ' . $failures . PHP_EOL;
    exit(1);
}

echo 'Smoke test completed successfully.' . PHP_EOL;
exit(0);
