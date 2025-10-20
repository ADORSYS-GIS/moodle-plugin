<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai;

defined('MOODLE_INTERNAL') || die();

/**
 * GIS AI Provider: bridges Moodle's AI subsystem to an OpenAI-compatible/Rust backend.
 *
 * Implements the Moodle 4.5+ AI Provider contract.
 */
class provider extends \core_ai\provider {
    /**
     * List of AI Actions supported by this provider.
     * Keep this minimal initially; add more actions as their processors are implemented.
     *
     * @return array<int, class-string>
     */
    public function get_action_list(): array {
        // TODO: Add supported actions once corresponding process_* classes are implemented.
        return [];
    }

    /**
     * Whether the provider is configured and can be used.
     * Checks ENV first, then plugin config fallback.
     */
    public function is_provider_configured(): bool {
        $envkey = getenv('OPENAI_API_KEY');
        if ($envkey !== false && $envkey !== '') {
            return true;
        }
        $config = get_config('aiprovider_gis_ai');
        if (!empty($config->apikey)) {
            return true;
        }
        return false;
    }
}
