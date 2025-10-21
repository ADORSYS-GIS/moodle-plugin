<?php
namespace local_gis_ai_assistant1\interfaces;

defined('MOODLE_INTERNAL') || die();

interface api_interface {
    public function send(array $payload): array;
}
