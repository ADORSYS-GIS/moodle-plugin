<?php
declare(strict_types=1);

namespace local_gis_ai_assistant1\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use moodle_exception;
use context_user;
use local_gis_ai_assistant1\helpers\logger;

require_once($CFG->libdir . '/externallib.php');

class get_response extends base_external {
    /**
     * Define parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'requestid' => new external_value(PARAM_ALPHANUMEXT, 'Request ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute.
     *
     * @param string $requestid
     * @return array
     */
    public static function execute(string $requestid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'requestid' => $requestid,
        ]);

        $context = context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('local/gis_ai_assistant1:use', $context);

        try {
            // Placeholder: replace with actual async retrieval.
            $content = 'Pending/placeholder response for ' . $params['requestid'];
            return self::success(['content' => $content]);
        } catch (\Throwable $e) {
            self::handle_exception($e, 'getresponsefailed');
        }
    }

    /**
     * Returns.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success'),
            'content' => new external_value(PARAM_RAW, 'AI response content'),
        ]);
    }
}
