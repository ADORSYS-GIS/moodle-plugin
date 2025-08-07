<?php
defined('MOODLE_INTERNAL') || die();

function theme_adorsys_theme_v1_page_init(moodle_page $page) {
    $page->requires->js(new moodle_url('/theme/adorsys_theme_v1/dist/js/main.js'), true);
}
