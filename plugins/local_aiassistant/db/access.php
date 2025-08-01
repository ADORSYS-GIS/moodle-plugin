<?php
defined('MOODLE_INTERNAL') || die();

// This array defines all the permissions (capabilities) for this plugin.
$capabilities = [
    // Defines a new permission called 'local/aiassistant:use'.
    'local/aiassistant:use' => [
        // This capability will be checked using has_capability().
        'captype' => 'write',
        // It applies at the system level (i.e., site-wide).
        'contextlevel' => CONTEXT_SYSTEM,
        // Specifies which roles get this permission by default upon installation.
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'user' => CAP_ALLOW // Let's allow all authenticated users for this example
        ],
        // Specifies which roles can get this permission by default upon upgrade. 
        'upgrade' => [
            'manager' => CAP_ALLOW
        ],
    ],
];