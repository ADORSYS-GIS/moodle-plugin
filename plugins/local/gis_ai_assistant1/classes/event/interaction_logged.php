<?php
declare(strict_types=1);

namespace local_gis_ai_assistant1\event;

defined('MOODLE_INTERNAL') || die();

class interaction_logged extends \core\event\base {
    public static function get_name(): string {
        return get_string('eventinteractionlogged', 'local_gis_ai_assistant1');
    }

    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'gis_ai_logs';
    }

    public function get_description(): string {
        return 'AI interaction log created with id ' . $this->objectid . '.';
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/local/gis_ai_assistant1/analytics/index.php');
    }
}
