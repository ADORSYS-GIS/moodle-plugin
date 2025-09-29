<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Returns the main SCSS content for the theme.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_adorsys_theme_v2_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $fs = get_file_storage();

    $context = context_system::instance();
    if ($setting = $fs->get_file($context->id, 'theme_adorsys_theme_v2', 'setting', null, '/', 'custom.scss')) {
        $scss .= file_get_contents($setting->get_filepath());
    }

    // Prepend the parent theme's SCSS.
    $scss .= file_get_contents($CFG->dirroot . '/theme/adorsys_theme_v1/scss/main.scss');

    // Add our own SCSS.
    $scss .= file_get_contents(__DIR__ . '/src/styles/main.scss');

    return $scss;
}

/**
 * Post-processes the SCSS for the theme.
 *
 * @param string $css The compiled CSS.
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_adorsys_theme_v2_scss_postprocess($css, $theme) {
    // Add any post-processing here if needed.
    return $css;
}

/**
 * Serves the custom SCSS file.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_adorsys_theme_v2_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel === CONTEXT_SYSTEM && $filearea === 'setting') {
        return theme_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options);
    }
    return false;
}
