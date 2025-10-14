<?php
declare(strict_types=1);

/**
 * Input sanitizer for GIS AI Assistant.
 *
 * Centralized sanitization for prompts and emails.
 *
 * @package    local_gis_ai_assistant1
 */
namespace local_gis_ai_assistant1\helpers;

defined('MOODLE_INTERNAL') || die();

use moodle_exception;

class sanitizer {
    /**
     * Sanitize prompt text by stripping tags, normalizing whitespace, and truncating.
     *
     * @param string $prompt
     * @param int $maxlen
     * @return string
     */
    public static function sanitize_prompt(string $prompt, int $maxlen = 2000): string {
        $s = strip_tags($prompt);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        $s = trim($s);
        if (mb_strlen($s) > $maxlen) {
            $s = mb_substr($s, 0, $maxlen);
        }
        // Optional simple bad-words filtering via BAD_WORDS_LIST (comma-separated)
        $bad = getenv('BAD_WORDS_LIST') ?: '';
        if (!empty($bad)) {
            $words = array_filter(array_map('trim', explode(',', $bad)));
            if (!empty($words)) {
                $pattern = '/\b(' . implode('|', array_map('preg_quote', $words)) . ')\b/iu';
                $s = preg_replace($pattern, '***', $s);
            }
        }
        return $s;
    }

    /**
     * Validate and sanitize an email address.
     *
     * @param string $email
     * @return string
     * @throws moodle_exception if invalid
     */
    public static function sanitize_email(string $email): string {
        $clean = clean_param($email, PARAM_EMAIL);
        if (empty($clean) || !filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            throw new moodle_exception('invalidemail', 'local_gis_ai_assistant1', '', $email);
        }
        return $clean;
    }
}
