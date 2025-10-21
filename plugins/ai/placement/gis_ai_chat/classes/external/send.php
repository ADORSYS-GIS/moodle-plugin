<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiplacement_gis_ai_chat\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * External function to send a chat prompt and process it via the AI manager.
 */
final class send extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context ID', VALUE_REQUIRED),
            'prompttext' => new external_value(PARAM_TEXT, 'Prompt text', VALUE_REQUIRED),
        ]);
    }

    public static function execute(int $contextid, string $prompttext): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'prompttext' => $prompttext,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('aiplacement/gis_ai_chat:generate_text', $context);

        try {
            // Build the action and process via manager.
            $action = new \core_ai\aiactions\generate_text(
                contextid: $params['contextid'],
                userid: (int)$USER->id,
                prompttext: $params['prompttext']
            );
            /** @var \core_ai\manager $manager */
            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            // Best-effort content extraction without hard-coding response internals.
            $content = null;
            if (is_object($response)) {
                if (method_exists($response, 'get_text')) {
                    $content = (string)$response->get_text();
                } elseif (method_exists($response, '__toString')) {
                    $content = (string)$response;
                }
            }

            return [
                'ok' => true,
                'content' => $content ?? '',
                'responseclass' => is_object($response) ? get_class($response) : gettype($response),
            ];
        } catch (\Throwable $e) {
            // Do not leak internals by default.
            return [
                'ok' => false,
                'error' => get_string('processingfailed', 'aiplacement_gis_ai_chat'),
            ];
        }
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Whether the request was successful'),
            'content' => new external_value(PARAM_RAW, 'AI generated content if available', VALUE_DEFAULT, ''),
            'responseclass' => new external_value(PARAM_TEXT, 'Response class name', VALUE_DEFAULT, ''),
            'error' => new external_value(PARAM_TEXT, 'Error message if any', VALUE_DEFAULT, ''),
        ]);
    }
}
