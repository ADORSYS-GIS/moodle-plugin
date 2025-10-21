<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\interfaces;

defined('MOODLE_INTERNAL') || die();

interface api_interface {
    /** Send a payload and return decoded array response. */
    public function send(array $payload): array;
}
