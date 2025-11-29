<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\helpers;

defined('MOODLE_INTERNAL') || die();

class sanitizer {
    /** Sanitize prompt text by stripping tags, removing control characters, normalizing whitespace, and truncating. */
    public static function sanitize_prompt(string $prompt, int $maxlen = 2000): string {
        // Strip HTML tags and remove control characters to avoid injection and rendering issues.
        $s = strip_tags($prompt);
        $s = preg_replace('/[\x00-\x1F\x7F]+/u', '', $s) ?? $s;

        // Normalize whitespace to single spaces and trim.
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        $s = trim($s);

        // Truncate safely using mb_* functions.
        if (mb_strlen($s) > $maxlen) {
            $s = mb_substr($s, 0, $maxlen);
        }

        // Replace configured bad words (best-effort, non-fatal).
        $bad = env_loader::get('BAD_WORDS_LIST', '');
        if (!empty($bad)) {
            $words = array_filter(array_map('trim', explode(',', $bad)));
            if (!empty($words)) {
                $quoted = array_map(static fn($w) => preg_quote($w, '/'), $words);
                $pattern = '/(?<=\b)(' . implode('|', $quoted) . ')(?=\b)/iu';
                $s = preg_replace($pattern, '***', $s);
            }
        }

        return $s;
    }

    /** Validate and sanitize an email address. */
    public static function sanitize_email(string $email): string {
        $clean = clean_param($email, PARAM_EMAIL);
        if (empty($clean) || !filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            throw new \moodle_exception('invalidemail', 'aiprovider_gis_ai', '', $email);
        }
        return $clean;
    }
    
    /** Validate and sanitize conversation ID. */
    public static function sanitize_conversation_id(string $conversationId): string {
        $clean = clean_param($conversationId, PARAM_ALPHANUMEXT);
        if (empty($clean) || mb_strlen($clean) < 10 || mb_strlen($clean) > 64) {
            throw new \moodle_exception('invalidconversationid', 'aiplacement_gis_ai_chat');
        }
        return $clean;
    }
    
    /** Validate prompt length and content. */
    public static function validate_prompt(string $prompt, int $maxLength = 2000): array {
        $errors = [];

        $trimmed = trim($prompt);
        if ($trimmed === '') {
            $errors[] = get_string('emptyprompt', 'aiplacement_gis_ai_chat');
        }

        if (mb_strlen($trimmed) > $maxLength) {
            $errors[] = get_string('messagelengthlimit', 'aiplacement_gis_ai_chat', $maxLength);
        }

        // Check for potential injection attempts and control characters.
        $suspiciousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/[\x00-\x08\x0B-\x1F]/u', // control chars
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $prompt)) {
                $errors[] = 'Invalid content detected';
                break;
            }
        }

        // Produce a sanitized version and ensure it's not empty after cleaning.
        $sanitized = self::sanitize_prompt($prompt, $maxLength);
        if ($sanitized === '') {
            $errors[] = 'Invalid content detected';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized,
        ];
    }
    
    /** Validate API configuration. */
    public static function validate_api_config(): array {
        $issues = [];
        
        // Check API key.
        $apiKey = env_loader::get('OPENAI_API_KEY', '');
        if (empty($apiKey)) {
            $issues[] = [
                'type' => 'error',
                'message' => 'API key is required',
                'setting' => 'OPENAI_API_KEY',
            ];
        } elseif (!preg_match('/^sk-[A-Za-z0-9]{20,}$/', $apiKey)) {
            $issues[] = [
                'type' => 'warning',
                'message' => 'API key format may be invalid',
                'setting' => 'OPENAI_API_KEY',
            ];
        }
        
        // Check base URL.
        $baseUrl = env_loader::get('OPENAI_BASE_URL', get_config('aiprovider_gis_ai', 'baseurl'));
        if (empty($baseUrl)) {
            $issues[] = [
                'type' => 'warning',
                'message' => 'Using default OpenAI URL',
                'setting' => 'baseurl',
            ];
        } elseif (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $issues[] = [
                'type' => 'error',
                'message' => 'Invalid base URL format',
                'setting' => 'baseurl',
            ];
        }
        
        return $issues;
    }
    
    /** Validate rate limiting settings. */
    public static function validate_rate_limits(): array {
        $issues = [];
        
        $requestsPerHour = (int)(get_config('aiprovider_gis_ai', 'requests_per_hour') ?: 60);
        $tokensPerHour = (int)(get_config('aiprovider_gis_ai', 'tokens_per_hour') ?: 40000);
        
        if ($requestsPerHour < 0) {
            $issues[] = [
                'type' => 'error',
                'message' => 'Requests per hour cannot be negative',
                'setting' => 'requests_per_hour',
            ];
        } elseif ($requestsPerHour > 1000) {
            $issues[] = [
                'type' => 'warning',
                'message' => 'Very high request limit - monitor costs',
                'setting' => 'requests_per_hour',
            ];
        }
        
        if ($tokensPerHour < 0) {
            $issues[] = [
                'type' => 'error',
                'message' => 'Tokens per hour cannot be negative',
                'setting' => 'tokens_per_hour',
            ];
        } elseif ($tokensPerHour > 100000) {
            $issues[] = [
                'type' => 'warning',
                'message' => 'Very high token limit - monitor costs',
                'setting' => 'tokens_per_hour',
            ];
        }
        
        return $issues;
    }
}
