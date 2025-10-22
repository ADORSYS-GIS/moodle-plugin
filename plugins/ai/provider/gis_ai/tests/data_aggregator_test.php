<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

use aiprovider_gis_ai\analytics\data_aggregator;

defined('MOODLE_INTERNAL') || die();

final class aiprovider_gis_ai_data_aggregator_test extends \advanced_testcase {
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    public function test_aggregate_metrics(): void {
        global $DB;
        $now = time();
        // Insert sample rows.
        $records = [
            (object)['userid' => 2, 'timestamp' => $now - 100, 'prompt_hash' => sha1('p1'), 'response_hash' => null, 'tokens' => 10, 'status' => 1, 'contextid' => 1, 'courseid' => 0],
            (object)['userid' => 3, 'timestamp' => $now - 50, 'prompt_hash' => sha1('p2'), 'response_hash' => null, 'tokens' => 20, 'status' => 1, 'contextid' => 1, 'courseid' => 0],
            (object)['userid' => 2, 'timestamp' => $now - 10, 'prompt_hash' => sha1('p3'), 'response_hash' => null, 'tokens' => 5,  'status' => 0, 'contextid' => 2, 'courseid' => 0],
        ];
        foreach ($records as $r) {
            $DB->insert_record('aiprovider_gis_ai_logs', $r);
        }

        $rows = data_aggregator::aggregate([]);
        $by = function(string $metric) use ($rows) {
            foreach ($rows as $r) { if ($r['metric'] === $metric) return $r['value']; }
            return null;
        };

        $this->assertEquals(3, $by('total_requests'));
        $this->assertEquals(2, $by('unique_users'));
        $this->assertEquals(35, $by('total_tokens'));
        $this->assertSame('66.67%', $by('success_rate'));
    }
}
