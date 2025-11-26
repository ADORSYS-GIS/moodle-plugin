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
 * Default layout for the adorsys_theme_v2 theme
 *
 * @package    theme_adorsys_theme_v2
 * @copyright  2025 adorsys_gis <gis-udm@adorsys.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID)]),
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'hasnavbar' => empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar(),
    'hasfooter' => empty($PAGE->layout_options['nofooter']),
    'navbar' => $OUTPUT->navbar(),
    'pageheading' => $PAGE->heading,
    'courseheader' => $OUTPUT->course_header(),
    'coursecontentheader' => $OUTPUT->course_content_header(),
    'maincontent' => $OUTPUT->main_content(),
    'coursecontentfooter' => $OUTPUT->course_content_footer(),
    'coursefooter' => $OUTPUT->course_footer(),
    'footer' => $OUTPUT->standard_footer_html(),
    'standardendhtml' => $OUTPUT->standard_end_of_body_html(),
];

echo $OUTPUT->render_from_template('theme_adorsys_theme_v2/default', $templatecontext);
