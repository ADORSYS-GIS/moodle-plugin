<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\analytics;

defined('MOODLE_INTERNAL') || die();

final class report_generator {
    /**
     * Generate analytics dataset (purge according to retention, then aggregate).
     * Returns a flat metrics array for compatibility with externals and UI.
     *
     * @param array $filters
     * @return array<int,array{metric:string,value:mixed}>
     */
    public static function generate(array $filters = []): array {
        usage_tracker::purge_old();
        return data_aggregator::aggregate($filters);
    }
}
