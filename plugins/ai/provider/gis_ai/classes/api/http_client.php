<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\api;

defined('MOODLE_INTERNAL') || die();

/**
 * OpenAI-compatible HTTP client used by processors.
 * Supports non-streaming and streaming (SSE-like) responses.
 */
final class http_client {
    /** Resolve config value from ENV or plugin config. */
    private static function cfg(string $envkey, string $cfgkey, ?string $default = null): string {
        $env = getenv($envkey);
        if ($env !== false && $env !== '') {
            return (string)$env;
        }
        $cfg = get_config('aiprovider_gis_ai');
        if (isset($cfg->{$cfgkey}) && $cfg->{$cfgkey} !== '') {
            return (string)$cfg->{$cfgkey};
        }
        return (string)($default ?? '');
    }

    /** Send prompt to /responses. */
    public static function send_prompt(string $prompt, string $useremail, array $options = [], bool $stream = false): array {
        $baseurl = rtrim(self::cfg('OPENAI_BASE_URL', 'baseurl', 'https://api.openai.com/v1'), '/');
        $apikey  = self::cfg('OPENAI_API_KEY', 'apikey', '');
        $model   = self::cfg('OPENAI_MODEL', 'model', 'gpt-4o');
        $timeout = (int)(self::cfg('OPENAI_TIMEOUT', 'timeout', '30'));

        $endpoint = $baseurl . '/responses';
        $payload = ['model' => $model, 'input' => (string)$prompt] + $options;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
            'x-user-email: ' . $useremail,
        ];

        if ($stream) {
            // Raw cURL for streaming.
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
                echo $data; // Placement is responsible for correct headers.
                flush();
                return strlen($data);
            });
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_exec($ch);
            curl_close($ch);
            return ['streaming' => true];
        }

        // Moodle curl wrapper for non-streaming calls.
        global $CFG;
        if (!class_exists('curl')) {
            require_once($CFG->libdir . '/filelib.php');
        }
        $curl = new \curl();
        $opts = [
            'timeout' => $timeout,
            'CURLOPT_HTTPHEADER' => $headers,
            'RETURNTRANSFER' => true,
        ];
        $resp = $curl->post($endpoint, $json, $opts);
        $info = method_exists($curl, 'get_info') ? $curl->get_info() : [];
        $http = (int)($info['http_code'] ?? 0);
        if ($resp === false || $http >= 400) {
            throw new \RuntimeException('AI endpoint call failed' . ($http ? ': HTTP ' . $http : ''));
        }
        $data = json_decode((string)$resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON from AI endpoint: ' . json_last_error_msg());
        }
        return $data;
    }
}
