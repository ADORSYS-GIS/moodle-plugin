<?php
declare(strict_types=1);

namespace local_gis_ai_assistant1\api;

defined('MOODLE_INTERNAL') || die();

use local_gis_ai_assistant1\helpers\env_loader;
use local_gis_ai_assistant1\helpers\logger;
use local_gis_ai_assistant1\helpers\sanitizer;

class client {
    /**
     * Send prompt to OpenAI-compatible endpoint (prefers /responses).
     *
     * @param string $prompt
     * @param string $useremail
     * @param array $options
     * @param bool $stream If true, echoes chunks directly and returns ['streaming' => true]
     * @return array
     */
    public static function send_prompt(string $prompt, string $useremail, array $options = [], bool $stream = false): array {
        $prompt = sanitizer::sanitize_prompt($prompt);
        $useremail = sanitizer::sanitize_email($useremail);

        $baseurl = rtrim(env_loader::get('OPENAI_BASE_URL'), '/');
        $apikey = env_loader::get('OPENAI_API_KEY');
        $model = env_loader::get('OPENAI_MODEL', 'gpt-4o');
        $timeout = env_loader::get_int('AI_TIMEOUT', 30);

        $endpoint = $baseurl . '/responses';

        $payload = [
            'model' => $model,
            'input' => $prompt,
        ] + $options;

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
            'x-user-email: ' . $useremail,
        ];

        if ($stream) {
            // Streaming mode: keep raw cURL to support write callback.
            // Caller should set response headers appropriately (e.g.,
            // Content-Type: text/event-stream, Cache-Control: no-cache).
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
                echo $data;
                flush();
                return strlen($data);
            });
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $resp = curl_exec($ch);
            // In streaming, we don't validate $resp; we streamed it already.
            curl_close($ch);
            return ['streaming' => true];
        }

        // Non-streaming: use Moodle's curl wrapper for proxy/cert compliance.
        global $CFG; // ensure core libs are available
        if (!class_exists('curl')) {
            // Fallback to include if needed.
            require_once($CFG->libdir . '/filelib.php');
        }
        $curl = new \curl();
        $options = [
            'timeout' => $timeout,
            'CURLOPT_HTTPHEADER' => $headers,
            'RETURNTRANSFER' => true,
        ];
        $resp = $curl->post($endpoint, $json, $options);
        $info = method_exists($curl, 'get_info') ? $curl->get_info() : [];
        $http = (int)($info['http_code'] ?? 0);

        if ($resp === false || $http >= 400) {
            logger::error('AI endpoint call failed', ['http' => $http, 'endpoint' => $endpoint]);
            throw new \RuntimeException('AI endpoint call failed' . ($http ? ': HTTP ' . $http : ''));
        }

        $data = json_decode((string)$resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logger::error('Invalid JSON from AI endpoint: ' . json_last_error_msg());
            throw new \RuntimeException('Invalid JSON from AI endpoint');
        }
        return $data;
    }
}
