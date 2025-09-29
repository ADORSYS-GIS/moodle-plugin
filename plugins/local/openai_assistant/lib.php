<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Plugin navigation hook
 */
function local_openai_assistant_extend_navigation(global_navigation $navigation) {
    global $PAGE;
    
    if (isloggedin() && !isguestuser()) {
        $node = $navigation->add(
            get_string('openai_assistant', 'local_openai_assistant'),
            new moodle_url('/local/openai_assistant/chat.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'openai_assistant'
        );
        $node->showinflatnavigation = true;
    }
}

/**
 * Load the chat widget on every page
 */
function local_openai_assistant_after_config() {
    global $PAGE, $CFG;
    
    // Only load for logged in users
    if (isloggedin() && !isguestuser()) {
        // Load CSS in <head>
        $PAGE->requires->css('/local/openai_assistant/style/widget.css');
    }
}

/**
 * Load the chat widget JavaScript before footer
 */
function local_openai_assistant_before_footer() {
    global $PAGE;
    
    // Only load for logged in users
    if (isloggedin() && !isguestuser()) {
        // Load JavaScript (can be loaded after <head>)
        $PAGE->requires->js('/local/openai_assistant/js/widget.js');
    }
}

/**
 * Simple Markdown-like to HTML renderer for assistant responses.
 * - Supports headings (#, ##, ###), bullet lists (- or *), paragraphs and code fences (```).
 * - Escapes HTML by default to prevent XSS, then injects safe HTML tags.
 */
function local_openai_assistant_render_ai_response($text) {
    if ($text === null || $text === '') {
        return '';
    }

    // If response is wrapped in quotes (happens when strings are double-encoded), remove them.
    if (preg_match('/^\s*"(.*)"\s*$/s', $text, $m)) {
        $text = $m[1];
    }

    // Normalize line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    // Handle fenced code blocks first (```code```)
    $text = preg_replace_callback('/```(.*?)```/s', function($matches) {
        $code = htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

        // Inline code `code` (do after escaping so code tags are safe)
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