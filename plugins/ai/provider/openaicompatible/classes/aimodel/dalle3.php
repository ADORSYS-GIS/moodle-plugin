<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aiprovider_openaicompatible\aimodel;

use core_ai\aimodel\base;

/**
 * DALL-E 3 OpenAI model class.
 *
 * @package    aiprovider_openaicompatible
 * @copyright  2025   Adorsys GIS <gis-udm@adorsys.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dalle3 extends base implements openai_base, openai_image_base {

    #[\Override]
    public function get_model_name(): string {
        return 'dall-e-3';
    }

    #[\Override]
    public function get_model_display_name(): string {
        return 'DALL-E 3';
    }

    #[\Override]
    public function model_type(): array {
        return [self::MODEL_TYPE_IMAGE];
    }

    #[\Override]
    public function calculate_quality(string $quality): string {
        if ($quality === 'standard') {
            return 'standard';
        } else if ($quality === 'hd') {
            return 'hd';
        }
        return 'standard';
    }

    #[\Override]
    public function calculate_size(string $aspectratio): string {
        if ($aspectratio === 'portrait') {
            return '1024x1792';
        } else if ($aspectratio === 'landscape') {
            return '1792x1024';
        }

        // Return a square image for all other aspect ratios.
        return '1024x1024';
    }

    #[\Override]
    public function response_format(): ?string {
        return 'b64_json';
    }

    #[\Override]
    public function get_output_format(): ?string {
        // DALL-E 3 does not accept output_format
        return null;
    }
}
