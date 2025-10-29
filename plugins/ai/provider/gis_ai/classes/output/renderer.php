<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {
    /** Render analytics table using the component mustache template. */
    public function render_analytics(analytics $renderable): string {
        $data = $renderable->export_for_template($this);
        return $this->render_from_template('aiprovider_gis_ai/analytics', $data);
    }
}
