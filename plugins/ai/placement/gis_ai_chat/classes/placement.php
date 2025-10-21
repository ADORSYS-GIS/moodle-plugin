<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiplacement_gis_ai_chat;

defined('MOODLE_INTERNAL') || die();

/**
 * GIS AI Chat placement: exposes the generate_text action in a site-wide chat UX.
 */
final class placement extends \core_ai\placement {
    /**
     * List Actions this Placement supports.
     *
     * @return array<int, class-string>
     */
    public function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
        ];
    }
}
