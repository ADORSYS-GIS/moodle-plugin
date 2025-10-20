<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalize various OpenAI-compatible response shapes to a simple array.
 */
final class response_normalizer {
    /**
     * @param array $response Raw decoded JSON
     * @return array{content:string, raw:array, tokens:?int}
     */
    public static function process(array $response): array {
        if (isset($response['error'])) {
            $err = $response['error']['message'] ?? json_encode($response['error']);
            throw new \moodle_exception('apiresponseerror', 'aiprovider_gis_ai', '', $err);
        }

        $content = '';

        // OpenAI /responses format.
        if (isset($response['output']) && is_array($response['output'])) {
            $parts = [];
            foreach ($response['output'] as $out) {
                if (is_string($out)) {
                    $parts[] = $out;
                } elseif (is_array($out)) {
                    $parts[] = $out['content'][0]['text'] ?? ($out['text'] ?? json_encode($out));
                }
            }
            $content = implode("\n\n", array_filter($parts));
        }

        // Chat completions-like format.
        if ($content === '' && !empty($response['choices']) && is_array($response['choices'])) {
            $choice = $response['choices'][0] ?? null;
            if ($choice) {
                if (isset($choice['message']['content'])) {
                    $content = (string)$choice['message']['content'];
                } elseif (isset($choice['text'])) {
                    $content = (string)$choice['text'];
                } elseif (isset($choice['delta']['content'])) {
                    $content = (string)$choice['delta']['content'];
                }
            }
        }

        // Fallback plain text.
        if ($content === '' && isset($response['text'])) {
            $content = (string)$response['text'];
        }

        $content = trim($content);
        if ($content === '') {
            throw new \moodle_exception('emptyresponse', 'aiprovider_gis_ai');
        }

        $tokens = $response['usage']['total_tokens'] ?? $response['usage']['tokens'] ?? null;
        return [
            'content' => $content,
            'raw' => $response,
            'tokens' => is_numeric($tokens) ? (int)$tokens : null,
        ];
    }
}
