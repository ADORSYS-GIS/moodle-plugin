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
 * Theme config file for adorsys_theme_v1
 *
 * @package    theme_adorsys_theme_v1
 * @copyright  2025 Adorsys
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$THEME->name = 'adorsys_theme_v1';

$THEME->parents = [];

$THEME->layouts = [

    'standard' => [
        'file' => 'drawers.php', 
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],
    // Used by default for most pages.
    'default' => [
        'file' => 'default.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    // Used for course main page.
    'course' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    // Used for course modules (like assignments, quizzes).
    'incourse' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    // Site home page.
    'frontpage' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    // User dashboard.
    'mydashboard' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    // My courses page.
    'mycourses' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],

    // Login page.
    'login' => [
        'file' => 'login.php',
        'regions' => [],
    ],

    // Admin pages.
    'admin' => [
        'file' => 'columns.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    // Category pages.
    'coursecategory' => [
        'file' => 'columns.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

    // Embedded pages.
    'embedded' => [
        'file' => 'embedded.php',
        'regions' => [],
    ],

    // Secure layout.
    'secure' => [
        'file' => 'secure.php',
        'regions' => [],
    ],

    // Maintenance page.
    'maintenance' => [
        'file' => 'maintenance.php',
        'regions' => [],
    ],

    // Popup windows.
    'popup' => [
        'file' => 'embedded.php',
        'regions' => [],
    ],

    // Redirection pages.
    'redirect' => [
        'file' => 'embedded.php',
        'regions' => [],
    ],

    // Report layout.
    'report' => [
        'file' => 'columns.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],

];

$THEME->page_init = 'theme_adorsys_theme_v1_page_init';

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

$THEME->settings = true;

$THEME->scss = function($theme) {
    return theme_adorsys_theme_v1_get_main_scss_content($theme);
};





