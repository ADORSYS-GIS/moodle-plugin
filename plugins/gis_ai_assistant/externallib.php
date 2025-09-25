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
 * Legacy external class wrapper delegating to namespaced implementation.
 *
 * This ensures maximum compatibility with Moodle's external function loader,
 * by providing a non-namespaced class `local_gis_ai_assistant_external` in
 * externallib.php as many components expect.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
// Explicitly load the namespaced implementation to avoid autoload timing issues.
require_once(__DIR__ . '/classes/external/chat_api.php');

/**
 * Legacy external class for AJAX/web service endpoints.
 *
 * Delegates all static methods to the namespaced class
 * \local_gis_ai_assistant\external\chat_api.
 */
class local_gis_ai_assistant_external extends \local_gis_ai_assistant\external\chat_api {}