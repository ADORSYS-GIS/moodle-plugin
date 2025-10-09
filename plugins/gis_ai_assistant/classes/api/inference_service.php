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
    
    /** @var string Accumulated streamed content */
    private $assembled_content = '';
    
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
            
            // Log request (best-effort; do not break on logging errors).
            $responsetime = (microtime(true) - $starttime) * 1000;
            try {
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
            } catch (\Throwable $logex) {
                if (function_exists('debugging')) {
                    debugging('AI log_request failed (success path): ' . $logex->getMessage(), DEBUG_DEVELOPER);
                }
            }
            
            // Cache response if enabled.
            if ($this->config['enable_cache'] && isset($cachekey)) {
                $this->cache->set($cachekey, $result);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Log error (best-effort).
            $responsetime = (microtime(true) - $starttime) * 1000;
            try {
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
            } catch (\Throwable $logex) {
                if (function_exists('debugging')) {
                    debugging('AI log_request failed (error path): ' . $logex->getMessage(), DEBUG_DEVELOPER);
                }
            }
            
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
            // Attach assembled content so caller (stream.php) can format as HTML for final event.
            if (is_array($result)) {
                $result['assembled_content'] = (string)$this->assembled_content;
            } else {
                $result = ['assembled_content' => (string)$this->assembled_content];
            }
            
            // Update rate limits.
            $usage = $result['usage'] ?? [];
            local_gis_ai_assistant_update_rate_limit($USER->id, $usage['total_tokens'] ?? 0);
            
            // Log request (normalize assembled content so history persists formatted newlines).
            $responsetime = (microtime(true) - $starttime) * 1000;
            $assembledForLog = (string)$this->assembled_content;
            if ($assembledForLog !== '') {
                $assembledForLog = str_replace(["\r\n", "\r"], "\n", $assembledForLog);
                $assembledForLog = preg_replace('/\\\r\\\n|\\\r|\\\n/', "\n", $assembledForLog);
                $assembledForLog = preg_replace('/\\\t/', "\t", $assembledForLog);
                $assembledForLog = preg_replace("/\n{3,}/", "\n\n", $assembledForLog);
            }
            local_gis_ai_assistant_log_request(
                $USER->id,
                $requestdata['model'],
                $usage,
                $responsetime,
                'success',
                null,
                $message,
                $assembledForLog
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
        
        // Build base URL candidates. Many OpenAI-compatible providers require /v1.
        $base = rtrim($this->config['base_url'], '/');
        $preferresponses = $this->is_responses_api();
        $hasversion = preg_match('~/v\d+(/|$)~', $base) === 1;
        $isazure = strpos($base, '.azure.com') !== false;
        $bases = [$base];
        if (!$hasversion && !$isazure) {
            array_unshift($bases, $base . '/v1');
        }

        // Build ordered attempts across base candidates and endpoint preferences.
        $endpoints = $preferresponses ? ['/responses', '/chat/completions'] : ['/chat/completions', '/responses'];
        $attempts = [];
        foreach ($bases as $b) {
            foreach ($endpoints as $ep) {
                $attempts[] = [$b, $ep, $ep === '/responses'];
            }
        }

        $lastErrMsg = null;
        // Reset any previously assembled content before attempting to stream.
        $this->assembled_content = '';
        foreach ($attempts as $attempt) {
            [$b, $endpoint, $useresponses] = $attempt;
            $url = $b . $endpoint;
            // Normalize potential duplicate segments if base already contains part of endpoint.
            $url = preg_replace('#/chat/chat/#', '/chat/', $url);
            $url = preg_replace('#/responses/responses#', '/responses', $url);

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->config['api_key'],
                'x-user-email: ' . $USER->email,
                'Accept: application/json',
            ];

            $payload = $useresponses ? $this->build_responses_payload($data) : $data;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
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

            if ($httpcode === 200) {
                return json_decode($response, true);
            }

            if ($httpcode === 404 || $httpcode === 405) {
                // Try next candidate (different endpoint or base).
                $errordata = json_decode($response, true);
                $provider = $errordata['error']['message'] ?? $errordata['message'] ?? null;
                $lastErrMsg = 'HTTP ' . $httpcode . ' on ' . $url . ($provider ? ' - ' . $provider : '');
                continue;
            }

            $errordata = json_decode($response, true);
            $provider = $errordata['error']['message'] ?? $errordata['message'] ?? null;
            $errormsg = 'HTTP ' . $httpcode . ' on ' . $url;
            if ($provider) { $errormsg .= ' - ' . $provider; }
            throw new \Exception($errormsg);
        }

        // If we exhausted attempts with only 404/405s, surface the last one.
        throw new \Exception($lastErrMsg ?: 'Unexpected error');
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
        
        // Build base URL candidates. Many OpenAI-compatible providers require /v1.
        $base = rtrim($this->config['base_url'], '/');
        $preferresponses = $this->is_responses_api();
        $hasversion = preg_match('~/v\d+(/|$)~', $base) === 1;
        $isazure = strpos($base, '.azure.com') !== false;
        $bases = [$base];
        if (!$hasversion && !$isazure) {
            array_unshift($bases, $base . '/v1');
        }

        $endpoints = $preferresponses ? ['/responses', '/chat/completions'] : ['/chat/completions', '/responses'];
        $attempts = [];
        foreach ($bases as $b) {
            foreach ($endpoints as $ep) {
                $attempts[] = [$b, $ep, $ep === '/responses'];
            }
        }

        $lastErrMsg = null;
        foreach ($attempts as $attempt) {
            [$b, $endpoint, $useresponses] = $attempt;
            $url = $b . $endpoint;

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->config['api_key'],
                'x-user-email: ' . $USER->email,
                'Accept: text/event-stream',
            ];

            // Reset any previous final response before attempting.
            unset($this->final_response);

            $payload = $useresponses ? $this->build_responses_payload($data, true) : $data;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_WRITEFUNCTION => function($ch, $chunk) use ($callback) {
                    return $this->handle_stream_chunk($chunk, $callback);
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

            if ($httpcode === 200) {
                return $this->final_response ?? [];
            }

            if ($httpcode === 404 || $httpcode === 405) {
                $lastErrMsg = 'HTTP ' . $httpcode . ' on ' . $url;
                continue; // try the next candidate
            }

            throw new \Exception('HTTP ' . $httpcode . ' on ' . $url);
        }

        throw new \Exception($lastErrMsg ?: 'Unexpected error');
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
        static $lastevent = '';
        
        $buffer .= $chunk;
        $lines = explode("\n", $buffer);
        $buffer = array_pop($lines); // Keep incomplete line in buffer.
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) { continue; }

            // Track event names when present (Responses API may send named events).
            if (str_starts_with($line, 'event: ')) {
                $lastevent = trim(substr($line, 7));
                continue;
            }

            if (!str_starts_with($line, 'data: ')) { continue; }

            $data = substr($line, 6); // Remove "data: " prefix.

            if ($data === '[DONE]') { break; }

            $json = json_decode($data, true);
            if (!$json) { continue; }

            // Chat Completions delta.
            if (isset($json['choices'][0]['delta']['content'])) {
                $piece = (string)$json['choices'][0]['delta']['content'];
                if ($piece !== '') {
                    $this->assembled_content .= $piece;
                    call_user_func($callback, $piece);
                }
            }

            // Responses API delta format (e.g., response.output_text.delta with {"delta":"text"}).
            if (isset($json['delta']) && is_string($json['delta'])) {
                $piece = (string)$json['delta'];
                if ($piece !== '') {
                    $this->assembled_content .= $piece;
                    call_user_func($callback, $piece);
                }
            }

            // Store final response data when usage or output_text is present.
            if (isset($json['usage'])) {
                $this->final_response = $json;
            } elseif (isset($json['output_text'])) {
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
        // Tolerant extraction across OpenAI-compatible variants.
        $content = null;
        // Chat Completions (classic)
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        } else if (isset($response['choices'][0]['text'])) {
            // Some providers (or completions endpoint) return 'text'.
            $content = $response['choices'][0]['text'];
        } else if (isset($response['content']) && is_string($response['content'])) {
            // Non-standard proxy may return top-level 'content'.
            $content = $response['content'];
        } else if (isset($response['output_text']) && is_string($response['output_text'])) {
            // Responses API convenience field.
            $content = $response['output_text'];
        } else if (isset($response['output']) && is_array($response['output'])) {
            // Responses API structured output: pick first text segment.
            foreach ($response['output'] as $item) {
                if (isset($item['content']) && is_array($item['content'])) {
                    foreach ($item['content'] as $c) {
                        if (($c['type'] ?? '') === 'output_text' && isset($c['text'])) {
                            $content = $c['text'];
                            break 2;
                        }
                    }
                }
            }
        }

        if ($content === null || $content === '') {
            throw new ai_exception('error_invalid_response', 'local_gis_ai_assistant');
        }
        // Normalize line endings and decode common escaped sequences (\\n, \\r, \\t) sometimes returned by proxies.
        if (is_string($content)) {
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            // Convert literal backslash-escaped sequences to real control chars.
            $content = preg_replace('/\\\r\\\n|\\\r|\\\n/', "\n", $content);
            $content = preg_replace('/\\\t/', "\t", $content);
            // Collapse 3+ blank lines to max two, avoid excessive vertical space.
            $content = preg_replace("/\n{3,}/", "\n\n", $content);
        }
        
        // Normalize usage across APIs.
        $usage = $response['usage'] ?? [];
        if ($usage && isset($usage['input_token_count'])) {
            // Responses API naming
            $usage = [
                'prompt_tokens' => $usage['input_token_count'] ?? null,
                'completion_tokens' => $usage['output_token_count'] ?? null,
                'total_tokens' => $usage['total_token_count'] ?? (($usage['input_token_count'] ?? 0) + ($usage['output_token_count'] ?? 0)),
            ];
        }

        $finish = $response['choices'][0]['finish_reason'] ?? ($response['finish_reason'] ?? ($response['status'] ?? ''));

        return [
            'content' => $content,
            'model' => $response['model'] ?? '',
            'usage' => $usage,
            'finish_reason' => is_string($finish) ? $finish : '',
        ];
    }

    /**
     * Whether to use the OpenAI Responses API instead of Chat Completions.
     * Controlled by env OPENAI_USE_RESPONSES ("1", "true" etc.).
     *
     * @return bool
     */
    private function is_responses_api() {
        // Always prefer Chat Completions API
        return false;
        
        // Original code (commented out)
        // $flag = getenv('OPENAI_USE_RESPONSES') ?: ($_SERVER['OPENAI_USE_RESPONSES'] ?? '');
        // return in_array(strtolower((string)$flag), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Build payload for the Responses API from the standard request data.
     *
     * @param array $requestdata Data built by prepare_request_data (contains messages, model, temperature, max_tokens, user)
     * @param bool $stream Whether to enable streaming
     * @return array Payload for /responses
     */
    private function build_responses_payload(array $requestdata, $stream = false) {
        $model = $requestdata['model'] ?? '';
        $temperature = $requestdata['temperature'] ?? null;
        $maxTokens = $requestdata['max_tokens'] ?? null;

        // Convert messages to a single input string preserving system + user context.
        $input = '';
        if (!empty($requestdata['messages']) && is_array($requestdata['messages'])) {
            foreach ($requestdata['messages'] as $m) {
                $role = $m['role'] ?? 'user';
                $text = $m['content'] ?? '';
                if ($text === '') { continue; }
                if ($role === 'system') {
                    $input .= "[System]\n" . $text . "\n\n";
                } else if ($role === 'user') {
                    $input .= "[User]\n" . $text . "\n\n";
                } else if ($role === 'assistant') {
                    $input .= "[Assistant]\n" . $text . "\n\n";
                } else {
                    $input .= $text . "\n\n";
                }
            }
        }
        $input = trim($input);
        if ($input === '' && isset($requestdata['messages'][0]['content'])) {
            $input = (string)$requestdata['messages'][0]['content'];
        }

        $payload = [
            'model' => $model,
            'input' => $input,
        ];
        if ($temperature !== null) { $payload['temperature'] = $temperature; }
        if ($maxTokens) { $payload['max_output_tokens'] = (int)$maxTokens; }
        if ($stream) { $payload['stream'] = true; }

        return $payload;
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
