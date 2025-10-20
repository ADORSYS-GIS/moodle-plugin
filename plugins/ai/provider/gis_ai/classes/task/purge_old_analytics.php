<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\task;

defined('MOODLE_INTERNAL') || die();

use aiprovider_gis_ai\analytics\usage_tracker;

final class purge_old_analytics extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('task_purge_old_analytics', 'aiprovider_gis_ai');
    }
    public function execute() {
        usage_tracker::purge_old();
    }
}
