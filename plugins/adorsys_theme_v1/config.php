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
$THEME->sheets = ['main']; // Use this if you are not compiling your CSS via Webpack
$THEME->scss = function($theme) {
    return theme_adorsys_theme_v1_get_main_scss_content($theme);
};
$THEME->layouts = [
    'default' => [
        'file' => 'default.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],
    'columns1' => [
        'file' => 'columns1.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'columns2' => [
        'file' => 'columns2.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],
    'secure' => [
        'file' => 'secure.php',
        'regions' => [],
    ],
    'embedded' => [
        'file' => 'embedded.php',
        'regions' => [],
    ],
    'standard' => [    // Make sure 'standard' layout points to the new file
        'file' => 'drawers.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre',
    ],
    'login' => [
        'file' => 'login.php',
        'regions' => [],
    ],
];

$THEME->page_init = 'theme_adorsys_theme_v1_page_init';  // Hook into page init

$THEME->rendererfactory = 'theme_overridden_renderer_factory'; // If you have custom renderers

$THEME->settings = true;  // Optional: If you have a settings.php

