<?php
defined('MOODLE_INTERNAL') || die();

function theme_adorsys_theme_v2_page_init(moodle_page $page) {
    // Load JS
    $page->requires->js(new moodle_url('/theme/adorsys_theme_v2/js/bundle.js'), true);

    // Load Tailwind CSS (compiled via Webpack)
    $page->requires->css(new moodle_url('/theme/adorsys_theme_v2/dist/bundle.css'));
}

/**
 * Loads the main SCSS preset (default, plain, or custom uploaded)
 */
function theme_adorsys_theme_v2_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();
    $context = context_system::instance();

    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/adorsys_theme_v2/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/adorsys_theme_v2/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_adorsys_theme_v2', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        // Fallback to default preset.
        $scss .= file_get_contents($CFG->dirroot . '/theme/adorsys_theme_v2/scss/preset/default.scss');
    }

    return $scss;
}

/**
 * Add optional user custom SCSS and background images
 */
function theme_adorsys_theme_v2_get_extra_scss($theme) {
    $content = '';
    $imageurl = $theme->setting_file_url('backgroundimage', 'backgroundimage');

    if (!empty($imageurl)) {
        $content .= '@media (min-width: 768px) {';
        $content .= 'body { background-image: url("' . $imageurl . '"); background-size: cover; }';
        $content .= '}';
    }

    $loginbg = $theme->setting_file_url('loginbackgroundimage', 'loginbackgroundimage');
    if (!empty($loginbg)) {
        $content .= 'body.pagelayout-login #page { background-image: url("' . $loginbg . '"); background-size: cover; }';
    }

    return !empty($theme->settings->scss) ? "{$theme->settings->scss}\n{$content}" : $content;
}

/**
 * Inject custom SCSS variables before the main SCSS
 */
function theme_adorsys_theme_v2_get_pre_scss($theme) {
    $scss = '';
    $configurable = [
        'brandcolor' => ['primary'],
    ];

    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        foreach ($targets as $target) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }
    }

    if (defined('BEHAT_SITE_RUNNING')) {
        $scss .= "\$behatsite: true;\n";
    }

    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Return fallback compiled CSS (if SCSS not available)
 */
function theme_adorsys_theme_v2_get_precompiled_css() {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/adorsys_theme_v2/style/main.css');
}