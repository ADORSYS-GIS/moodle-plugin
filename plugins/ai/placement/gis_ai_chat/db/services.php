<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = [
    'aiplacement_gis_ai_chat_send' => [
        'classname'   => '\\aiplacement_gis_ai_chat\\external\\send',
        'methodname'  => 'execute',
        'description' => 'Send prompt to AI via core_ai manager and return response',
        'type'        => 'write',
        'capabilities'=> 'aiplacement/gis_ai_chat:generate_text',
        'loginrequired' => true,
        'ajax'        => true,
    ],
];
