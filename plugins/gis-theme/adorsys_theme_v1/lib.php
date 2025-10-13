<?php
defined('MOODLE_INTERNAL') || die();

function theme_adorsys_theme_v1_page_init(moodle_page $page) {
    // Load JS
    $page->requires->js(new moodle_url('/theme/adorsys_theme_v1/js/bundle.js'), true);

    // Load Tailwind CSS (compiled via Webpack)
    $page->requires->css(new moodle_url('/theme/adorsys_theme_v1/style/bundle.css'));
}

function theme_adorsys_theme_v1_get_main_scss_content($theme) {
    $scss = file_get_contents(__DIR__ . '/src/styles/main.scss');
    if (!empty($theme->settings['customcss'])) {
        $scss .= $theme->settings['customcss'];
    }
    return $scss;
}
