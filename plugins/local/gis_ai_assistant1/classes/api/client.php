<?php
namespace local_gis_ai_assistant1\api;

defined('MOODLE_INTERNAL') || die();

class client {
    public static function call(string $endpoint, array $payload, array $headers = []): array {
        // HTTP fallback client placeholder.
        return ['status' => 'not_implemented'];
    }
}
