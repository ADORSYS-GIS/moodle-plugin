<?php
namespace local_ai_openai_v1;

defined('MOODLE_INTERNAL') || die();

// It MUST extend the core Moodle provider class.
class provider extends \core_ai\provider {

    // The method MUST be named get_action_list().
    // It MUST return an array of full class names.
    public function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
        ];
    }

    // This part was correct.
    public function is_provider_configured(): bool {
        $api_key = get_config('local_ai_openai_v1', 'api_key');
        $rust_binary = get_config('local_ai_openai_v1', 'rust_binary_path');

        return !empty($api_key) && !empty($rust_binary) && is_executable($rust_binary);
    }
}