<?php
namespace local_gis_ai_assistant1\helpers;

defined('MOODLE_INTERNAL') || die();

class logger {
    public static function info(string $msg): void { debugging($msg, DEBUG_DEVELOPER); }
}
