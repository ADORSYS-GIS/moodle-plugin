<?php
declare(strict_types=1);

/**
 * Environment variable loader for GIS AI Assistant.
 *
 * Centralized class to load and validate ENV vars.
 *
 * @package    local_gis_ai_assistant1
 */
namespace local_gis_ai_assistant1\helpers;

defined('MOODLE_INTERNAL') || die();

use moodle_exception;

class env_loader {
    /**
     * Per-request cache of resolved env values (env or defaults).
     * Note: do not cache per-call fallback values to avoid cross-call contamination.
     *
     * @var array<string,string>
     */
    private static array $cache = [];

    /**
     * Defaults and required markers:
     *  - null = required (no default)
     *  - string = default value
     *
     * @var array<string, string|null>
     */
    private static array $defaults = [
        'OPENAI_API_KEY' => null,
        'OPENAI_BASE_URL' => 'https://api.openai.com/v1',
        'OPENAI_MODEL' => 'gpt-4o',
        'AI_RUST_MODE' => 'ffi',           // 'ffi' or 'api'
        'AI_RUST_ENDPOINT' => 'http://127.0.0.1:8080',
        'AI_RUST_LIB_PATH' => '/usr/local/lib/libai_rust.so',
        'AI_TIMEOUT' => '30',              // seconds
        'AI_DEBUG' => 'false',             // 'true'|'false'
        'AI_LOG_FILE' => '',               // optional filesystem path
        'GIS_AI_ASSISTANT_ENABLED' => 'true',
        'MASK_SECRETS' => 'true',          // toggle masking in snapshots
    ];

    /**
     * Get ENV value with fallback to defaults.
     * If key is required and missing, throws moodle_exception.
     *
     * @param string $key
     * @param mixed $fallback override default
     * @return string
     * @throws moodle_exception
     */
    public static function get(string $key, $fallback = null): string {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $env = getenv($key);
        if ($env !== false) {
            return self::$cache[$key] = (string)$env;
        }

        if ($fallback !== null) {
            // Do not cache runtime fallback.
            return (string)$fallback;
        }

        if (array_key_exists($key, self::$defaults) && self::$defaults[$key] !== null) {
            return self::$cache[$key] = (string)self::$defaults[$key];
        }

        // Required and missing.
        throw new moodle_exception('envmissing', 'local_gis_ai_assistant1', '', $key);
    }

    /**
     * Get boolean value from ENV (common true-ish values supported).
     */
    public static function get_bool(string $key): bool {
        $val = self::get($key, 'false');
        $val = strtolower(trim($val));
        return in_array($val, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Get integer value from ENV.
     */
    public static function get_int(string $key, int $fallback = 0): int {
        $val = self::get($key, (string)$fallback);
        return (int)$val;
    }

    /**
     * Get CSV env as array (trimmed, unique, non-empty).
     *
     * @param string $key
     * @param string $sep
     * @return string[]
     */
    public static function get_array(string $key, string $sep = ','): array {
        $raw = self::get($key, '');
        if ($raw === '') {
            return [];
        }
        $items = array_map('trim', explode($sep, $raw));
        $items = array_filter($items, static fn($v) => $v !== '');
        return array_values(array_unique($items));
    }

    /**
     * Validate required ENV variables; returns array of missing keys (empty if none).
     *
     * @return string[]
     */
    public static function validate_required(): array {
        $missing = [];
        foreach (self::$defaults as $k => $v) {
            if ($v === null && getenv($k) === false) {
                $missing[] = $k;
            }
        }
        return $missing;
    }

    /**
     * Return an associative array of selected ENV keys (useful for dev dashboard display).
     * Sensitive values are masked (API keys).
     *
     * @param string[] $keys
     * @return array<string, string>
     */
    public static function snapshot(array $keys): array {
        $out = [];
        $mask = self::get_bool('MASK_SECRETS');
        foreach ($keys as $k) {
            $val = getenv($k);
            if ($val === false) {
                $out[$k] = '[missing]';
            } else {
                if ($mask && self::looks_like_secret($k)) {
                    $out[$k] = self::mask_secret($val);
                } else {
                    $out[$k] = $val;
                }
            }
        }
        return $out;
    }

    private static function looks_like_secret(string $k): bool {
        $k = strtoupper($k);
        return str_contains($k, 'KEY') || str_contains($k, 'SECRET') || str_contains($k, 'TOKEN') || str_contains($k, 'PASSWORD');
    }

    private static function mask_secret(string $s): string {
        $len = strlen($s);
        if ($len <= 8) {
            return '********';
        }
        $start = substr($s, 0, 4);
        $end = substr($s, -4);
        return $start . str_repeat('*', max(4, $len - 8)) . $end;
    }
}
