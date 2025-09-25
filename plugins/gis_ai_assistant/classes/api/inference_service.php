<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AI inference service for OpenAI-compatible APIs.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant\api;

use local_gis_ai_assistant\exceptions\ai_exception;
use local_gis_ai_assistant\exceptions\rate_limit_exception;
use local_gis_ai_assistant\exceptions\configuration_exception;
use local_gis_ai_assistant\util\crypto_helper;

defined('MOODLE_INTERNAL') || die();
 
 // Ensure plugin library functions are available.
 require_once(__DIR__ . '/../../lib.php');

// Explicitly require exception classes to avoid autoload timing issues in some PHPUnit environments.
require_once(__DIR__ . '/../exceptions/ai_exception.php');
require_once(__DIR__ . '/../exceptions/rate_limit_exception.php');
require_once(__DIR__ . '/../exceptions/configuration_exception.php');

/**
 * Service for making AI inference requests to OpenAI-compatible endpoints.
 */
class inference_service {
    
    /** @var array Configuration */
    private $config;
    
    /** @var \cache Cache instance */
    private $cache;
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->config = local_gis_ai_assistant_get_config();
        $this->cache = \cache::make('local_gis_ai_assistant', 'responses');
        
        if (!local_gis_ai_assistant_is_configured()) {
            throw new configuration_exception('AI service not properly configured');
        }
    }
    
    /**
     * Make a chat completion request.
     *
     * @param string $message User message
     * @param array $options Additional options
     * @return array Response data
     * @throws ai_exception
     */
    public function chat_completion($message, $options = []) {
        global $USER;
        
        $starttime = microtime(true);
        
        // Sanitize input.
        $message = local_gis_ai_assistant_sanitize_input($message);
        if (empty($message)) {
            throw new ai_exception('error_empty_message', 'local_gis_ai_assistant');
        }
        
        // Check rate limits.
        $estimatedtokens = $this->estimate_tokens($message);
        $ratelimit = local_gis_ai_assistant_check_rate_limit($USER->id, $estimatedtokens);
        if (!$ratelimit['allowed']) {
            $resetminutes = ceil(($ratelimit['reset_time'] - time()) / 60);
            throw new rate_limit_exception($resetminutes);
        }
        
        // Prepare request data.
        $requestdata = $this->prepare_request_data($message, $options);
        
        // Check cache if enabled.
        if ($this->config['enable_cache']) {
            $cachekey = local_gis_ai_assistant_generate_cache_key($message, $requestdata);
            $cached = $this->cache->get($cachekey);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        try {
            // Make API request.
            $response = $this->make_api_request($requestdata);
            
            // Process response.
            $result = $this->process_response($response);
            
            // Update rate limits.
            $usage = $result['usage'] ?? [];
            local_gis_ai_assistant_update_rate_limit($USER->id, $usage['total_tokens'] ?? 0);
            
            // Log request.
            $responsetime = (microtime(true) - $starttime) * 1000;
            local_gis_ai_assistant_log_request(
                $USER->id,
                $requestdata['model'],
                $usage,
                $responsetime,
                'success',
                null,
                $message,
                $result['content'] ?? ''
            );
            
            // Cache response if enabled.
            if ($this->config['enable_cache'] && isset($cachekey)) {
                $this->cache->set($cachekey, $result);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Log error.
            $responsetime = (microtime(true) - $starttime) * 1000;
            local_gis_ai_assistant_log_request(
                $USER->id,
                $requestdata['model'],
                [],
                $responsetime,
                'error',
                $e->getMessage(),
                $message,
                ''
            );
            
            throw new ai_exception('error_api_request_failed', 'local_gis_ai_assistant', '', $e->getMessage());
        }
    }
    
    /**
     * Make a streaming chat completion request.
     *
     * @param string $message User message
     * @param callable $callback Callback for streaming chunks
     * @param array $options Additional options
     * @return array Final response data
     * @throws ai_exception
     */
    public function chat_completion_stream($message, $callback, $options = []) {
        global $USER;
        
        $starttime = microtime(true);
        
        // Sanitize input.
        $message = local_gis_ai_assistant_sanitize_input($message);
        if (empty($message)) {
            throw new ai_exception('Empty message provided');
        }
        
        // Check rate limits.
        $estimatedtokens = $this->estimate_tokens($message);
        $ratelimit = local_gis_ai_assistant_check_rate_limit($USER->id, $estimatedtokens);
        if (!$ratelimit['allowed']) {
            $resetminutes = ceil(($ratelimit['reset_time'] - time()) / 60);
            throw new rate_limit_exception("Rate limit exceeded. Try again in {$resetminutes} minutes.");
        }
        
        // Prepare request data.
        $requestdata = $this->prepare_request_data($message, $options);
        $requestdata['stream'] = true;
        
        try {
            // Make streaming API request.
            $result = $this->make_streaming_api_request($requestdata, $callback);
            
            // Update rate limits.
            $usage = $result['usage'] ?? [];
            local_gis_ai_assistant_update_rate_limit($USER->id, $usage['total_tokens'] ?? 0);
            
            // Log request.
            $responsetime = (microtime(true) - $starttime) * 1000;
            local_gis_ai_assistant_log_request(
                $USER->id,
                $requestdata['model'],
                $usage,
                $responsetime,
                'success',
                null,
                $message,
                ''
            );
            
            return $result;
            
        } catch (\Exception $e) {
            // Log error.
            $responsetime = (microtime(true) - $starttime) * 1000;
            local_gis_ai_assistant_log_request(
                $USER->id,
                $requestdata['model'],
                [],
                $responsetime,
                'error',
                $e->getMessage(),
                $message,
                ''
            );
            
            throw new ai_exception('error_api_request_failed', 'local_gis_ai_assistant', '', $e->getMessage());
        }
    }
    
    /**
     * Prepare request data for API call.
     *
     * @param string $message User message
     * @param array $options Additional options
     * @return array Request data
     */
    private function prepare_request_data($message, $options = []) {
        global $USER;
        
        $messages = [];
        
        // Add system prompt if configured.
        if (!empty($this->config['system_prompt'])) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->config['system_prompt']
            ];
        }
        
        // Add user message.
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        return [
            'model' => $options['model'] ?? $this->config['model'],
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
            'temperature' => $options['temperature'] ?? $this->config['temperature'],
            'user' => $USER->email, // For tracking/analytics on API side
        ];
    }
    
    /**
     * Make API request to OpenAI-compatible endpoint.
     *
     * @param array $data Request data
     * @return array Response data
     * @throws \Exception
     */
    private function make_api_request($data) {
        global $USER;
        
        $url = rtrim($this->config['base_url'], '/') . '/chat/completions';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['api_key'],
            'x-user-email: ' . $USER->email, // Custom header for user identification
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Moodle-AI-Plugin/1.0',
        ]);
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }
        
        if ($httpcode !== 200) {
            $errordata = json_decode($response, true);
            $errormsg = $errordata['error']['message'] ?? 'HTTP ' . $httpcode;
            throw new \Exception($errormsg);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Make streaming API request.
     *
     * @param array $data Request data
     * @param callable $callback Callback for chunks
     * @return array Final response data
     * @throws \Exception
     */
    private function make_streaming_api_request($data, $callback) {
        global $USER;
        
        $url = rtrim($this->config['base_url'], '/') . '/chat/completions';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['api_key'],
            'x-user-email: ' . $USER->email,
            'Accept: text/event-stream',
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_WRITEFUNCTION => function($ch, $data) use ($callback) {
                return $this->handle_stream_chunk($data, $callback);
            },
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Moodle-AI-Plugin/1.0',
        ]);
        
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }
        
        if ($httpcode !== 200) {
            throw new \Exception('HTTP ' . $httpcode);
        }
        
        return $this->final_response ?? [];
    }
    
    /**
     * Handle streaming chunk.
     *
     * @param string $chunk Data chunk
     * @param callable $callback Callback function
     * @return int Bytes processed
     */
    private function handle_stream_chunk($chunk, $callback) {
        static $buffer = '';
        
        $buffer .= $chunk;
        $lines = explode("\n", $buffer);
        $buffer = array_pop($lines); // Keep incomplete line in buffer.
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || !str_starts_with($line, 'data: ')) {
                continue;
            }
            
            $data = substr($line, 6); // Remove "data: " prefix.
            
            if ($data === '[DONE]') {
                break;
            }
            
            $json = json_decode($data, true);
            if ($json && isset($json['choices'][0]['delta']['content'])) {
                $content = $json['choices'][0]['delta']['content'];
                call_user_func($callback, $content);
            }
            
            // Store final response data.
            if ($json && isset($json['usage'])) {
                $this->final_response = $json;
            }
        }
        
        return strlen($chunk);
    }
    
    /**
     * Process API response.
     *
     * @param array $response Raw API response
     * @return array Processed response
     * @throws ai_exception
     */
    private function process_response($response) {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new ai_exception('error_invalid_response', 'local_gis_ai_assistant');
        }
        
        return [
            'content' => $response['choices'][0]['message']['content'],
            'model' => $response['model'] ?? '',
            'usage' => $response['usage'] ?? [],
            'finish_reason' => $response['choices'][0]['finish_reason'] ?? '',
        ];
    }
    
    /**
     * Estimate tokens for rate limiting.
     *
     * @param string $text Text to estimate
     * @return int Estimated token count
     */
    private function estimate_tokens($text) {
        // Rough estimation: ~4 characters per token for English text.
        return (int) ceil(strlen($text) / 4);
    }
}
