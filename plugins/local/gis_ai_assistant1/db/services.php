<?php
// This file registers external web service functions for AJAX use.
// Moodle loads this during plugin installation/upgrade.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_gis_ai_assistant1_send_prompt' => [
        'classname'   => 'local_gis_ai_assistant1\external\send_prompt',
        'methodname'  => 'execute',
        'description' => 'Send a user prompt to the AI assistant and return a response.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/gis_ai_assistant1:use',
    ],
    'local_gis_ai_assistant1_get_response' => [
        'classname'   => 'local_gis_ai_assistant1\external\get_response',
        'methodname'  => 'execute',
        'description' => 'Poll for a response by request ID (placeholder for async workflows).',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/gis_ai_assistant1:use',
    ],
    'local_gis_ai_assistant1_get_analytics' => [
        'classname'   => 'local_gis_ai_assistant1\external\get_analytics',
        'methodname'  => 'execute',
        'description' => 'Get analytics data for admins.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/gis_ai_assistant1:viewanalytics',
    ],
];

// No custom services are required for AJAX functions; they are available via the core AJAX service.
