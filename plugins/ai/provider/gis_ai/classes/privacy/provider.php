<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: declares analytics table and supports export and deletion.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe stored data for this plugin.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('aiprovider_gis_ai_logs', [
            'userid' => 'privacy:metadata:aiprovider_gis_ai_logs:userid',
            'timestamp' => 'privacy:metadata:aiprovider_gis_ai_logs:timestamp',
            'prompt_hash' => 'privacy:metadata:aiprovider_gis_ai_logs:prompt_hash',
            'response_hash' => 'privacy:metadata:aiprovider_gis_ai_logs:response_hash',
            'tokens' => 'privacy:metadata:aiprovider_gis_ai_logs:tokens',
            'status' => 'privacy:metadata:aiprovider_gis_ai_logs:status',
            'contextid' => 'privacy:metadata:aiprovider_gis_ai_logs:contextid',
            'courseid' => 'privacy:metadata:aiprovider_gis_ai_logs:courseid',
        ], 'privacy:metadata:aiprovider_gis_ai_logs');
        return $collection;
    }

    /**
     * Get contexts for a given user id.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist('aiprovider_gis_ai');
        $sql = "SELECT DISTINCT contextid FROM {aiprovider_gis_ai_logs} WHERE userid = :userid AND contextid > 0";
        $params = ['userid' => $userid];
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export user data for the approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $records = $DB->get_records('aiprovider_gis_ai_logs', [
                'userid' => $userid,
                'contextid' => $context->id,
            ], 'timestamp ASC');
            if (!$records) {
                continue;
            }
            $data = array_values(array_map(static function($r) {
                return [
                    'timestamp' => (int)$r->timestamp,
                    'prompt_hash' => (string)$r->prompt_hash,
                    'response_hash' => (string)($r->response_hash ?? ''),
                    'tokens' => (int)($r->tokens ?? 0),
                    'status' => (int)$r->status,
                    'courseid' => (int)($r->courseid ?? 0),
                ];
            }, $records));
            writer::with_context($context)->export_data([
                'aiprovider_gis_ai', 'analytics'
            ], (object)['interactions' => $data]);
        }
    }

    /**
     * Delete all user data for all users in the specified context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        $DB->delete_records('aiprovider_gis_ai_logs', ['contextid' => $context->id]);
    }

    /**
     * Delete all user data for the specified user in the specified contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        if (empty($contextlist->count())) {
            return;
        }
        $contextids = array_map(static fn($c) => $c->id, $contextlist->get_contexts());
        list($insql, $inparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        $params = ['userid' => $userid] + $inparams;
        $DB->delete_records_select('aiprovider_gis_ai_logs', "userid = :userid AND contextid $insql", $params);
    }
}
