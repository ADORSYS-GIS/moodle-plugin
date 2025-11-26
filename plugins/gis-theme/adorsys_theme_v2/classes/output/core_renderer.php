<?php

namespace theme_adorsys_theme_v2\output;

defined('MOODLE_INTERNAL') || die();

// Use Moodle's core renderer as the base
use \core_renderer;

/**
 * Renderer for adorsys_theme_v2 child theme.
 */
class theme_adorsys_theme_v2 extends \core_renderer {
    /**
     * Renders the navbar template.
     */
    public function navbar(): string {
        global $USER, $PAGE, $CFG;
        
        $sitename = $PAGE->heading ?? 'Moodle';
        $context = [
            'sitename' => format_string($sitename),
            'config' => [
                'wwwroot' => $CFG->wwwroot ?? '/',
            ],
            'navitems' => [
                ['title' => 'Dashboard', 'url' => new moodle_url('/my/')], 
                ['title' => 'Courses', 'url' => new moodle_url('/course/index.php')],
            ],
            'isloggedin' => isloggedin(),
        ];
        
        if (isloggedin()) {
            $context['userpictureurl'] = (new \user_picture($USER))->get_url($PAGE)->out();
            $context['profileurl'] = (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out();
            $context['logouturl'] = (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out();
        }
        
        return $this->render_from_template('core/navbar', $context);
    }
}
