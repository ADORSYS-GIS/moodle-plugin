// AMD module providing Markdown -> HTML conversion using Marked when available,
// with a safe fallback renderer.
//
// Usage:
// define(['local_gis_ai_assistant/markdown'], function(Markdown) {
//   var html = Markdown.toHtml(markdownText);
// });

define([], function() {
    'use strict';

    // Optionally preload Marked and DOMPurify so nested lists render correctly.
    function ensure() {
        return new Promise(function(resolve) {
            try {
                if (window.marked && window.DOMPurify) { resolve({ marked: window.marked, DOMPurify: window.DOMPurify }); return; }
                var rq = (typeof requirejs !== 'undefined') ? requirejs : (typeof require !== 'undefined' ? require : null);
                if (!rq) { resolve({ marked: window.marked || null, DOMPurify: window.DOMPurify || null }); return; }
                // Configure CDN paths once; RequireJS will handle AMD correctly, avoiding anonymous define mismatch.
                rq.config({
                    paths: {
                        'marked_cdn': 'https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min',
                        'dompurify_cdn': 'https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min'
                    },
                    shim: {
                        'marked_cdn': { exports: 'marked' },
                        'dompurify_cdn': { exports: 'DOMPurify' }
                    }
                });
                var deps = [];
                if (!window.marked) { deps.push('marked_cdn'); }
                if (!window.DOMPurify) { deps.push('dompurify_cdn'); }
                if (!deps.length) { resolve({ marked: window.marked, DOMPurify: window.DOMPurify }); return; }
                rq(deps, function() {
                    try {
                        for (var i = 0; i < arguments.length; i++) {
                            var mod = arguments[i];
                            // Marked exposes .parse; DOMPurify exposes .sanitize
                            if (!window.marked && mod && typeof mod.parse === 'function') { window.marked = mod; }
                            if (!window.DOMPurify && mod && typeof mod.sanitize === 'function') { window.DOMPurify = mod; }
                        }
                    } catch (e) {}
                    resolve({ marked: window.marked || null, DOMPurify: window.DOMPurify || null });
                });
            } catch (e) { resolve({ marked: window.marked || null, DOMPurify: window.DOMPurify || null }); }
        });
    }

    function escapeHTML(text) {
        var div = document.createElement('div');
        div.textContent = String(text || '');
        return div.innerHTML;
    }

    // If content has no real newlines but contains literal escape sequences ("\n", "\t"), decode them.
    function maybeDecodeLiteralEscapes(md) {
        if (!md) { return md; }
        var s = String(md);
        if (s.indexOf('\n') !== -1) { return s; }
        if (!/\\n|\\r\\n|\\t/.test(s)) { return s; }
        s = s.replace(/\\r\\n|\\r|\\n/g, '\n');
        s = s.replace(/\\t/g, '\t');
        return s;
    }

    // Heuristic: wrap likely Mermaid blocks that aren't fenced so they can render.
    function fenceMermaidHeuristic(s) {
        try {
            var lines = String(s || '').split(/\r?\n/);
            var out = [];
            var capturing = false;
            var buf = [];
            var startRe = /^(?:\s*)(graph\b|flowchart\b|sequenceDiagram\b|classDiagram\b|stateDiagram(?:-v2)?\b|erDiagram\b|journey\b)|^(?:\s*)subgraph\b/;
            function flush() {
                if (buf.length) {
                    out.push('```mermaid');
                    out.push(buf.join('\n'));
                    out.push('```');
                    buf.length = 0;
                }
            }
            for (var i = 0; i < lines.length; i++) {
                var ln = lines[i];
                if (!capturing && startRe.test(ln)) {
                    capturing = true;
                    buf.push(ln);
                    continue;
                }
                if (capturing) {
                    buf.push(ln);
                    if (/^\s*end\s*$/.test(ln)) { flush(); capturing = false; }
                    continue;
                }
                out.push(ln);
            }
            if (capturing) { flush(); }
            return out.join('\n');
        } catch (e) { return s; }
    }

    // Heuristic: if content is one very long line that looks like code, re-insert newlines in sensible places.
    function maybeNormalizeSingleLineCode(md) {
        if (!md) { return md; }
        var s = maybeDecodeLiteralEscapes(md);
        if (s.indexOf('\n') !== -1) { return s; }
        if (s.length < 120) { return s; } // avoid touching short one-liners
        // Protect URL schemes so we don't split on '://'.
        s = s.replace(/:\/\//g, ':__SLASHSLASH__');
        // Add newlines around braces and after semicolons.
        s = s.replace(/\)\s*\{/g, ')\n{');
        s = s.replace(/\{[ \t]*/g, '{\n');
        s = s.replace(/[ \t]*\}/g, '\n}');
        s = s.replace(/;[ \t]*/g, ';\n');
        // Insert newline before '//' comments (after protecting URL schemes).
        s = s.replace(/\/\/[ \t]*/g, '\n// ');
        // Insert newline before common code starters if not already at line start.
        s = s.replace(/\s+(public\s+class\b)/g, '\n$1');
        s = s.replace(/\s+(class\s+[A-Za-z_])/g, '\n$1');
        s = s.replace(/\s+(fn\s+[A-Za-z_])/g, '\n$1');
        // Restore URL schemes.
        s = s.replace(/:__SLASHSLASH__/g, '://');
        return s;
    }

    // Compute indentation level (2 spaces per level; tabs = 4 spaces).
    function listIndentLevel(line) {
        var m = (line.match(/^\s+/) || [''])[0];
        var width = m.replace(/\t/g, '    ').length;
        return Math.floor(width / 2);
    }

    // Render nested lists from plain text using indentation (supports -, *, + and ordered X.)
    function renderNestedLists(text) {
        var lines = String(text || '').split(/\n/);
        var out = [];
        var stack = []; // each: {type: 'ul'|'ol', level}
        function openList(type) { stack.push({type:type}); out.push(type === 'ul' ? '<ul>' : '<ol>'); }
        function closeList() { var s = stack.pop(); if (!s) return; out.push(s.type === 'ul' ? '</ul>' : '</ol>'); }
        function closeToLevel(level) { while (stack.length > level) closeList(); }

        for (var i = 0; i < lines.length; i++) {
            var ln = lines[i];
            var ul = ln.match(/^(\s*)([\-*+])\s+(.+)$/);
            var ol = ln.match(/^(\s*)(\d+)\.\s+(.+)$/);
            if (ul || ol) {
                var isUl = !!ul;
                var content = (ul ? ul[3] : ol[3]);
                var level = listIndentLevel(ln);
                // Ensure stack depth == level
                if (stack.length < level + 1) {
                    while (stack.length < level + 1) { openList(isUl ? 'ul' : 'ol'); }
                } else if (stack.length > level + 1) {
                    closeToLevel(level + 1);
                }
                // If list type changes at same level, close and reopen
                if (stack.length && stack[stack.length - 1].type !== (isUl ? 'ul' : 'ol')) {
                    closeList();
                    openList(isUl ? 'ul' : 'ol');
                }
                out.push('<li>' + content + '</li>');
            } else {
                // Non-list line: close all lists if blank or new paragraph context
                if (/^\s*$/.test(ln)) {
                    closeToLevel(0);
                    out.push('');
                } else {
                    // Keep line as-is (already escaped earlier)
                    out.push(ln);
                }
            }
        }
        closeToLevel(0);
        return out.join('\n');
    }

    // Minimal safe fallback Markdown renderer (headings, bold, italics, code, lists, hr, line breaks).
    function fallbackMarkdownToHtml(md) {
        if (!md) { return ''; }
        md = fenceMermaidHeuristic(maybeNormalizeSingleLineCode(md));
        // Normalize common malformed fences like "` ``" -> "```".
        var src = String(md).replace(/`\s*`\s*`/g, '```');
        var html = escapeHTML(src);
        // Fenced code blocks with optional language: ```lang[spaces]?[optional newline]...```
        // Allow the content to begin on same line after the language tag.
        html = html.replace(/```([a-zA-Z0-9_+\-]+)[ \t]*\r?\n?([\s\S]*?)```/g, function(_, lang, code) {
            return '<pre><code class="language-' + lang + '">' + code + '</code></pre>';
        });
        // Remaining fenced code blocks without language (content may start same line)
        html = html.replace(/```([\s\S]*?)```/g, function(_, code) {
            return '<pre><code>' + code + '</code></pre>';
        });

        // Indented code blocks (4 spaces or a tab). Convert contiguous groups into <pre><code>.
        // We operate on the escaped HTML. Remove one leading indentation unit per line inside the block.
        html = html.replace(/(?:^|\n)((?:[ \t]{4}.+(?:\n|$))+)/g, function(_, block) {
            var lines = block.replace(/\n$/,'').split(/\n/);
            var stripped = lines.map(function(line) {
                return line.replace(/^(?:    |\t)/, '');
            }).join('\n');
            return '\n<pre><code>' + stripped + '</code></pre>\n';
        });

        // Temporarily protect code blocks so newline -> <br> conversion doesn't affect them.
        var codePlaceholders = [];
        html = html.replace(/<pre><code[^>]*>[\s\S]*?<\/code><\/pre>/g, function(m) {
            codePlaceholders.push(m);
            return '[[CODEBLOCK_' + codePlaceholders.length + ']]';
        });
        // Inline code
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        // Headings
        html = html.replace(/^######\s+(.+)$/gm, '<h6>$1</h6>');
        html = html.replace(/^#####\s+(.+)$/gm, '<h5>$1</h5>');
        html = html.replace(/^####\s+(.+)$/gm, '<h4>$1</h4>');
        html = html.replace(/^###\s+(.+)$/gm, '<h3>$1</h3>');
        html = html.replace(/^##\s+(.+)$/gm, '<h2>$1</h2>');
        // Horizontal rule
        html = html.replace(/^\s*---\s*$/gm, '<hr />');
        // Bold and italic
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/(^|[^*])\*(?!\*)([^*]+)\*(?!\*)/g, '$1<em>$2</em>');
        
        // Blockquotes > ... per line
        html = html.replace(/^>\s?(.+)$/gm, '<blockquote>$1</blockquote>');

        // Nested lists using indentation (works even without Marked)
        html = renderNestedLists(html);
        // Do not inject <br> globally; CSS uses white-space: pre-wrap to preserve line breaks.

        // Restore code blocks intact (with their original newlines preserved)
        html = html.replace(/\[\[CODEBLOCK_(\d+)\]\]/g, function(_, i) {
            return codePlaceholders[parseInt(i, 10) - 1] || '';
        });
        return html;
    }

    // Basic sanitizer to strip dangerous tags/attrs from HTML produced by Marked.
    function sanitizeHtml(html) {
        try {
            var div = document.createElement('div');
            div.innerHTML = String(html || '');
            // Remove dangerous elements entirely.
            var dangerousTags = ['script', 'iframe', 'object', 'embed', 'link', 'style', 'meta'];
            dangerousTags.forEach(function(tag) {
                var nodes = div.getElementsByTagName(tag);
                for (var i = nodes.length - 1; i >= 0; i--) {
                    nodes[i].parentNode.removeChild(nodes[i]);
                }
            });
            // Strip on* attributes and javascript: URLs.
            var treeWalker = document.createTreeWalker(div, NodeFilter.SHOW_ELEMENT, null, false);
            while (treeWalker.nextNode()) {
                var el = treeWalker.currentNode;
                // Remove event handler attributes.
                var attrs = Array.prototype.slice.call(el.attributes || []);
                attrs.forEach(function(attr) {
                    var name = attr.name.toLowerCase();
                    var val = (attr.value || '').toLowerCase();
                    if (name.indexOf('on') === 0 || val.indexOf('javascript:') === 0) {
                        el.removeAttribute(attr.name);
                    }
                });
                // Enforce rel on target _blank links.
                if (el.tagName === 'A') {
                    if (el.getAttribute('target') === '_blank') {
                        el.setAttribute('rel', 'noopener noreferrer');
                    }
                }
            }
            return div.innerHTML;
        } catch (e) {
            return escapeHTML(String(html || ''));
        }
    }

    function renderWithMarked(md) {
        try {
            if (!window.marked) { return null; }
            // Configure Marked to be conservative.
            try {
                window.marked.setOptions({
                    gfm: true,
                    breaks: true,
                    headerIds: false,
                    mangle: false
                });
            } catch (e) {}
            var html = window.marked.parse(String(fenceMermaidHeuristic(maybeNormalizeSingleLineCode(md)) || ''));
            html = convertMermaidBlocksInHtml(html);
            // Prefer DOMPurify if available, else use internal sanitizer.
            if (window.DOMPurify && window.DOMPurify.sanitize) {
                try { return window.DOMPurify.sanitize(html); } catch (e) {}
            }
            return sanitizeHtml(html);
        } catch (e) {
            return null;
        }
    }

    return {
        // Preload Marked/DOMPurify
        ensure: ensure,
        // Convert Markdown to HTML using Marked when available, else fallback.
        toHtml: function(md) {
            var out = renderWithMarked(md);
            if (out === null) {
                return fallbackMarkdownToHtml(String(md || ''));
            }
            return out;
        },
        // Async version that waits for dependencies
        toHtmlAsync: function(md) {
            return ensure().then(function(){
                var out = renderWithMarked(md);
                if (out === null) { return fallbackMarkdownToHtml(String(md || '')); }
                return out;
            });
        }
    };
});
