<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\event;

defined('MOODLE_INTERNAL') || die();

final class interaction_logged extends \core\event\base {
    public static function get_name(): string {
        return get_string('eventinteractionlogged', 'aiprovider_gis_ai');
    }

    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'aiprovider_gis_ai_logs';
    }

    public function get_description(): string {
        return 'AI interaction log created with id ' . $this->objectid . '.';
    }

    public function get_url(): \moodle_url {
        // Placeholder: could point to an admin report when implemented.
        return new \moodle_url('/admin/index.php');
    }
}
