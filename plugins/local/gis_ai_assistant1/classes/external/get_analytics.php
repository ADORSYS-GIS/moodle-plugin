<?php
declare(strict_types=1);

namespace local_gis_ai_assistant1\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use moodle_exception;
use context_system;
use local_gis_ai_assistant1\helpers\logger;

require_once($CFG->libdir . '/externallib.php');

class get_analytics extends base_external {
    /**
     * Parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filters' => new external_value(PARAM_RAW, 'JSON filters', VALUE_DEFAULT, '{}'),
        ]);
    }

    /**
     * Execute.
     *
     * @param string $filters
     * @return array
     */
    public static function execute(string $filters): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), [
            'filters' => $filters,
        ]);

        try {
            $decoded = json_decode($params['filters'], true) ?? [];
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new moodle_exception('invalidjson', 'local_gis_ai_assistant1');
            }

            $context = context_system::instance();
            self::validate_context($context);

            $isadmin = has_capability('local/gis_ai_assistant1:viewanalytics', $context);
            if (!$isadmin) {
                $canown = has_capability('local/gis_ai_assistant1:viewownanalytics', $context);
                if ($canown) {
                    if (empty($decoded['user_id'])) {
                        $decoded['user_id'] = (int)$USER->id; // default to self when not provided
                    }
                    if ((int)$decoded['user_id'] !== (int)$USER->id) {
                        // Not self; require full analytics capability (throws exception)
                        require_capability('local/gis_ai_assistant1:viewanalytics', $context);
                    }
                } else {
                    // No permission for analytics
                    require_capability('local/gis_ai_assistant1:viewanalytics', $context);
                }
            }

            $data = [];
            $reportclass = '\\local_gis_ai_assistant1\\analytics\\report_generator';
            if (class_exists($reportclass)) {
                /** @var class-string $reportclass */
                $data = $reportclass::generate($decoded);
            }

            return self::success(['data' => $data]);
        } catch (\\Throwable $e) {
            self::handle_exception($e, 'getanalyticsfailed');
        }
    }

    /**
     * Returns.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success'),
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'metric' => new external_value(PARAM_TEXT, 'Metric name'),
                    'value'  => new external_value(PARAM_RAW, 'Metric value'),
                ]),
                'Analytics rows', VALUE_DEFAULT, []
            ),
        ]);
    }
}
