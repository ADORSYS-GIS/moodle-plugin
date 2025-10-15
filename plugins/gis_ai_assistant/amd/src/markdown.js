// Shim module: proxy to markdown_engine (markdown-it) for backward compatibility
define(['local_gis_ai_assistant/markdown_engine'], function(Engine) {
    'use strict';
    // Minimal shim: delegate to markdown_engine and return early. Legacy code below is intentionally unreachable.
    return {
        ensure: function(){ return Engine.ensure(); },
        toHtml: function(md){ return Engine.renderAsync(md); },
        toHtmlAsync: function(md){ return Engine.renderAsync(md); },
        toHtmlSync: function(md){ return Engine.render(md); }
    };

    // Optionally preload Marked and DOMPurify (no external fetch to avoid CDN errors). Always resolve.
    function ensure() {
        return Promise.resolve({ marked: window.marked || null, DOMPurify: window.DOMPurify || null });
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

    // Remove common noise lines produced by LLM exports (e.g., "cssCopy", version banners, stray ``` lines).
    function stripNoiseLines(md) {
        try {
            var lines = String(md || '').split(/\r?\n/);
            var noiseline = /^\s*[A-Za-z][A-Za-z0-9_-]*Copy\s*$/i; // cssCopy, markdownCopy, luaCopy, lessCopy
            var versionline = /^\s*(?:text)?mermaid(?:\s+version.*)?\s*$/i; // mermaid version 10.x
            var plainMermaid = /^\s*mermaid\s*$/i; // lone 'mermaid'
            var mermaidPrefix = /^\s*mermaid\s+(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram(?:-v2)?|erDiagram|journey)\b/i;
            var syntaxerrorline = /^\s*syntax\s+error\s+in\b/i; // Syntax error in textmermaid ...
            var out = [];
            for (var i = 0; i < lines.length; i++) {
                var ln = lines[i];
                if (noiseline.test(ln)) { continue; }
                if (versionline.test(ln)) { continue; }
                if (plainMermaid.test(ln)) { continue; }
                if (mermaidPrefix.test(ln)) { ln = ln.replace(mermaidPrefix, '$1'); }
                if (syntaxerrorline.test(ln)) { continue; }
                // Do NOT drop isolated ``` lines; they may close a fenced block we created.
                out.push(ln);
            }
            return out.join('\n');
        } catch (e) { return md; }
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
        md = stripNoiseLines(fenceMermaidHeuristic(maybeNormalizeSingleLineCode(md)));
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
        html = html.replace(/^#\s+(.+)$/gm, '<h1>$1</h1>');
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

    function convertMermaidBlocksInHtml(html) {
        try {
            var div = document.createElement('div');
            div.innerHTML = String(html || '');
            var codes = div.querySelectorAll ? div.querySelectorAll('pre code') : [];
            for (var i = 0; i < codes.length; i++) {
                var code = codes[i];
                var cls = (code.className || '').toLowerCase();
                var pre = code.parentNode && code.parentNode.tagName === 'PRE' ? code.parentNode : null;
                if (!pre || !pre.parentNode) { continue; }
                var raw = code.textContent || code.innerText || '';
                // Decide whether this block is Mermaid by class or content (also allow obvious edge syntax).
                var looksMermaid = (cls.indexOf('mermaid') !== -1)
                    || /^(\s*)(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram(?:-v2)?|erDiagram|journey)\b/m.test(raw)
                    || /^\s*subgraph\b/m.test(raw)
                    || /-->|==>|-\.-\>|<--|==/m.test(raw);
                if (!looksMermaid) { continue; }
                // Lightweight normalization (similar to mermaid_loader normalization).
                try {
                    var lines = String(raw || '').split(/\r?\n/);
                    var cleaned = [];
                    var drop1 = /^\s*[A-Za-z][A-Za-z0-9_-]*Copy\s*$/i;
                    var drop2 = /^\s*(?:text)?mermaid(?:\s+version.*)?\s*$/i;
                    var dropPlain = /^\s*mermaid\s*$/i;
                    var mermaidPrefix = /^\s*mermaid\s+(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram(?:-v2)?|erDiagram|journey)\b/i;
                    var dropFence = /^\s*```/;
                    for (var j = 0; j < lines.length; j++) {
                        var ln = lines[j];
                        if (drop1.test(ln) || drop2.test(ln) || dropPlain.test(ln) || dropFence.test(ln)) { continue; }
                        if (mermaidPrefix.test(ln)) { ln = ln.replace(mermaidPrefix, '$1'); }
                        cleaned.push(ln);
                    }
                    raw = cleaned.join('\n').replace(/^\s+|\s+$/g, '');
                    var hasDir = /^(\s*)(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram(?:-v2)?|erDiagram|journey)\b/m.test(raw);
                    var startsWithSub = /^\s*subgraph\b/m.test(raw);
                    var hasEdges = /-->|==>|-\.-\>|==/m.test(raw);
                    if (!hasDir) {
                        if (startsWithSub || hasEdges) {
                            raw = 'graph TD\n' + raw;
                            hasDir = true;
                        }
                    }
                    if (!hasDir) { continue; }
                    if (!raw.trim()) { continue; }
                } catch (e) {}
                var divMer = document.createElement('div');
                divMer.className = 'mermaid';
                divMer.setAttribute('data-mermaid-source', raw);
                divMer.textContent = raw;
                pre.parentNode.replaceChild(divMer, pre);
            }
            return div.innerHTML;
        } catch (e) { return html; }
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
            var prepped = stripNoiseLines(fenceMermaidHeuristic(maybeNormalizeSingleLineCode(md)));
            var html = window.marked.parse(String(prepped || ''));
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

    // (Unreachable legacy code intentionally left to avoid risky mass deletion during refactor)
});
