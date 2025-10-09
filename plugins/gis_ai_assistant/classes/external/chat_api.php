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
 * External API for AI chat functionality.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_system;
use local_gis_ai_assistant\api\inference_service;
use local_gis_ai_assistant\exceptions\ai_exception;
use local_gis_ai_assistant\exceptions\rate_limit_exception;
use local_gis_ai_assistant\exceptions\configuration_exception;
use local_gis_ai_assistant\middleware\request_validator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
// Defensive include in case the autoloader cache is stale after a fresh stack.
require_once(__DIR__ . '/../api/inference_service.php');
require_once(__DIR__ . '/../middleware/request_validator.php');
require_once(__DIR__ . '/../../lib.php');

/**
 * External API for AI chat.
 */
class chat_api extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function send_message_parameters() {
        return new external_function_parameters([
            'message' => new external_value(PARAM_RAW, 'User message (multiline allowed)'),
            'model' => new external_value(PARAM_TEXT, 'AI model to use', VALUE_DEFAULT, ''),
            'temperature' => new external_value(PARAM_FLOAT, 'Temperature setting', VALUE_DEFAULT, 0.7),
            'max_tokens' => new external_value(PARAM_INT, 'Maximum tokens', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Send message to AI and get response.
     *
     * @param string $message User message
     * @param string $model AI model
     * @param float $temperature Temperature setting
     * @param int $max_tokens Maximum tokens
     * @return array Response data
     */
    public static function send_message($message, $model = '', $temperature = 0.7, $max_tokens = 0) {
        global $USER;

        $stage = 'init';
        // Validate parameters.
        $params = self::validate_parameters(self::send_message_parameters(), [
            'message' => $message,
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        ]);
        $stage = 'params_ok';

        // Enforce middleware validations.
        $params['message'] = request_validator::validate_message($params['message']);
        if (!empty($params['model'])) {
            $params['model'] = request_validator::validate_model($params['model']);
        }
        $params['temperature'] = request_validator::validate_temperature($params['temperature']);
        if (!empty($params['max_tokens'])) {
            $params['max_tokens'] = request_validator::validate_max_tokens($params['max_tokens']);
        }
        $stage = 'validated';

        // Check capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/gis_ai_assistant:use', $context);
        $stage = 'caps_ok';

        try {
            // Check if AI is enabled via plugin configuration.
            $cfg = \local_gis_ai_assistant_get_config();
            if (empty($cfg['enabled'])) {
                throw new ai_exception('ai_disabled', 'local_gis_ai_assistant');
            }
            $service = new inference_service();
            $stage = 'service_ok';
            
            $options = [];
            if (!empty($params['model'])) {
                $options['model'] = $params['model'];
            }
            if ($params['temperature'] !== 0.7) {
                $options['temperature'] = $params['temperature'];
            }
            if ($params['max_tokens'] > 0) {
                $options['max_tokens'] = $params['max_tokens'];
            }

            $result = $service->chat_completion($params['message'], $options);
            $stage = 'api_ok';

            // Format content as HTML using Markdown for richer display, with sanitization.
            $context = \context_system::instance();
            $contenthtml = '';
            try {
                $contenthtml = format_text($result['content'], FORMAT_MARKDOWN, ['noclean' => false, 'filter' => true], $context);
            } catch (\Throwable $fmtEx) {
                // Fallback to escaped plain text if formatting fails (e.g., filters misconfigured or unavailable).
                $contenthtml = s((string)$result['content']);
            }
            // If Markdown filter didn't convert (still plain text), apply minimal fallback for readability.
            if ($contenthtml === '' || strip_tags($contenthtml) === (string)$result['content']) {
                $contenthtml = nl2br(s((string)$result['content']));
            }

            return [
                'success' => true,
                'content' => $result['content'],
                'content_html' => $contenthtml,
                'model' => $result['model'],
                'usage' => $result['usage'],
                'finish_reason' => $result['finish_reason'],
            ];

        } catch (configuration_exception $e) {
            return [
                'success' => false,
                'error' => get_string('no_api_key', 'local_gis_ai_assistant'),
                'error_code' => 'configuration_error',
                'error_stage' => $stage,
            ];
        } catch (rate_limit_exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'rate_limit',
                'error_stage' => $stage,
            ];
        } catch (ai_exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'api_error',
                'error_stage' => $stage,
            ];
        } catch (\moodle_exception $e) {
            // Surface Moodle-level validation/permission errors to the UI.
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => property_exists($e, 'errorcode') ? $e->errorcode : 'moodle_exception',
                'error_stage' => $stage,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => get_string('error_occurred', 'local_gis_ai_assistant'),
                'error_code' => 'unknown_error',
                'error_stage' => $stage,
            ];
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function send_message_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'content' => new external_value(PARAM_RAW, 'AI response content', VALUE_OPTIONAL),
            'content_html' => new external_value(PARAM_RAW, 'AI response content formatted as HTML', VALUE_OPTIONAL),
            'model' => new external_value(PARAM_TEXT, 'Model used', VALUE_OPTIONAL),
            'usage' => new external_single_structure([
                'prompt_tokens' => new external_value(PARAM_INT, 'Prompt tokens', VALUE_OPTIONAL),
                'completion_tokens' => new external_value(PARAM_INT, 'Completion tokens', VALUE_OPTIONAL),
                'total_tokens' => new external_value(PARAM_INT, 'Total tokens', VALUE_OPTIONAL),
            ], 'Token usage', VALUE_OPTIONAL),
            'finish_reason' => new external_value(PARAM_TEXT, 'Finish reason', VALUE_OPTIONAL),
            'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
            'error_code' => new external_value(PARAM_TEXT, 'Error code', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Returns description of method parameters for streaming.
     *
     * @return external_function_parameters
     */
    public static function send_message_stream_parameters() {
        return new external_function_parameters([
            'message' => new external_value(PARAM_RAW, 'User message (multiline allowed)'),
            'model' => new external_value(PARAM_TEXT, 'AI model to use', VALUE_DEFAULT, ''),
            'temperature' => new external_value(PARAM_FLOAT, 'Temperature setting', VALUE_DEFAULT, 0.7),
            'max_tokens' => new external_value(PARAM_INT, 'Maximum tokens', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Send message to AI and get streaming response.
     *
     * @param string $message User message
     * @param string $model AI model
     * @param float $temperature Temperature setting
     * @param int $max_tokens Maximum tokens
     * @return array Response data
     */
    public static function send_message_stream($message, $model = '', $temperature = 0.7, $max_tokens = 0) {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::send_message_stream_parameters(), [
            'message' => $message,
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        ]);

        // Enforce middleware validations.
        $params['message'] = request_validator::validate_message($params['message']);
        if (!empty($params['model'])) {
            $params['model'] = request_validator::validate_model($params['model']);
        }
        $params['temperature'] = request_validator::validate_temperature($params['temperature']);
        if (!empty($params['max_tokens'])) {
            $params['max_tokens'] = request_validator::validate_max_tokens($params['max_tokens']);
        }

        // Check capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/gis_ai_assistant:use', $context);

        try {
            // Check if AI is enabled via plugin configuration.
            $cfg = \local_gis_ai_assistant_get_config();
            if (empty($cfg['enabled'])) {
                throw new ai_exception('ai_disabled', 'local_gis_ai_assistant');
            }
            $service = new inference_service();
            
            $options = [];
            if (!empty($params['model'])) {
                $options['model'] = $params['model'];
            }
            if ($params['temperature'] !== 0.7) {
                $options['temperature'] = $params['temperature'];
            }
            if ($params['max_tokens'] > 0) {
                $options['max_tokens'] = $params['max_tokens'];
            }

            // For streaming, we'll return a session ID that the frontend can use
            // to establish a streaming connection via a separate endpoint.
            // Use a URL-safe ID (alphanum + underscore) so it passes PARAM_ALPHANUMEXT in stream.php.
            try {
                $sessionid = 'ai_stream_' . bin2hex(random_bytes(16));
            } catch (\Exception $e) {
                // Fallback if random_bytes is unavailable for any reason.
                $sessionid = 'ai_stream_' . str_replace('.', '', uniqid('', true));
            }
            
            // Store the request data in cache for the streaming endpoint.
            $cache = \cache::make('local_gis_ai_assistant', 'stream_sessions');
            $cache->set('stream_' . $sessionid, [
                'message' => $params['message'],
                'options' => $options,
                'userid' => $USER->id,
                'created' => time(),
            ]);

            $url = new \moodle_url('/local/gis_ai_assistant/stream.php', ['session' => $sessionid]);
            return [
                'success' => true,
                'session_id' => $sessionid,
                'stream_url' => $url->out(false),
            ];

        } catch (configuration_exception $e) {
            return [
                'success' => false,
                'error' => get_string('no_api_key', 'local_gis_ai_assistant'),
                'error_code' => 'configuration_error',
            ];
        } catch (rate_limit_exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'rate_limit',
            ];
        } catch (ai_exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'api_error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => get_string('error_occurred', 'local_gis_ai_assistant'),
                'error_code' => 'unknown_error',
            ];
        }
    }

    /**
     * Returns description of method result value for streaming.
     *
     * @return external_single_structure
     */
    public static function send_message_stream_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'session_id' => new external_value(PARAM_TEXT, 'Stream session ID', VALUE_OPTIONAL),
            'stream_url' => new external_value(PARAM_URL, 'Stream URL', VALUE_OPTIONAL),
            'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
            'error_code' => new external_value(PARAM_TEXT, 'Error code', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Returns description of method parameters for analytics.
     *
     * @return external_function_parameters
     */
    public static function get_analytics_parameters() {
        return new external_function_parameters([
            'period' => new external_value(PARAM_TEXT, 'Time period (day, week, month)', VALUE_DEFAULT, 'week'),
        ]);
    }

    /**
     * Get AI usage analytics.
     *
     * @param string $period Time period
     * @return array Analytics data
     */
    public static function get_analytics($period = 'week') {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::get_analytics_parameters(), [
            'period' => $period,
        ]);

        // Check capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/gis_ai_assistant:viewanalytics', $context);

        $periods = [
            'day' => 86400,
            'week' => 604800,
            'month' => 2592000,
        ];

        $timeframe = $periods[$params['period']] ?? $periods['week'];
        $since = time() - $timeframe;

        try {
            // Total requests.
            $totalrequests = $DB->count_records_select('local_gis_ai_assistant_conversations', 'timecreated >= ?', [$since]);

            // Total tokens.
            $tokensql = 'SELECT SUM(tokens_used) FROM {local_gis_ai_assistant_conversations} WHERE timecreated >= ?';
            $totaltokens = $DB->get_field_sql($tokensql, [$since]) ?: 0;

            // Average response time.
            $avgtimesql = 'SELECT AVG(response_time) FROM {local_gis_ai_assistant_conversations} WHERE timecreated >= ?';
            $avgtime = $DB->get_field_sql($avgtimesql, [$since]) ?: 0;

            // Top users.
            $usersql = 'SELECT u.firstname, u.lastname, COUNT(*) as request_count 
                       FROM {local_gis_ai_assistant_conversations} r 
                       JOIN {user} u ON r.userid = u.id 
                       WHERE r.timecreated >= ? 
                       GROUP BY r.userid, u.firstname, u.lastname 
                       ORDER BY request_count DESC 
                       LIMIT 10';
            $topusers = $DB->get_records_sql($usersql, [$since]);

            // Usage by model.
            $modelsql = 'SELECT model, COUNT(*) as request_count, SUM(tokens_used) as token_count 
                        FROM {local_gis_ai_assistant_conversations} 
                        WHERE timecreated >= ? 
                        GROUP BY model 
                        ORDER BY request_count DESC';
            $modelusage = $DB->get_records_sql($modelsql, [$since]);

            return [
                'success' => true,
                'period' => $params['period'],
                'total_requests' => $totalrequests,
                'total_tokens' => (int)$totaltokens,
                'average_response_time' => round($avgtime, 2),
                'top_users' => array_values($topusers),
                'model_usage' => array_values($modelusage),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to retrieve analytics: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Returns description of method result value for analytics.
     *
     * @return external_single_structure
     */
    public static function get_analytics_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'period' => new external_value(PARAM_TEXT, 'Time period', VALUE_OPTIONAL),
            'total_requests' => new external_value(PARAM_INT, 'Total requests', VALUE_OPTIONAL),
            'total_tokens' => new external_value(PARAM_INT, 'Total tokens', VALUE_OPTIONAL),
            'average_response_time' => new external_value(PARAM_FLOAT, 'Average response time', VALUE_OPTIONAL),
            'top_users' => new external_multiple_structure(
                new external_single_structure([
                    'firstname' => new external_value(PARAM_TEXT, 'First name'),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                    'request_count' => new external_value(PARAM_INT, 'Request count'),
                ]), 'Top users', VALUE_OPTIONAL
            ),
            'model_usage' => new external_multiple_structure(
                new external_single_structure([
                    'model' => new external_value(PARAM_TEXT, 'Model name'),
                    'request_count' => new external_value(PARAM_INT, 'Request count'),
                    'token_count' => new external_value(PARAM_INT, 'Token count'),
                ]), 'Model usage', VALUE_OPTIONAL
            ),
            'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Parameters for get_history.
     *
     * @return external_function_parameters
     */
    public static function get_history_parameters() {
        return new external_function_parameters([
            'limit' => new external_value(PARAM_INT, 'Maximum number of entries to return', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * Return recent conversation history for the current user.
     * Each entry represents one user message and the corresponding AI response.
     *
     * @param int $limit
     * @return array
     */
    public static function get_history($limit = 50) {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::get_history_parameters(), [ 'limit' => $limit ]);
        $limit = max(1, min((int)$params['limit'], 200));

        // Check capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/gis_ai_assistant:use', $context);

        try {
            $records = $DB->get_records(
                'local_gis_ai_assistant_conversations',
                ['userid' => $USER->id],
                'timecreated DESC',
                'id, message, response, timecreated',
                0,
                $limit
            );

            // Return in ascending order so UI can display chronologically.
            $entries = array_values($records);
            $entries = array_reverse($entries);

            $context = \context_system::instance();
            $history = [];
            foreach ($entries as $r) {
                $message = (string)($r->message ?? '');
                $response = (string)($r->response ?? '');
                try {
                    $responsehtml = format_text($response, FORMAT_MARKDOWN, ['noclean' => false, 'filter' => true], $context);
                } catch (\Throwable $fmtEx) {
                    $responsehtml = s($response);
                }
                if ($responsehtml === '' || strip_tags($responsehtml) === $response) {
                    $responsehtml = nl2br(s($response));
                }
                $history[] = [
                    'message' => $message,
                    'response' => $response,
                    'response_html' => $responsehtml,
                    'timecreated' => (int)($r->timecreated ?? 0),
                ];
            }

            return [
                'success' => true,
                'history' => $history,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to load history',
            ];
        }
    }

    /**
     * Returns structure for get_history.
     *
     * @return external_single_structure
     */
    public static function get_history_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'history' => new external_multiple_structure(
                new external_single_structure([
                    'message' => new external_value(PARAM_RAW, 'User message'),
                    'response' => new external_value(PARAM_RAW, 'AI response'),
                    'response_html' => new external_value(PARAM_RAW, 'AI response formatted as HTML'),
                    'timecreated' => new external_value(PARAM_INT, 'Unix timestamp'),
                ])
            , VALUE_OPTIONAL),
            'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL),
        ]);
    }
}
