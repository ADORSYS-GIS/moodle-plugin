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
 * AI Analytics dashboard.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/gis_ai_assistant:viewanalytics', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/gis_ai_assistant/analytics.php');
$PAGE->set_title(get_string('analytics_title', 'local_gis_ai_assistant'));
$PAGE->set_heading(get_string('analytics_title', 'local_gis_ai_assistant'));
$PAGE->set_pagelayout('admin');

// Add CSS and JS for charts.
$PAGE->requires->css('/local/gis_ai_assistant/styles.css');
$PAGE->requires->js_call_amd('local_gis_ai_assistant/analytics', 'init');

echo $OUTPUT->header();

// Render analytics dashboard.
echo $OUTPUT->render_from_template('local_gis_ai_assistant/analytics', [
    'has_analytics_capability' => true,
]);

echo $OUTPUT->footer();
