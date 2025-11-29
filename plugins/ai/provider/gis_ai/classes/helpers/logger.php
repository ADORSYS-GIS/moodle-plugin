<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\helpers;

defined('MOODLE_INTERNAL') || die();

class logger {
    
    /** @var array Performance metrics */
    private static $performance = [];
    
    /** @var float Request start time */
    private static $requestStartTime;
    
    /**
     * Initialize request tracking.
     */
    public static function init_request(): void {
        self::$requestStartTime = microtime(true);
        self::$performance = [];
    }
    
    /** Base logging method with structured output. */
    public static function log(string $message, int $level = DEBUG_NORMAL, array $context = []): void {
        $timestamp = date('c');
        $requestId = self::get_request_id();
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => self::level_to_string($level),
            'message' => $message,
            'request_id' => $requestId,
        ];
        
        if (!empty($context)) {
            $logEntry['context'] = $context;
        }
        
        $line = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            if (env_loader::get_bool('AI_DEBUG')) {
                debugging($message . ' | ' . json_encode($context), $level);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $logfile = env_loader::get('AI_LOG_FILE', '');
        if (!empty($logfile)) {
            @error_log($line . PHP_EOL, 3, $logfile);
        }
    }
    
    /**
     * Convert Moodle debug level to string.
     */
    private static function level_to_string(int $level): string {
        switch ($level) {
            case DEBUG_MINIMAL: return 'ERROR';
            case DEBUG_NORMAL: return 'WARN';
            case DEBUG_DEVELOPER: return 'INFO';
            case DEBUG_ALL: return 'DEBUG';
            default: return 'UNKNOWN';
        }
    }
    
    /**
     * Get or generate request ID for tracking.
     */
    private static function get_request_id(): string {
        static $requestId = null;
        if ($requestId === null) {
            $requestId = uniqid('ai_', true);
        }
        return $requestId;
    }
    
    /**
     * Log API request with rate limiting check.
     */
    public static function log_api_request(string $action, array $params, ?int $userid = null): void {
        global $USER;
        
        if ($userid === null) {
            $userid = $USER->id;
        }
        
        $context = [
            'action' => $action,
            'user_id' => $userid,
            'params_hash' => hash('sha256', json_encode($params)),
            'timestamp' => time(),
        ];
        
        // Check rate limits
        try {
            $rateLimit = self::check_rate_limit($userid);
            $context['rate_limit'] = [
                'allowed' => $rateLimit['allowed'],
                'remaining_requests' => $rateLimit['remaining_requests'],
                'remaining_tokens' => $rateLimit['remaining_tokens'],
            ];
            
            if (!$rateLimit['allowed']) {
                self::error('Rate limit exceeded for action: ' . $action, $context);
                return;
            }
        } catch (\Exception $e) {
            self::error('Failed to check rate limit: ' . $e->getMessage());
        }
        
        self::info('API request: ' . $action, $context);
    }
    
    /**
     * Simple rate limiting check.
     */
    private static function check_rate_limit(int $userid): array {
        $cache = \cache::make('aiprovider_gis_ai', 'rate_limits');
        $key = 'user_' . $userid . '_' . date('Y-m-d-H');
        
        $usage = $cache->get($key) ?: ['requests' => 0, 'tokens' => 0];
        
        $maxRequests = (int)(env_loader::get('AI_REQUESTS_PER_HOUR', '60') ?: 60);
        $maxTokens = (int)(env_loader::get('AI_TOKENS_PER_HOUR', '40000') ?: 40000);
        
        return [
            'allowed' => $usage['requests'] < $maxRequests,
            'remaining_requests' => max(0, $maxRequests - $usage['requests']),
            'remaining_tokens' => max(0, $maxTokens - $usage['tokens']),
        ];
    }
    
    /**
     * Record API response with performance metrics.
     */
    public static function log_api_response(string $action, $response, float $duration, ?int $tokensUsed = null): void {
        $context = [
            'action' => $action,
            'duration_ms' => round($duration * 1000, 2),
            'success' => !empty($response),
        ];
        
        if ($tokensUsed !== null) {
            $context['tokens_used'] = $tokensUsed;
        }
        
        if ($duration > 5.0) {
            self::error('Slow API response: ' . $action, $context);
        } else {
            self::info('API response: ' . $action, $context);
        }
        
        // Track performance
        self::$performance[$action] = [
            'duration' => $duration,
            'tokens' => $tokensUsed,
            'timestamp' => microtime(true),
        ];
    }
    
    /**
     * Log performance metrics at end of request.
     */
    public static function log_performance(): void {
        if (self::$requestStartTime === null) {
            return;
        }
        
        $totalDuration = microtime(true) - self::$requestStartTime;
        
        $context = [
            'total_duration_ms' => round($totalDuration * 1000, 2),
            'operations' => count(self::$performance),
            'memory_peak' => memory_get_peak_usage(true),
        ];
        
        if (!empty(self::$performance)) {
            $context['performance'] = self::$performance;
        }
        
        if ($totalDuration > 10.0) {
            self::error('Slow request detected', $context);
        } else {
            self::info('Request completed', $context);
        }
    }
    
    /**
     * Log security events.
     */
    public static function log_security(string $event, array $context = []): void {
        $context['security_event'] = true;
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        self::error('SECURITY: ' . $event, $context);
    }

    public static function info(string $message, array $context = []): void {
        self::log($message, DEBUG_DEVELOPER, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::log($message, DEBUG_MINIMAL, $context);
    }

    public static function debug(string $message, array $context = []): void {
        self::log($message, DEBUG_DEVELOPER, $context);
    }

    public static function exception(\Throwable $ex, ?string $prefix = null): void {
        $msg = ($prefix ? $prefix . ': ' : '') . $ex->getMessage();
        $context = ['file' => $ex->getFile(), 'line' => $ex->getLine()];
        try {
            if (env_loader::get_bool('AI_DEBUG')) {
                $context['trace'] = $ex->getTraceAsString();
            }
        } catch (\Throwable $e) {
            // ignore
        }
        self::error($msg, $context);
    }
}
