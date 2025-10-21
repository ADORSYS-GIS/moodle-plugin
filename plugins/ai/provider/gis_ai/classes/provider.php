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
        $actions = [];
        if (class_exists(\aiprovider_gis_ai\process_generate_text::class)) {
            $actions[] = \core_ai\aiactions\generate_text::class;
        }
        return $actions;
    }

    /**
     * Whether the provider is configured and can be used.
     * Checks ENV first, then plugin config fallback.
     */
    public function is_provider_configured(): bool {
        $apikey = \aiprovider_gis_ai\helpers\env_loader::get('OPENAI_API_KEY', '');
        return $apikey !== '';
    }

    /**
     * Optional provider healthcheck for admin UI.
     * Non-fatal readiness check for FFI lib or HTTP endpoint reachability.
     *
     * @return array{ok:bool, message:string}
     */
    public function healthcheck(): array {
        $result = ['ok' => false, 'message' => 'Unknown'];
        try {
            $mode = \aiprovider_gis_ai\helpers\env_loader::get('AI_RUST_MODE', 'ffi');
            if ($mode === 'ffi') {
                $libpath = \aiprovider_gis_ai\helpers\env_loader::get('AI_RUST_LIB_PATH', '/usr/local/lib/libai_rust.so');
                if (file_exists($libpath)) {
                    $result = ['ok' => true, 'message' => 'FFI library found'];
                } else {
                    $result = ['ok' => false, 'message' => 'FFI library not found: ' . $libpath];
                }
            } else {
                $endpoint = rtrim(\aiprovider_gis_ai\helpers\env_loader::get('AI_RUST_ENDPOINT', 'http://127.0.0.1:8080'), '/') . '/health';
                global $CFG;
                if (!class_exists('curl')) {
                    require_once($CFG->libdir . '/filelib.php');
                }
                $curl = new \curl();
                $resp = $curl->get($endpoint);
                $info = method_exists($curl, 'get_info') ? $curl->get_info() : [];
                $http = (int)($info['http_code'] ?? 0);
                $result = ['ok' => ($http >= 200 && $http < 300), 'message' => 'HTTP ' . $http];
            }
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => $e->getMessage()];
        }
        return $result;
    }
}
