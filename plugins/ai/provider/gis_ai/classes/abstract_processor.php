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
    /** Get API key (ENV only). */
    protected function get_apikey(): string {
        return \aiprovider_gis_ai\helpers\env_loader::get('OPENAI_API_KEY', '');
    }

    /** Get base URL from ENV or plugin config. */
    protected function get_baseurl(): string {
        $env = \aiprovider_gis_ai\helpers\env_loader::get('OPENAI_BASE_URL', '');
        if ($env !== '') {
            return rtrim($env, '/');
        }
        $config = get_config('aiprovider_gis_ai');
        $url = (string)($config->baseurl ?? 'https://api.openai.com/v1');
        return rtrim($url, '/');
    }

    /** Get default model from ENV or plugin config. */
    protected function get_default_model(): string {
        $env = \aiprovider_gis_ai\helpers\env_loader::get('OPENAI_MODEL', '');
        if ($env !== '') {
            return $env;
        }
        $config = get_config('aiprovider_gis_ai');
        return (string)($config->model ?? 'gpt-4o');
    }

    /** Timeout in seconds. */
    protected function get_timeout(): int {
        // Prefer central loader and AI_TIMEOUT, fallback to legacy OPENAI_TIMEOUT.
        $val = \aiprovider_gis_ai\helpers\env_loader::get('AI_TIMEOUT', '');
        if ($val === '') {
            $legacy = \aiprovider_gis_ai\helpers\env_loader::get('OPENAI_TIMEOUT', '');
            if ($legacy !== '' && ctype_digit((string)$legacy)) {
                return (int)$legacy;
            }
            return 30;
        }
        return (int)$val;
    }
}
