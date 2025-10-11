<?php
namespace local_gis_ai_assistant1\forms;

defined('MOODLE_INTERNAL') || die();

class feedback_form extends \moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('textarea', 'feedback', get_string('feedback'));
        $mform->addRule('feedback', null, 'required');
        $this->add_action_buttons();
    }
}
