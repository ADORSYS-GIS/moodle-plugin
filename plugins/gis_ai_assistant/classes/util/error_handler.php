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
 * Error logging and handling utilities
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for error handling and logging
 */
class error_handler {
    
    /** @var string Component name for logging */
    const COMPONENT = 'local_gis_ai_assistant';

    /**
     * Log an error with context
     *
     * @param string $message Error message
     * @param \Exception $exception Optional exception
     * @param array $context Additional context
     */
    public static function log_error($message, \Exception $exception = null, array $context = []) {
        global $USER;

        $errorData = [
            'userid' => $USER->id,
            'message' => $message,
            'context' => $context
        ];

        if ($exception) {
            $errorData['exception'] = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        // Log to Moodle's error log
        debugging(json_encode($errorData), DEBUG_DEVELOPER);

        // Log to system log for critical errors
        if ($exception instanceof \local_gis_ai_assistant\exceptions\ai_exception) {
            error_log('[GIS AI Assistant] Critical Error: ' . json_encode($errorData));
        }
    }

    /**
     * Handle API errors
     *
     * @param \Exception $exception The exception to handle
     * @return array Error response structure
     */
    public static function handle_api_error(\Exception $exception) {
        $errorResponse = [
            'success' => false,
            'error_code' => 'unknown_error',
            'error' => get_string('error_occurred', self::COMPONENT)
        ];

        if ($exception instanceof \local_gis_ai_assistant\exceptions\configuration_exception) {
            $errorResponse['error_code'] = 'configuration_error';
            $errorResponse['error'] = get_string('no_api_key', self::COMPONENT);
        } else if ($exception instanceof \local_gis_ai_assistant\exceptions\rate_limit_exception) {
            $errorResponse['error_code'] = 'rate_limit';
            $errorResponse['error'] = $exception->getMessage();
        } else if ($exception instanceof \local_gis_ai_assistant\exceptions\ai_exception) {
            $errorResponse['error_code'] = 'ai_error';
            $errorResponse['error'] = $exception->getMessage();
        }

        // Log the error
        self::log_error($errorResponse['error'], $exception);

        return $errorResponse;
    }

    /**
     * Log security related events
     *
     * @param string $event Event type
     * @param array $data Event data
     */
    public static function log_security_event($event, array $data) {
        global $USER;

        $eventData = [
            'userid' => $USER->id,
            'event' => $event,
            'data' => $data,
            'ip' => getremoteaddr()
        ];

        // Log to Moodle's security log
        add_to_log(SITEID, self::COMPONENT, 'security', '', json_encode($eventData));
    }
}
