<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/gis_ai_assistant1:use' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [ 'user' => CAP_ALLOW, 'manager' => CAP_ALLOW ],
    ],
    'local/gis_ai_assistant1:viewanalytics' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [ 'manager' => CAP_ALLOW ],
    ],
];
