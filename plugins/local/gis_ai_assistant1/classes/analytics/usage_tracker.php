<?php
declare(strict_types=1);

namespace local_gis_ai_assistant1\analytics;

defined('MOODLE_INTERNAL') || die();

use local_gis_ai_assistant1\helpers\env_loader;
use local_gis_ai_assistant1\helpers\logger;

final class usage_tracker {
    /**
     * Log an AI interaction (anonymised prompt via SHA-256).
     *
     * @param int $userid
     * @param string $prompt
     * @param array $response processed response (expects 'tokens' if available)
     * @param bool $success
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
        // Contextual fields for course-level analytics.
        $record->contextid = $contextid;
        $record->courseid = $courseid;
        // Optional hashed raw response for auditing only (no raw content stored).
        $record->response_hash = isset($response['raw']) ? hash('sha256', json_encode($response['raw'])) : null;

        try {
            $insertid = $DB->insert_record('gis_ai_logs', $record, true, false);
            // Trigger event for observers/async processing. Non-fatal if it fails.
            try {
                $ctx = $contextid ? \context::instance_by_id($contextid, IGNORE_MISSING) : \context_system::instance();
                $event = \local_gis_ai_assistant1\event\interaction_logged::create([
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

    /**
     * Purge old logs per retention policy.
     *
     * @return void
     */
    public static function purge_old(): void {
        global $DB;

        $days = env_loader::get_int('ANALYTICS_RETENTION_DAYS', 90);
        if ($days <= 0) {
            return;
        }
        $cutoff = time() - ($days * DAYSECS);
        try {
            $DB->delete_records_select('gis_ai_logs', 'timestamp < :cutoff', ['cutoff' => $cutoff]);
        } catch (\Throwable $e) {
            logger::exception($e, 'purgeoldfailed');
        }
    }
}
