<?php
namespace local_gis_ai_assistant1\helpers;

defined('MOODLE_INTERNAL') || die();

class sanitizer {
    public static function text(string $t): string { return clean_param($t, PARAM_TEXT); }
}
