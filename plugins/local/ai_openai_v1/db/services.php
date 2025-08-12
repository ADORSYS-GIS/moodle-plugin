<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_ai_openai_v1_generate_text' => [
        'classname'   => 'local_ai_openai_v1\external\text_generation',
        'methodname'  => 'generate',
        'description' => 'Generate text using the AI provider.',
        'type'        => 'write',
        'ajax'        => true,
    ],
];