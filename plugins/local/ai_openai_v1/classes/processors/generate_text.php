<?php
namespace local_ai_openai_v1\processors;

defined('MOODLE_INTERNAL') || die();

use local_ai_openai_v1\process_manager;

class generate_text extends \core_ai\process_base {

    public function process(\core_ai\actions\base $action): \core_ai\responses\base {
        $prompt = $action->get_configuration('prompttext'); // The parameter name is 'prompttext'.

        try {
            $manager = process_manager::get_instance();
            $rust_response = $manager->process_prompt($prompt);

            if (!empty($rust_response['error'])) {
                 throw new \Exception($rust_response['error']);
            }

            return new \core_ai\responses\generate_text(
                $action,
                $rust_response['ai_response_text']
            );

        } catch (\Exception $e) {
            return new \core_ai\responses\generate_text(
                $action,
                '',
                $e->getMessage()
            );
        }
    }
}