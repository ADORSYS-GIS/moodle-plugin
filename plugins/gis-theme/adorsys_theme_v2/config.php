<?php
defined('MOODLE_INTERNAL') || die();

$plugin = new stdClass();
$plugin->version = 2023010100;
$plugin->requires = 2022041900;
$plugin->component = 'theme_adorsys_theme_v2';
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0';

$THEME->name = 'adorsys_theme_v2';
$THEME->parents = ['adorsys_theme_v1'];
$THEME->sheets = ['main'];
$THEME->editor_sheets = [];
$THEME->parents_sheets = ['main'];
$THEME->enable_dock = false;
$THEME->yuicssmodules = array();
$THEME->scsspostprocess = 'theme_adorsys_theme_v2_scss_postprocess';
$THEME->layouts = [
    'base' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'standard' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'course' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'coursecategory' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'incourse' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'frontpage' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'admin' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'mydashboard' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'myprofile' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'login' => [
        'file' => 'login.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    'maintenance' => [
        'file' => 'maintenance.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    'embedded' => [
        'file' => 'embedded.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    'print' => [
        'file' => 'columns.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    'report' => [
        'file' => 'columns.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
    'secure' => [
        'file' => 'secure.php',
        'regions' => ['side-pre', 'side-post'],
        'defaultregion' => 'side-post',
        'options' => ['langmenu' => true],
    ],
];

$THEME->scss = function($theme) {
    return theme_adorsys_theme_v2_get_main_scss_content($theme);
};