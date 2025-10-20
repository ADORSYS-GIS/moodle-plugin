<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\output;

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
