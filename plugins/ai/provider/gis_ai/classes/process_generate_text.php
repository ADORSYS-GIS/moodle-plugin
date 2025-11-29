<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai;

defined('MOODLE_INTERNAL') || die();

use aiprovider_gis_ai\helpers\sanitizer;
use aiprovider_gis_ai\helpers\env_loader;
use aiprovider_gis_ai\helpers\logger;
use aiprovider_gis_ai\api\http_client;
use aiprovider_gis_ai\api\rust_bridge;
use aiprovider_gis_ai\api\response_normalizer;
use aiprovider_gis_ai\analytics\usage_tracker;

/**
 * Processor for core_ai\aiactions\generate_text.
 *
 * Note: We keep return type generic; placement extracts text via get_text()/__toString().
 */
final class process_generate_text extends abstract_processor {
    /**
     * Process the action and return a simple response object with get_text().
     * @param object $action The action instance (expected: \core_ai\aiactions\generate_text)
     * @return object
     */
    public function process($action) {
    global $USER, $DB;
    
    // Initialize performance tracking.
    $startTime = microtime(true);
    logger::init_request();
    
    try {
        // Extract fields in a defensive way to avoid tight coupling.
        $prompt = null;
        if (is_object($action)) {
            if (method_exists($action, 'get_prompttext')) {
                $prompt = (string)$action->get_prompttext();
            } elseif (property_exists($action, 'prompttext')) {
                $prompt = (string)$action->prompttext;
            } elseif (property_exists($action, 'prompt')) {
                $prompt = (string)$action->prompt;
            }
        }
        $prompt = (string)($prompt ?? '');
        
        // Validate prompt before processing.
        $validation = sanitizer::validate_prompt($prompt);
        if (!$validation['valid']) {
            logger::error('Prompt validation failed', ['errors' => $validation['errors']]);
            return $this->create_error_response(implode(', ', $validation['errors']));
        }
        $prompt = $validation['sanitized'];

        $userid = 0;
        if (is_object($action)) {
            if (method_exists($action, 'get_userid')) {
                $userid = (int)$action->get_userid();
            } elseif (property_exists($action, 'userid')) {
                $userid = (int)$action->userid;
            }
        }
        $contextid = 0;
        if (is_object($action)) {
            if (method_exists($action, 'get_contextid')) {
                $contextid = (int)$action->get_contextid();
            } elseif (property_exists($action, 'contextid')) {
                $contextid = (int)$action->contextid;
            }
        }
        
        // Check rate limits before processing.
        logger::log_api_request('generate_text', ['prompt_length' => strlen($prompt)], $userid);
        
        // Simple rate limiting check.
        $cache = \cache::make('aiprovider_gis_ai', 'rate_limits');
        $key = 'user_' . ($userid ?: $USER->id) . '_' . date('Y-m-d-H');
        $usage = $cache->get($key) ?: ['requests' => 0, 'tokens' => 0];
        
        $maxRequests = (int)(get_config('aiprovider_gis_ai', 'requests_per_hour') ?: 60);
        if ($maxRequests > 0 && $usage['requests'] >= $maxRequests) {
            logger::error('Rate limit exceeded', ['user' => $userid, 'requests' => $usage['requests']]);
            return $this->create_error_response(get_string('ratelimitexceeded', 'aiprovider_gis_ai'));
        }
        
        // Update usage counter.
        $usage['requests']++;
        $cache->set($key, $usage);

        $useremail = '';
        try {
            if ($userid > 0) {
                $useremail = (string)($DB->get_field('user', 'email', ['id' => $userid]) ?: '');
            }
            if ($useremail === '' && isset($USER->email)) {
                $useremail = (string)$USER->email;
            }
            if ($useremail !== '') {
                $useremail = sanitizer::sanitize_email($useremail);
            }
        } catch (\Throwable $e) {
            // If email resolution fails, proceed without it.
            $useremail = '';
            logger::warning('Failed to resolve user email', ['user' => $userid, 'error' => $e->getMessage()]);
        }

        // Decide backend: rust_bridge in ffi/api mode, otherwise HTTP client.
        $mode = strtolower(env_loader::get('AI_RUST_MODE', ''));
        $raw = [];
        $backendUsed = 'unknown';
        
        try {
            if ($mode === 'ffi' || $mode === 'api') {
                $backendUsed = 'rust_bridge';
                logger::info('Using Rust backend', ['mode' => $mode]);
                $raw = rust_bridge::send_prompt($prompt, $useremail, []);
            } else {
                $backendUsed = 'http_client';
                logger::info('Using HTTP backend');
                $raw = http_client::send_prompt($prompt, $useremail, [], false);
            }
        } catch (\Throwable $e) {
            logger::exception($e, 'Backend request failed');
            return $this->create_error_response(get_string('apiresponseerror', 'aiprovider_gis_ai', $e->getMessage()));
        }

        $norm = response_normalizer::process($raw);
        if (empty($norm['content'])) {
            logger::error('Empty response from backend', ['backend' => $backendUsed, 'raw' => $raw]);
            return $this->create_error_response(get_string('emptyresponse', 'aiprovider_gis_ai'));
        }
        
        // Update token usage.
        $tokensUsed = $norm['tokens'] ?? 0;
        if ($tokensUsed > 0) {
            $usage['tokens'] += $tokensUsed;
            $cache->set($key, $usage);
        }
        
        // Log usage best-effort.
        try {
            usage_tracker::log_interaction($userid ?: (int)($USER->id ?? 0), $prompt, $norm, true, $contextid, 0);
        } catch (\Throwable $e) {
            logger::warning('Failed to log usage', ['error' => $e->getMessage()]);
        }
        
        // Log performance metrics.
        $duration = microtime(true) - $startTime;
        logger::log_api_response('generate_text', $norm, $duration, $tokensUsed);
        logger::log_performance();

        // Return a lightweight response wrapper exposing get_text and __toString.
        return new class($norm['content']) {
            private string $text;
            public function __construct(string $text) { $this->text = $text; }
            public function get_text(): string { return $this->text; }
            public function __toString(): string { return $this->text; }
        };
        
        } catch (\Throwable $e) {
            logger::exception($e, 'Unexpected error in text generation');
            return $this->create_error_response(get_string('processingfailed', 'aiprovider_gis_ai'));
        }
    }
    
    /**
     * Create an error response.
     *
     * @param string $message Error message
     * @return object
     */
    private function create_error_response(string $message): object {
        return new class($message) {
            private string $text;
            private bool $isError = true;
            
            public function __construct(string $text) { $this->text = $text; }
            public function get_text(): string { return $this->text; }
            public function __toString(): string { return $this->text; }
            public function is_error(): bool { return $this->isError; }
        };
    }
}