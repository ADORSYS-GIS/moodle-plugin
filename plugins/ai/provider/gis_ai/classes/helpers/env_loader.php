<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\helpers;

defined('MOODLE_INTERNAL') || die();

class env_loader {
    /** @var array<string,string> */
    private static array $cache = [];

    /** @var array<string, string|null> */
    private static array $defaults = [
        'OPENAI_API_KEY' => null,
        'OPENAI_BASE_URL' => 'https://api.openai.com/v1',
        'OPENAI_MODEL' => 'gpt-4o',
        'AI_RUST_MODE' => 'ffi',
        'AI_RUST_ENDPOINT' => 'http://127.0.0.1:8080',
        'AI_RUST_LIB_PATH' => '/usr/local/lib/libai_rust.so',
        'AI_TIMEOUT' => '30',
        'AI_DEBUG' => 'false',
        'AI_LOG_FILE' => '',
        'MASK_SECRETS' => 'true',
    ];

    public static function get(string $key, $fallback = null): string {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $env = getenv($key);
        if ($env !== false) {
            return self::$cache[$key] = (string)$env;
        }
        if ($fallback !== null) {
            return (string)$fallback;
        }
        if (array_key_exists($key, self::$defaults) && self::$defaults[$key] !== null) {
            return self::$cache[$key] = (string)self::$defaults[$key];
        }
        throw new \moodle_exception('envmissing', 'aiprovider_gis_ai', '', $key);
    }

    public static function get_bool(string $key): bool {
        $val = strtolower(trim(self::get($key, 'false')));
        return in_array($val, ['1', 'true', 'yes', 'on'], true);
    }

    public static function get_int(string $key, int $fallback = 0): int {
        return (int)self::get($key, (string)$fallback);
    }

    /**
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
