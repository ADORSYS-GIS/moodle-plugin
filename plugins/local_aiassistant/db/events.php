<?php
defined('MOODLE_INTERNAL') || die();

// This array tells Moodle which functions (or class methods) to call
// when specific events happen.
$observers = [
    [
        // The event we want to listen for. '\core\event\base' is a wildcard
        // that matches on every single page load before the header is printed.
        'eventname' => '\core\event\base',
        // The class and method Moodle should call when the event fires.
        'callback'  => 'local_aiassistant_observer::on_before_header',
        // A priority level.
        'priority'  => 200,
    ],
];