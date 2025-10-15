<?php
namespace local_gis_ai_assistant1\interfaces;

defined('MOODLE_INTERNAL') || die();

interface analytics_interface {
    public function aggregate(): array;
}
