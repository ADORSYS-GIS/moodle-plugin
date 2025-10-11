<?php
namespace local_gis_ai_assistant1\helpers;

defined('MOODLE_INTERNAL') || die();

class env_loader {
    public static function get(string $key, $default = null) {
        $v = getenv($key);
        return ($v === false) ? $default : $v;
    }
}
