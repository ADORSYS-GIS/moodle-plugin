<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\analytics;

defined('MOODLE_INTERNAL') || die();

use aiprovider_gis_ai\helpers\logger;
use aiprovider_gis_ai\helpers\env_loader;

final class usage_tracker {
    /**
     * Log an AI interaction (anonymised prompt via SHA-256).
     *
     * @param int $userid
     * @param string $prompt
     * @param array $response processed response (expects 'tokens' if available)
     * @param bool $success
     * @param int $contextid
     * @param int $courseid
     * @return void
     */
    public static function log_interaction(int $userid, string $prompt, array $response, bool $success, int $contextid = 0, int $courseid = 0): void {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->timestamp = time();
        $record->prompt_hash = hash('sha256', $prompt);
        $record->tokens = (int)($response['tokens'] ?? 0);
        $record->status = $success ? 1 : 0;
        $record->contextid = $contextid;
        $record->courseid = $courseid;
        $record->response_hash = isset($response['raw']) ? hash('sha256', json_encode($response['raw'])) : null;

        try {
            $insertid = $DB->insert_record('aiprovider_gis_ai_logs', $record, true, false);
            // Trigger event for observers/async processing. Non-fatal if it fails.
            try {
                $ctx = $contextid ? \context::instance_by_id($contextid, IGNORE_MISSING) : \context_system::instance();
                $event = \aiprovider_gis_ai\event\interaction_logged::create([
                    'context' => $ctx,
                    'objectid' => $insertid,
                    'other' => [
                        'userid' => $userid,
                        'prompt_hash' => $record->prompt_hash,
                        'courseid' => $courseid,
                    ],
                ]);
                $event->trigger();
            } catch (\Throwable $ev) {
                logger::exception($ev, 'eventtriggerfailed');
            }
        } catch (\Throwable $e) {
            logger::exception($e, 'loginteractionfailed');
        }
    }

    /** Purge old logs per retention policy. */
    public static function purge_old(): void {
        global $DB;
        $days = env_loader::get_int('ANALYTICS_RETENTION_DAYS', 90);
        if ($days <= 0) {
            return;
        }
        $cutoff = time() - ($days * DAYSECS);
        try {
            $DB->delete_records_select('aiprovider_gis_ai_logs', 'timestamp < :cutoff', ['cutoff' => $cutoff]);
        } catch (\Throwable $e) {
            logger::exception($e, 'purgeoldfailed');
        }
    }
}
