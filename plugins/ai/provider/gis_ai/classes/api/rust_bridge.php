<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Rust bridge supporting FFI or microservice HTTP mode.
 */
final class rust_bridge {
    /** Public entry to send a prompt via the chosen Rust mode. */
    public static function send_prompt(string $prompt, string $useremail, array $options = []): array {
        $mode = strtolower(\aiprovider_gis_ai\helpers\env_loader::get('AI_RUST_MODE', 'ffi'));
        if ($mode === 'ffi') {
            try {
                return self::send_via_ffi($prompt, $useremail, $options);
            } catch (\Throwable $e) {
                // Fall back to API mode if FFI fails.
                return self::send_via_api($prompt, $useremail, $options);
            }
        }
        if ($mode === 'api') {
            return self::send_via_api($prompt, $useremail, $options);
        }
        throw new \moodle_exception('invalidmode', 'aiprovider_gis_ai', '', $mode);
    }

    /** Call into Rust shared lib via FFI. */
    private static function send_via_ffi(string $prompt, string $useremail, array $options): array {
        if (!extension_loaded('ffi')) {
            throw new \RuntimeException('PHP FFI extension not available');
        }
        $libpath = \aiprovider_gis_ai\helpers\env_loader::get('AI_RUST_LIB_PATH', '/usr/local/lib/libai_rust.so');
        $cdefs = <<<CDEF
            char* ai_send_prompt(const char* prompt, const char* user_email, const char* json_options);
            void  ai_free_string(char* s);
        CDEF;
        try {
            $ffi = \FFI::cdef($cdefs, $libpath);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to load Rust FFI library at {$libpath}: " . $e->getMessage());
        }
        $json_options = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $cptr = $ffi->ai_send_prompt($prompt, $useremail, $json_options);
        if ($cptr == null) {
            throw new \RuntimeException('ai_send_prompt returned null');
        }
        $response_json = \FFI::string($cptr);
        $ffi->ai_free_string($cptr);
        $data = json_decode($response_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON from Rust FFI: ' . json_last_error_msg());
        }
        return $data;
    }

    /** Call Rust microservice over HTTP. */
    private static function send_via_api(string $prompt, string $useremail, array $options): array {
        $endpoint = rtrim(\aiprovider_gis_ai\helpers\env_loader::get('AI_RUST_ENDPOINT', 'http://127.0.0.1:8080'), '/') . '/send_prompt';
        $payload = ['prompt' => $prompt, 'options' => $options];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = ['Content-Type: application/json', 'x-user-email: ' . $useremail];
        $apikey = \aiprovider_gis_ai\helpers\env_loader::get('AI_RUST_API_KEY', '');
        if ($apikey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apikey;
        }
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => (int)\aiprovider_gis_ai\helpers\env_loader::get('AI_TIMEOUT', '30'),
        ]);
        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno || $http >= 400) {
            throw new \RuntimeException('Rust API request failed: ' . ($err ?: 'HTTP ' . $http));
        }
        $data = json_decode((string)$resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON from Rust API: ' . json_last_error_msg());
        }
        return $data;
    }
}
