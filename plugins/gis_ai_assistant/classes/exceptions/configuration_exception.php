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

/**
 * Configuration exception class.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant\exceptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Configuration exception.
 */
class configuration_exception extends ai_exception {
    
    /**
     * Constructor.
     *
     * @param string $message Error message
     * @param string $module Module name
     * @param string $link Link for more info
     * @param mixed $a Additional data
     * @param string $debuginfo Debug information
     */
    public function __construct($message = '', $module = 'local_gis_ai_assistant', $link = '', $a = null, $debuginfo = null) {
        parent::__construct('error_api_key_missing', $module, $link, $message, $debuginfo);
    }
}
