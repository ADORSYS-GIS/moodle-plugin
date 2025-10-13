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
    global $PAGE, $CFG;
    
    // Only load for logged in users
    if (isloggedin() && !isguestuser()) {
        // Load JavaScript (can be loaded after <head>)
        $PAGE->requires->js('/local/openai_assistant/js/widget.js');
    }
}

/**
 * Render assistant responses to HTML using Parsedown when available, otherwise
 * fall back to the lightweight renderer. Output is sanitized via Moodle's
 * format_text to ensure safety.
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

    // Use a direct, simple markdown renderer instead of the complex Parsedown approach
    $result = local_openai_assistant_simple_markdown($text);
    
    // Debug: Log the raw input and processed output
    error_log("=== MARKDOWN DEBUG ===");
    error_log("Raw input: " . json_encode(substr($text, 0, 300)));
    error_log("HTML output: " . json_encode(substr($result, 0, 400)));
    error_log("=== END DEBUG ===");
    
    return $result;

    // Fallback: the original lightweight renderer (keeps behavior if Parsedown not present)
    // Handle fenced code blocks first (```code```)
    $text_for_fallback = preg_replace_callback('/```(.*?)```/s', function($matches) {
        $code = htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<pre><code>' . $code . '</code></pre>';
    }, $text);

    $lines = explode("\n", $text_for_fallback);
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

    // Sanitize fallback HTML through Moodle formatter if available
    if (function_exists('format_text')) {
        return format_text($html, FORMAT_HTML, ['noclean' => false]);
    }

    return $html;
}

/**
 * Simple, reliable markdown renderer that focuses on code blocks and basic formatting
 */
function local_openai_assistant_simple_markdown($text) {
    if ($text === null || $text === '') {
        return '';
    }

    // Normalize line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Handle fenced code blocks with language (```rust\ncode\n``` or ```rust code```)
    $text = preg_replace_callback('/```(\w+)?\s*(.*?)```/s', function($matches) {
        $language = isset($matches[1]) && $matches[1] ? htmlspecialchars($matches[1], ENT_QUOTES) : '';
        $code = trim($matches[2]);
        
        // If code contains newlines, it's a block
        if (strpos($code, "\n") !== false) {
            $code = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $class = $language ? ' class="language-' . $language . '"' : '';
            return "\n<pre><code{$class}>{$code}</code></pre>\n";
        } else {
            // Single line code
            $code = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return "<code>{$code}</code>";
        }
    }, $text);
    
    // Split into lines for processing
    $lines = explode("\n", $text);
    $html = '';
    $in_list = false;
    $in_paragraph = false;

    foreach ($lines as $line) {
        $trim = trim($line);

        // Skip empty lines
        if ($trim === '') {
            if ($in_list) {
                $html .= "</ul>\n";
                $in_list = false;
            }
            if ($in_paragraph) {
                $html .= "</p>\n";
                $in_paragraph = false;
            }
            continue;
        }

        // Check if this line is already HTML (from code block processing)
        if (preg_match('/^<(pre|code)/', $trim)) {
            if ($in_list) {
                $html .= "</ul>\n";
                $in_list = false;
            }
            if ($in_paragraph) {
                $html .= "</p>\n";
                $in_paragraph = false;
            }
            $html .= $line . "\n";
            continue;
        }

        // Headings
        if (preg_match('/^(#{1,6})\s*(.+)$/', $trim, $m)) {
            if ($in_list) {
                $html .= "</ul>\n";
                $in_list = false;
            }
            if ($in_paragraph) {
                $html .= "</p>\n";
                $in_paragraph = false;
            }
            $level = min(6, strlen($m[1]));
            $content = htmlspecialchars($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
            $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
            $html .= "<h{$level}>{$content}</h{$level}>\n";
            continue;
        }

        // Lists
        if (preg_match('/^[-\*\+]\s+(.+)$/', $trim, $m)) {
            if ($in_paragraph) {
                $html .= "</p>\n";
                $in_paragraph = false;
            }
            if (!$in_list) {
                $html .= "<ul>\n";
                $in_list = true;
            }
            $item = htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $item = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item);
            $item = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $item);
            $item = preg_replace('/`([^`]+)`/', '<code>$1</code>', $item);
            $html .= "<li>{$item}</li>\n";
            continue;
        }

        // Regular paragraphs
        if ($in_list) {
            $html .= "</ul>\n";
            $in_list = false;
        }
        
        $escaped = htmlspecialchars($trim, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
        $escaped = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $escaped);
        $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);
        
        if (!$in_paragraph) {
            $html .= "<p>";
            $in_paragraph = true;
        } else {
            $html .= " ";
        }
        $html .= $escaped;
    }

    // Close any open tags
    if ($in_list) {
        $html .= "</ul>\n";
    }
    if ($in_paragraph) {
        $html .= "</p>\n";
    }

    return $html;
}