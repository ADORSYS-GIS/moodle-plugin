<?php
declare(strict_types=1);

namespace local_gis_ai_assistant1\analytics;

defined('MOODLE_INTERNAL') || die();

use local_gis_ai_assistant1\helpers\logger;

final class data_aggregator {
    /**
     * Aggregate metrics from gis_ai_logs with optional filters.
     *
     * @param array $filters
     * @return array<int,array{metric:string,value:mixed}>
     */
    public static function aggregate(array $filters = []): array {
        global $DB;

        // Normalize pagination inputs.
        $limit = isset($filters['limit']) ? max(0, (int)$filters['limit']) : 0;
        $offset = isset($filters['offset']) ? max(0, (int)$filters['offset']) : 0;

        // Build conditions and params.
        $conditions = [];
        $params = [];
        if (!empty($filters['date_from'])) {
            $conditions[] = 'timestamp >= :date_from';
            $params['date_from'] = (int)$filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'timestamp <= :date_to';
            $params['date_to'] = (int)$filters['date_to'];
        }
        if (!empty($filters['user_id'])) {
            $conditions[] = 'userid = :user_id';
            $params['user_id'] = (int)$filters['user_id'];
        }

        $where = $conditions ? implode(' AND ', $conditions) : '1=1';

        // Cache small results briefly.
        $cache = \cache::make('local_gis_ai_assistant1', 'analytics');
        $cachekey = 'agg:' . md5(json_encode([$filters, $limit, $offset]));
        $cached = $cache->get($cachekey);
        if ($cached) {
            return $cached;
        }

        try {
            $totalRequests = (int)$DB->count_records_select('gis_ai_logs', $where, $params);
            $uniqueUsers = (int)($DB->get_field_sql("SELECT COUNT(DISTINCT userid) FROM {gis_ai_logs} WHERE $where", $params) ?? 0);
            $totalTokens = (int)($DB->get_field_select('gis_ai_logs', 'SUM(tokens)', $where, $params) ?? 0);
            $avgTokens = $totalRequests > 0 ? round($totalTokens / max(1, $totalRequests), 2) : 0.0;
            $successRate = (float)($DB->get_field_select('gis_ai_logs', 'AVG(status)', $where, $params) ? : 0.0);
            $successRate = round($successRate * 100, 2);

            $pagedrows = [];
            if ($limit > 0) {
                $sql = "SELECT id, userid, timestamp, prompt_hash, tokens, status, contextid, courseid
                          FROM {gis_ai_logs}
                         WHERE $where
                      ORDER BY timestamp DESC";
                $pagedrows = array_values($DB->get_records_sql($sql, $params, $offset, $limit));
            }

            $result = [
                ['metric' => 'total_requests', 'value' => $totalRequests],
                ['metric' => 'unique_users', 'value' => $uniqueUsers],
                ['metric' => 'total_tokens', 'value' => $totalTokens],
                ['metric' => 'avg_tokens_per_request', 'value' => $avgTokens],
                ['metric' => 'success_rate', 'value' => $successRate . '%'],
            ];
            if ($limit > 0) {
                $result[] = ['metric' => 'paged_rows', 'value' => $pagedrows];
            }

            $cache->set($cachekey, $result);
            return $result;
        } catch (\Throwable $e) {
            logger::exception($e, 'aggregationfailed');
            return [];
        }
    }
}
