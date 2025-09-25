<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy provider for local_gis_ai_assistant plugin.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the AI plugin.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_gis_ai_assistant_conversations',
            [
                'userid' => 'privacy:metadata:local_gis_ai_assistant_conversations:userid',
                'message' => 'privacy:metadata:local_gis_ai_assistant_conversations:message',
                'response' => 'privacy:metadata:local_gis_ai_assistant_conversations:response',
                'model' => 'privacy:metadata:local_gis_ai_assistant_conversations:model',
                'tokens_used' => 'privacy:metadata:local_gis_ai_assistant_conversations:tokens_used',
                'response_time' => 'privacy:metadata:local_gis_ai_assistant_conversations:response_time',
                'timecreated' => 'privacy:metadata:local_gis_ai_assistant_conversations:timecreated',
            ],
            'privacy:metadata:local_gis_ai_assistant_conversations'
        );

        $collection->add_database_table(
            'local_gis_ai_assistant_rate_limits',
            [
                'userid' => 'privacy:metadata:local_gis_ai_assistant_rate_limits:userid',
                'requests_count' => 'privacy:metadata:local_gis_ai_assistant_rate_limits:requests_count',
                'tokens_count' => 'privacy:metadata:local_gis_ai_assistant_rate_limits:tokens_count',
                'window_start' => 'privacy:metadata:local_gis_ai_assistant_rate_limits:window_start',
            ],
            'privacy:metadata:local_gis_ai_assistant_rate_limits'
        );

        $collection->add_external_location_link(
            'openai',
            [
                'messages' => 'privacy:metadata:openai:messages',
                'user_email' => 'privacy:metadata:openai:user_email',
            ],
            'privacy:metadata:openai'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();

        $hasdata = $DB->record_exists('local_gis_ai_assistant_conversations', ['userid' => $userid]) ||
                   $DB->record_exists('local_gis_ai_assistant_rate_limits', ['userid' => $userid]);

        if ($hasdata) {
            $contextlist->add_context(\context_system::instance());
        }

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        
        $sql = "SELECT DISTINCT r.userid
                FROM {local_gis_ai_assistant_conversations} r";
        
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        
        $user = $contextlist->get_user();
        $userid = $user->id;
        
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            
            // Export AI conversations.
            $conversations = $DB->get_records('local_gis_ai_assistant_conversations', ['userid' => $userid]);
            if (!empty($conversations)) {
                $data = [];
                foreach ($conversations as $conversation) {
                    $data[] = [
                        'message' => $conversation->message,
                        'response' => $conversation->response,
                        'model' => $conversation->model,
                        'tokens_used' => $conversation->tokens_used,
                        'response_time' => $conversation->response_time,
                        'time_created' => transform::datetime($conversation->timecreated),
                    ];
                }
                
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_gis_ai_assistant'), get_string('conversations', 'local_gis_ai_assistant')],
                    (object) $data
                );
            }
            
            // Export rate limit data.
            $ratelimits = $DB->get_records('local_gis_ai_assistant_rate_limits', ['userid' => $userid]);
            if (!empty($ratelimits)) {
                $data = [];
                foreach ($ratelimits as $limit) {
                    $data[] = [
                        'requests_count' => $limit->requests_count,
                        'tokens_count' => $limit->tokens_count,
                        'window_start' => transform::datetime($limit->window_start),
                    ];
                }
                
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_gis_ai_assistant'), get_string('rate_limits', 'local_gis_ai_assistant')],
                    (object) $data
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        
        $DB->delete_records('local_gis_ai_assistant_conversations');
        $DB->delete_records('local_gis_ai_assistant_rate_limits');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        
        $userid = $contextlist->get_user()->id;
        
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            
            $DB->delete_records('local_gis_ai_assistant_conversations', ['userid' => $userid]);
            $DB->delete_records('local_gis_ai_assistant_rate_limits', ['userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        
        $context = $userlist->get_context();
        
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        
        $DB->delete_records_select('local_gis_ai_assistant_conversations', "userid $insql", $inparams);
        $DB->delete_records_select('local_gis_ai_assistant_rate_limits', "userid $insql", $inparams);
    }
}
