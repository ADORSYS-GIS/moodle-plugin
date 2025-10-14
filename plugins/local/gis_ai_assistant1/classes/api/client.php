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

        $ch = curl_init($endpoint);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
            'x-user-email: ' . $useremail,
        ];

        if ($stream) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
                echo $data;
                flush();
                return strlen($data);
            });
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($stream) {
            curl_close($ch);
            return ['streaming' => true];
        }

        curl_close($ch);

        if ($errno || $http >= 400) {
            logger::error('AI endpoint call failed', ['errno' => $errno, 'error' => $err, 'http' => $http, 'endpoint' => $endpoint]);
            throw new \RuntimeException('AI endpoint call failed: ' . ($err ?: 'HTTP ' . $http));
        }

        $data = json_decode($resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logger::error('Invalid JSON from AI endpoint: ' . json_last_error_msg());
            throw new \RuntimeException('Invalid JSON from AI endpoint');
        }
        return $data;
    }
}
