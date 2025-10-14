<?php
declare(strict_types=1);

/**
 * Renderable analytics view model for GIS AI Assistant.
 *
 * @package     local_gis_ai_assistant1
 * @category    output
 * @since       2025.10
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant1\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

class analytics implements renderable, templatable {
    /** @var array<int, array{metric:string,value:mixed}> */
    private array $rows;

    /**
     * @param array<int, array{metric:string,value:mixed}> $rows
     */
    public function __construct(array $rows = []) {
        $this->rows = $rows;
    }

    /**
     * Export for Mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'rows' => array_map(static function($r) {
                return [
                    'metric' => (string)($r['metric'] ?? ''),
                    'value'  => is_scalar($r['value'] ?? null) ? (string)$r['value'] : json_encode($r['value'] ?? null),
                ];
            }, $this->rows),
        ];
    }
}
