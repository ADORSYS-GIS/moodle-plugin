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
 * Maintenance layout for the adorsys_theme_v2 theme
 *
 * @package    theme_adorsys_theme_v2
 * @copyright  2025 adorsys_gis <gis-udm@adorsys.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'maincontent' => $OUTPUT->main_content(),
    'hasnavbar' => false,
    'hasfooter' => false,
    'standardendhtml' => $OUTPUT->standard_end_of_body_html(),
];

echo $OUTPUT->render_from_template('theme_adorsys_theme_v2/maintenance', $templatecontext);
