<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiplacement_gis_ai_chat\privacy;

defined('MOODLE_INTERNAL') || die();

final class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
