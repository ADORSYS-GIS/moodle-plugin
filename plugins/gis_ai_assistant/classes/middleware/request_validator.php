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
 * Request validation middleware
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant\middleware;

defined('MOODLE_INTERNAL') || die();

/**
 * Middleware for validating and sanitizing requests
 */
class request_validator {
    
    /**
     * Validate and sanitize chat message
     *
     * @param string $message The message to validate
     * @return string Sanitized message
     * @throws \moodle_exception If validation fails
     */
    public static function validate_message($message) {
        // Remove any null bytes
        $message = str_replace("\0", '', $message);
        
        // Basic XSS prevention
        $message = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Length validation (align with UI textarea maxlength = 10000)
        if (empty($message) || strlen($message) > 10000) {
            throw new \moodle_exception('invalid_message_length', 'local_gis_ai_assistant');
        }
        
        return $message;
    }

    /**
     * Validate temperature parameter
     *
     * @param float $temperature The temperature value to validate
     * @return float Validated temperature
     * @throws \moodle_exception If validation fails
     */
    public static function validate_temperature($temperature) {
        $temperature = floatval($temperature);
        if ($temperature < 0 || $temperature > 2.0) {
            throw new \moodle_exception('invalid_temperature', 'local_gis_ai_assistant');
        }
        return $temperature;
    }

    /**
     * Validate max tokens parameter
     *
     * @param int $maxTokens The max tokens value to validate
     * @return int Validated max tokens
     * @throws \moodle_exception If validation fails
     */
    public static function validate_max_tokens($maxTokens) {
        $maxTokens = intval($maxTokens);
        if ($maxTokens < 1 || $maxTokens > 4096) {
            throw new \moodle_exception('invalid_max_tokens', 'local_gis_ai_assistant');
        }
        return $maxTokens;
    }

    /**
     * Validate model name
     *
     * @param string $model The model name to validate
     * @return string Validated model name
     * @throws \moodle_exception If validation fails
     */
    public static function validate_model($model) {
        $allowedModels = ['kivoyo', 'gpt-4-turbo', 'gpt-3.5-turbo', 'gpt-4o-mini', 'adorsys'];
        if (!in_array($model, $allowedModels)) {
            throw new \moodle_exception('invalid_model', 'local_gis_ai_assistant');
        }
        return $model;
    }

    /**
     * Validate analytics time range
     *
     * @param int $timeRange The time range in days
     * @return int Validated time range
     * @throws \moodle_exception If validation fails
     */
    public static function validate_time_range($timeRange) {
        $timeRange = intval($timeRange);
        if ($timeRange < 1 || $timeRange > 365) {
            throw new \moodle_exception('invalid_time_range', 'local_gis_ai_assistant');
        }
        return $timeRange;
    }
}
