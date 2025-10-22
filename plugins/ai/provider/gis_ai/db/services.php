<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

$functions = [
    'aiprovider_gis_ai_get_analytics' => [
        'classname'   => '\\aiprovider_gis_ai\\external\\get_analytics',
        'methodname'  => 'execute',
        'description' => 'Return aggregated provider analytics metrics',
        'type'        => 'read',
        'capabilities'=> 'aiprovider/gis_ai:viewanalytics',
        'loginrequired' => true,
        'ajax'        => true,
    ],
];
