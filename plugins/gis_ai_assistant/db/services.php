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
 * External services for local_gis_ai_assistant plugin.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_gis_ai_assistant_send_message' => [
        'classname' => '\\local_gis_ai_assistant\\external\\chat_api',
        'methodname' => 'send_message',
        'description' => 'Send message to AI and get response',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/gis_ai_assistant:use',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_gis_ai_assistant_send_message_stream' => [
        'classname' => '\\local_gis_ai_assistant\\external\\chat_api',
        'methodname' => 'send_message_stream',
        'description' => 'Send message to AI and get streaming response',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/gis_ai_assistant:use',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_gis_ai_assistant_get_analytics' => [
        'classname' => '\\local_gis_ai_assistant\\external\\chat_api',
        'methodname' => 'get_analytics',
        'description' => 'Get AI usage analytics',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/gis_ai_assistant:viewanalytics',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_gis_ai_assistant_get_history' => [
        'classname' => '\\local_gis_ai_assistant\\external\\chat_api',
        'methodname' => 'get_history',
        'description' => 'Get recent conversation history for current user',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/gis_ai_assistant:use',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];

$services = [
    'AI Assistant Service' => [
        'functions' => [
            'local_gis_ai_assistant_send_message',
            'local_gis_ai_assistant_send_message_stream',
            'local_gis_ai_assistant_get_analytics',
            'local_gis_ai_assistant_get_history',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
