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
 * Columns layout for the adorsys_theme_v2 theme
 *
 * @package    theme_adorsys_theme_v2
 * @copyright  2025 adorsys_gis <gis-udm@adorsys.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $PAGE, $USER, $CFG, $OUTPUT;

// Sidebar
$sidepreblockshtml = $OUTPUT->blocks('side-pre');
$sidepostblockshtml = $OUTPUT->blocks('side-post');
$hasblocks = (strpos($sidepreblockshtml, 'data-block=') !== false || strpos($sidepostblockshtml, 'data-block=') !== false);

// User info
$isloggedin = isloggedin() && !isguestuser();
$userpictureurl = $isloggedin ? $OUTPUT->user_picture($USER, ['size'=>100,'link'=>false]) : '';
$username = $isloggedin ? fullname($USER) : '';
$profileurl = $isloggedin ? (new moodle_url('/user/profile.php', ['id'=>$USER->id]))->out(false) : '';
$logouturl = $isloggedin ? (new moodle_url('/login/logout.php', ['sesskey'=>sesskey()]))->out(false) : '';
$loginurl = !$isloggedin ? (new moodle_url('/login/index.php'))->out(false) : '';

// Menu items
$menuitems = [
    ['text'=>get_string('myhome'), 'url'=>new moodle_url('/my/')],
    ['text'=>get_string('courses'), 'url'=>new moodle_url('/course/index.php')],
    ['text'=>get_string('sitehome'), 'url'=>new moodle_url('/')],
];

// Template context
$templatecontext = [
    'sitename' => format_string($SITE->shortname,true,['context'=>context_system::instance()]),
    'config' => $CFG,
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'maincontent' => $OUTPUT->main_content(),   // ✅ MUST include this
    'sidepreblocks' => $sidepreblockshtml,
    'sidepostblocks' => $sidepostblockshtml,
    'hasblocks' => $hasblocks,
    'menuitems' => $menuitems,
    'isloggedin' => $isloggedin,
    'userpictureurl' => $userpictureurl,
    'username' => $username,
    'profileurl' => $profileurl,
    'logouturl' => $logouturl,
    'loginurl' => $loginurl,
];

echo $OUTPUT->render_from_template('theme_adorsys_theme_v2/columns', $templatecontext);
