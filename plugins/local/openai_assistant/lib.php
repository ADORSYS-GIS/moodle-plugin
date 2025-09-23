<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Plugin navigation hook
 */
function local_openai_assistant_extend_navigation(global_navigation $navigation) {
    global $PAGE;
    
    if (isloggedin() && !isguestuser()) {
        $node = $navigation->add(
            get_string('openai_assistant', 'local_openai_assistant'),
            new moodle_url('/local/openai_assistant/chat.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'openai_assistant'
        );
        $node->showinflatnavigation = true;
    }
}
