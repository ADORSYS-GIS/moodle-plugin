<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\helpers;

defined('MOODLE_INTERNAL') || die();

class sanitizer {
    /** Sanitize prompt text by stripping tags, normalizing whitespace, and truncating. */
    public static function sanitize_prompt(string $prompt, int $maxlen = 2000): string {
        $s = strip_tags($prompt);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        $s = trim($s);
        if (mb_strlen($s) > $maxlen) {
            $s = mb_substr($s, 0, $maxlen);
        }
        $bad = env_loader::get('BAD_WORDS_LIST', '');
        if (!empty($bad)) {
            $words = array_filter(array_map('trim', explode(',', $bad)));
            if (!empty($words)) {
                $quoted = array_map(static fn($w) => preg_quote($w, '/'), $words);
                $pattern = '/\b(' . implode('|', $quoted) . ')\b/iu';
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
}
