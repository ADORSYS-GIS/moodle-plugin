<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

final class get_analytics extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'date_from' => new external_value(PARAM_INT, 'From timestamp', VALUE_OPTIONAL),
            'date_to'   => new external_value(PARAM_INT, 'To timestamp', VALUE_OPTIONAL),
            'user_id'   => new external_value(PARAM_INT, 'Filter by user id', VALUE_OPTIONAL),
            'limit'     => new external_value(PARAM_INT, 'Limit rows for paged_rows', VALUE_OPTIONAL),
            'offset'    => new external_value(PARAM_INT, 'Offset for paged_rows', VALUE_OPTIONAL),
        ]);
    }

    public static function execute($date_from = null, $date_to = null, $user_id = null, $limit = null, $offset = null): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'user_id'   => $user_id,
            'limit'     => $limit,
            'offset'    => $offset,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('aiprovider/gis_ai:viewanalytics', $context);

        $rows = \aiprovider_gis_ai\analytics\data_aggregator::aggregate($params);

        return ['rows' => array_values($rows)];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'rows' => new external_multiple_structure(new external_single_structure([
                'metric' => new external_value(PARAM_TEXT, 'Metric name'),
                'value'  => new external_value(PARAM_RAW, 'Metric value'),
            ])),
        ]);
    }
}
