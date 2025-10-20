<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiplacement_gis_ai_chat\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class feedback_form extends \moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('textarea', 'feedback', get_string('feedback', 'aiplacement_gis_ai_chat'));
        $mform->addRule('feedback', null, 'required');
        $this->add_action_buttons();
    }
}
