<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_ai\hook\after_ai_provider_form_hook::class,
        'callback' => \aiprovider_gisai\hook_listener::class . '::set_form_definition_for_aiprovider_gisai',
    ],
];
