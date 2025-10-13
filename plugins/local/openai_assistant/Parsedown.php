<?php
/**
 * Minimal Parsedown-compatible shim providing text() and setSafeMode().
 * This is a light-weight vendor replacement that implements a subset of Markdown
 * features: headings, lists, code fences, bold/italic, inline code.
 *
 * Purpose: provide a safe, local implementation so the plugin can call
 * require_once(__DIR__ . '/Parsedown.php') and use Parsedown::text().
 */

class Parsedown {
    private $safeMode = false;

    /**
     * Enable "safe mode" (no-op for this lightweight shim, kept for compatibility).
     */
    public function setSafeMode($enabled = true) {
        $this->safeMode = (bool) $enabled;
        return $this;
    }

    /**
     * Convert a Markdown-ish string to HTML.
     * This implements a conservative subset suitable for assistant replies.
     */
    public function text($text) {
        if ($text === null || $text === '') {
            return '';
        }

        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Handle fenced code blocks first (```language\ncode```)
        $text = preg_replace_callback('/```(\w+)?\s*\n(.*?)\n```/s', function($matches) {
            $language = isset($matches[1]) ? htmlspecialchars($matches[1], ENT_QUOTES) : '';
            $code = htmlspecialchars(trim($matches[2]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $class = $language ? ' class="language-' . $language . '"' : '';
            return '<pre><code' . $class . '>' . $code . '</code></pre>';
        }, $text);
        
        // Handle simple code blocks without language (```code```)
        $text = preg_replace_callback('/```(.*?)```/s', function($matches) {
            $code = htmlspecialchars(trim($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<pre><code>' . $code . '</code></pre>';
        }, $text);

        $lines = explode("\n", $text);
        $html = '';
        $in_list = false;

        foreach ($lines as $line) {
            $trim = trim($line);

            // Skip lines that are just whitespace
            if ($trim === '') {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                } else {
                    $html .= "<p></p>\n";
                }
                continue;
            }

            // Headings: ### -> h3, ## -> h2, # -> h1
            if (preg_match('/^(#{1,6})\s*(.+)$/', $trim, $m)) {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                }
                $level = min(6, strlen($m[1]));
                $content = htmlspecialchars($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                // Convert simple markdown bold/italic inside headings
                $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
                $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
                $html .= "<h{$level}>" . $content . "</h{$level}>\n";
                continue;
            }

            // Unordered lists: lines starting with - or *
            if (preg_match('/^[-\*\+]\s+(.+)$/', $trim, $m)) {
                if (!$in_list) {
                    $html .= "<ul>\n";
                    $in_list = true;
                }
                $item = htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $item = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item);
                $item = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $item);
                $html .= "<li>" . $item . "</li>\n";
                continue;
            }

            // Process inline formatting
            $escaped = htmlspecialchars($trim, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Bold (**) first, then italics (*)
            $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
            $escaped = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $escaped);

            // Inline code `code`
            $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);

            // Paragraph
            if ($in_list) {
                $html .= "</ul>\n";
                $in_list = false;
            }
            $html .= "<p>" . $escaped . "</p>\n";
        }

        // Close list if still open
        if ($in_list) {
            $html .= "</ul>\n";
            $in_list = false;
        }

        return $html;
    }
}