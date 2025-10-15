<?php
namespace local_gis_ai_assistant1\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;

class provider implements \core_privacy\local\metadata\provider {
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('local_gis_ai_assistant1_logs', [
            'userid' => 'User ID',
            'request_json' => 'Request JSON',
            'response_json' => 'Response JSON',
            'ipaddress' => 'IP Address',
            'timecreated' => 'Time created',
        ], 'AI logs');
        return $items;
    }
}
