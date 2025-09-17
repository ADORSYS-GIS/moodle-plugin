<?php

namespace theme_adorsys_theme_v1\output;

defined('MOODLE_INTERNAL') || die();

use core_renderer;
use moodle_page;

class theme_adorsys_theme_v1 extends core_renderer {

    /**
     * Returns a context array for standard page layouts.
     */
    public function get_template_context(): array {
        global $SITE, $PAGE;

        $blockshtml = $this->blocks('side-pre');
        $hasblocks = strpos($blockshtml, 'data-block=') !== false;

        return [
            'output' => $this,
            'sitename' => format_string($SITE->shortname, true, ['context' => \context_course::instance(SITEID)]),
            'bodyattributes' => $this->body_attributes(),
            'sidepreblocks' => $blockshtml,
            'hasblocks' => $hasblocks,
            'hasnavbar' => empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar(),
            'navbar' => $this->navbar(),
            'pageheading' => $PAGE->heading,
            'courseheader' => $this->course_header(),
            'maincontent' => $this->main_content(),
            'coursecontentheader' => $this->course_content_header(),
            'coursecontentfooter' => $this->course_content_footer(),
            'coursefooter' => $this->course_footer(),
            'hasfooter' => empty($PAGE->layout_options['nofooter']),
            'footer' => $this->standard_footer_html(),
            'standardendhtml' => $this->standard_end_of_body_html(),
        ];
    }

    /**
     * Minimal template context for login, secure, etc.
     */
    public function get_minimal_context(): array {
        return [
            'output' => $this,
            'bodyattributes' => $this->body_attributes(),
            'maincontent' => $this->main_content(),
            'standardendhtml' => $this->standard_end_of_body_html(),
        ];
    }
}
