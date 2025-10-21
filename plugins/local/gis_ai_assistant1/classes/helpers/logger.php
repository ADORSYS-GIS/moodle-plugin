<?php
declare(strict_types=1);

/**
 * Logging facade for GIS AI Assistant.
 *
 * Uses Moodle debugging() when AI_DEBUG is enabled and optionally writes to a file.
 *
 * @package    local_gis_ai_assistant1
 */
namespace local_gis_ai_assistant1\helpers;

defined('MOODLE_INTERNAL') || die();

class logger {
    /**
     * Core logging method with optional structured context.
     *
     * @param string $message
     * @param int $level Moodle DEBUG_* level
     * @param array<string,mixed> $context
     * @return void
     */
    public static function log(string $message, int $level = DEBUG_NORMAL, array $context = []): void {
        $timestamp = date('c');
        $ctx = empty($context) ? '' : ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $line = "[$timestamp] $message$ctx";

        // Debugging output if AI_DEBUG is true.
        try {
            if (env_loader::get_bool('AI_DEBUG')) {
                debugging($message . $ctx, $level);
            }
        } catch (\Throwable $e) {
            // Swallow to avoid breaking runtime if env isn't available.
        }

        // Optional file logging via AI_LOG_FILE path.
        $logfile = getenv('AI_LOG_FILE') ?: '';
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
