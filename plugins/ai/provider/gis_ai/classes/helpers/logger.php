<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\helpers;

defined('MOODLE_INTERNAL') || die();

class logger {
    /** Base logging method. */
    public static function log(string $message, int $level = DEBUG_NORMAL, array $context = []): void {
        $timestamp = date('c');
        $ctx = empty($context) ? '' : ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $line = "[$timestamp] $message$ctx";

        try {
            if (env_loader::get_bool('AI_DEBUG')) {
                debugging($message . $ctx, $level);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $logfile = env_loader::get('AI_LOG_FILE', '');
        if (!empty($logfile)) {
            @error_log($line . PHP_EOL, 3, $logfile);
        }
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
