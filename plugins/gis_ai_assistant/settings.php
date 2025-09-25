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
 * Admin settings for local_gis_ai_assistant plugin.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_gis_ai_assistant', get_string('pluginname', 'local_gis_ai_assistant'));

    // Enable/disable AI functionality.
    $settings->add(new admin_setting_configcheckbox(
        'local_gis_ai_assistant/enabled',
        get_string('enabled', 'local_gis_ai_assistant'),
        get_string('enabled_desc', 'local_gis_ai_assistant'),
        1
    ));

    // Default model name.
    $settings->add(new admin_setting_configtext(
        'local_gis_ai_assistant/default_model',
        get_string('default_model', 'local_gis_ai_assistant'),
        get_string('default_model_desc', 'local_gis_ai_assistant'),
        'gpt-4o-mini',
        PARAM_TEXT
    ));

    // Maximum tokens per request.
    $settings->add(new admin_setting_configtext(
        'local_gis_ai_assistant/max_tokens',
        get_string('max_tokens', 'local_gis_ai_assistant'),
        get_string('max_tokens_desc', 'local_gis_ai_assistant'),
        '2048',
        PARAM_INT
    ));

    // Temperature setting.
    $settings->add(new admin_setting_configtext(
        'local_gis_ai_assistant/temperature',
        get_string('temperature', 'local_gis_ai_assistant'),
        get_string('temperature_desc', 'local_gis_ai_assistant'),
        '0.7',
        PARAM_FLOAT
    ));

    // Rate limiting - requests per hour.
    $settings->add(new admin_setting_configtext(
        'local_gis_ai_assistant/rate_limit_requests',
        get_string('rate_limit_requests', 'local_gis_ai_assistant'),
        get_string('rate_limit_requests_desc', 'local_gis_ai_assistant'),
        '100',
        PARAM_INT
    ));

    // Rate limiting - tokens per hour.
    $settings->add(new admin_setting_configtext(
        'local_gis_ai_assistant/rate_limit_tokens',
        get_string('rate_limit_tokens', 'local_gis_ai_assistant'),
        get_string('rate_limit_tokens_desc', 'local_gis_ai_assistant'),
        '50000',
        PARAM_INT
    ));

    // Cache TTL in seconds.
    $settings->add(new admin_setting_configtext(
        'local_gis_ai_assistant/cache_ttl',
        get_string('cache_ttl', 'local_gis_ai_assistant'),
        get_string('cache_ttl_desc', 'local_gis_ai_assistant'),
        '3600',
        PARAM_INT
    ));

    // Enable caching.
    $settings->add(new admin_setting_configcheckbox(
        'local_gis_ai_assistant/enable_cache',
        get_string('enable_cache', 'local_gis_ai_assistant'),
        get_string('enable_cache_desc', 'local_gis_ai_assistant'),
        1
    ));

    // Enable analytics.
    $settings->add(new admin_setting_configcheckbox(
        'local_gis_ai_assistant/enable_analytics',
        get_string('enable_analytics', 'local_gis_ai_assistant'),
        get_string('enable_analytics_desc', 'local_gis_ai_assistant'),
        1
    ));

    // System prompt for AI.
    $settings->add(new admin_setting_configtextarea(
        'local_gis_ai_assistant/system_prompt',
        get_string('system_prompt', 'local_gis_ai_assistant'),
        get_string('system_prompt_desc', 'local_gis_ai_assistant'),
        'You are a helpful AI assistant integrated into a Moodle learning management system. Provide accurate, educational, and appropriate responses to help users with their learning and teaching activities.',
        PARAM_TEXT
    ));

    // Streaming session TTL.
    $settings->add(new admin_setting_configtext(
        'local_gis_ai_assistant/stream_session_ttl',
        get_string('stream_session_ttl', 'local_gis_ai_assistant'),
        get_string('stream_session_ttl_desc', 'local_gis_ai_assistant'),
        '300',
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
