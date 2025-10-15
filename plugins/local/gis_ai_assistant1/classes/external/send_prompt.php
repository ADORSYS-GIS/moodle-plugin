<?php
declare(strict_types=1);

namespace local_gis_ai_assistant1\external;

defined('MOODLE_INTERNAL') || die();

use local_gis_ai_assistant1\api\rust_bridge;
use local_gis_ai_assistant1\api\response_handler;
use local_gis_ai_assistant1\helpers\logger;
use local_gis_ai_assistant1\analytics\usage_tracker;
// External API base definitions are in global namespace; use fully-qualified names below.

require_once($CFG->libdir . '/externallib.php');

class send_prompt extends base_external {
    /**
     * Define parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'prompt'  => new \external_value(PARAM_TEXT, 'User prompt', VALUE_REQUIRED),
            'options' => new \external_value(PARAM_RAW, 'JSON options', VALUE_DEFAULT, '{}'),
            'stream'  => new \external_value(PARAM_BOOL, 'Enable streaming', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param string $prompt
     * @param string $options
     * @param bool $stream
     * @return array
     */
    public static function execute(string $prompt, string $options, bool $stream): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'prompt'  => $prompt,
            'options' => $options,
            'stream'  => $stream,
        ]);

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('local/gis_ai_assistant1:use', $context);

        if (trim($params['prompt']) === '') {
            throw new \moodle_exception('invalidprompt', 'local_gis_ai_assistant1');
        }

        try {
            $opts = json_decode($params['options'], true) ?? [];
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \moodle_exception('invalidjson', 'local_gis_ai_assistant1');
            }

            $raw = rust_bridge::send_prompt($params['prompt'], $USER->email, $opts);
            $processed = response_handler::process($raw);
            // Log successful interaction (anonymised prompt).
            usage_tracker::log_interaction((int)$USER->id, (string)$params['prompt'], $processed, true, (int)$context->id, 0);
            return self::success([
                'content' => $processed['content'],
                'tokens'  => $processed['tokens'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            // Log failed interaction.
            try { usage_tracker::log_interaction((int)$USER->id, (string)$params['prompt'], ['tokens' => 0], false, (int)$context->id, 0); } catch (\Throwable $ignored) {}
            self::handle_exception($e, 'sendpromptfailed');
        }
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
            'content' => new \external_value(PARAM_RAW, 'AI response'),
            'tokens'  => new \external_value(PARAM_INT, 'Token count', VALUE_OPTIONAL),
        ]);
    }
}
