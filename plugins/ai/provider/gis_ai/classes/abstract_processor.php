<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai;

defined('MOODLE_INTERNAL') || die();

/**
 * Base processor for GIS AI provider actions.
 *
 * Extend this when implementing specific process_* classes (e.g., process_generate_text).
 */
abstract class abstract_processor extends \core_ai\process_base {
    /** Get API key from ENV or plugin config. */
    protected function get_apikey(): string {
        $env = getenv('OPENAI_API_KEY');
        if ($env !== false && $env !== '') {
            return (string)$env;
        }
        $config = get_config('aiprovider_gis_ai');
        return (string)($config->apikey ?? '');
    }

    /** Get base URL from ENV or plugin config. */
    protected function get_baseurl(): string {
        $env = getenv('OPENAI_BASE_URL');
        if ($env !== false && $env !== '') {
            return rtrim((string)$env, '/');
        }
        $config = get_config('aiprovider_gis_ai');
        $url = (string)($config->baseurl ?? 'https://api.openai.com/v1');
        return rtrim($url, '/');
    }

    /** Get default model from ENV or plugin config. */
    protected function get_default_model(): string {
        $env = getenv('OPENAI_MODEL');
        if ($env !== false && $env !== '') {
            return (string)$env;
        }
        $config = get_config('aiprovider_gis_ai');
        return (string)($config->model ?? 'gpt-4o');
    }

    /** Timeout in seconds. */
    protected function get_timeout(): int {
        $env = getenv('OPENAI_TIMEOUT');
        if ($env !== false && $env !== '' && ctype_digit((string)$env)) {
            return (int)$env;
        }
        return 30;
    }
}
