<?php
declare(strict_types=1);

namespace local_gis_ai_assistant1\api;

defined('MOODLE_INTERNAL') || die();

use local_gis_ai_assistant1\helpers\env_loader;
use local_gis_ai_assistant1\helpers\logger;
use local_gis_ai_assistant1\helpers\sanitizer;

class rust_bridge {
    /**
     * Send a prompt via Rust backend using FFI or HTTP API depending on AI_RUST_MODE.
     *
     * @param string $prompt
     * @param string $useremail
     * @param array $options
     * @return array
     */
    public static function send_prompt(string $prompt, string $useremail, array $options = []): array {
        $prompt = sanitizer::sanitize_prompt($prompt);
        $useremail = sanitizer::sanitize_email($useremail);

        $mode = strtolower(env_loader::get('AI_RUST_MODE', 'ffi'));
        if ($mode === 'ffi') {
            try {
                return self::send_via_ffi($prompt, $useremail, $options);
            } catch (\Throwable $e) {
                logger::exception($e, 'FFI bridge failed, falling back to API');
                return self::send_via_api($prompt, $useremail, $options);
            }
        }
        if ($mode === 'api') {
            return self::send_via_api($prompt, $useremail, $options);
        }
        throw new \moodle_exception('invalidmode', 'local_gis_ai_assistant1', '', $mode);
    }

    /**
     * Call into Rust shared library via PHP FFI.
     *
     * @param string $prompt
     * @param string $useremail
     * @param array $options
     * @return array
     */
    private static function send_via_ffi(string $prompt, string $useremail, array $options): array {
        if (!extension_loaded('ffi')) {
            throw new \RuntimeException('PHP FFI extension not available');
        }

        $libpath = env_loader::get('AI_RUST_LIB_PATH', '/usr/local/lib/libai_rust.so');
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
            logger::error('Invalid JSON returned by Rust FFI: ' . json_last_error_msg());
            throw new \RuntimeException('Invalid JSON from Rust FFI');
        }
        return $data;
    }

    /**
     * Call Rust microservice over HTTP.
     *
     * @param string $prompt
     * @param string $useremail
     * @param array $options
     * @return array
     */
    private static function send_via_api(string $prompt, string $useremail, array $options): array {
        $endpoint = rtrim(env_loader::get('AI_RUST_ENDPOINT', 'http://127.0.0.1:8080'), '/') . '/send_prompt';
        $payload = [
            'prompt' => $prompt,
            'options' => $options,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type: application/json',
            'x-user-email: ' . $useremail,
        ];
        $apikey = env_loader::get('AI_RUST_API_KEY', '');
        if ($apikey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apikey;
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => env_loader::get_int('AI_TIMEOUT', 30),
        ]);

        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || $http >= 400) {
            logger::error('Rust API request failed', ['errno' => $errno, 'error' => $err, 'http' => $http, 'endpoint' => $endpoint]);
            throw new \RuntimeException('Rust API request failed: ' . ($err ?: 'HTTP ' . $http));
        }

        $data = json_decode($resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logger::error('Invalid JSON from Rust API: ' . json_last_error_msg());
            throw new \RuntimeException('Invalid JSON from Rust API');
        }
        return $data;
    }
}
