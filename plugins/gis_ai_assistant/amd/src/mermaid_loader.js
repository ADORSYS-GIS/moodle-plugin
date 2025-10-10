// AMD module to load Mermaid and render mermaid diagrams inside a root element.
// Converts <pre><code class="language-mermaid">...</code></pre> into <div class="mermaid">...</div> and renders them.

define([], function() {
    'use strict';

    var CDN_PATH = 'https://cdnjs.cloudflare.com/ajax/libs/mermaid/10.9.1/mermaid.min';
    var state = { promise: null };

    function getRequire() {
        return (typeof requirejs !== 'undefined') ? requirejs : ((typeof require !== 'undefined') ? require : null);
    }

    function detectTheme(preferFromDom) {
        try {
            if (preferFromDom) {
                if (document.querySelector('.ai-chat-container.ai-dark') || document.querySelector('.ai-dark')) { return 'dark'; }
                if (document.querySelector('.ai-chat-container.ai-light') || document.querySelector('.ai-light')) { return 'default'; }
            }
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { return 'dark'; }
        } catch (e) {}
        return 'default';
    }

    function initMermaidIfNeeded() {
        try {
            if (!window.mermaid) { return; }
            if (window.mermaid.__ai_inited) { return; }
            var theme = detectTheme(true);
            if (window.mermaid.initialize) {
                try {
                    window.mermaid.initialize({ startOnLoad: false, theme: (theme === 'dark' ? 'dark' : 'default') });
                } catch (e) {
                    try { window.mermaid.initialize({ startOnLoad: false }); } catch (e2) {}
                }
            }
            window.mermaid.__ai_inited = true;
        } catch (e) {}
    }

    function loadMermaid() {
        if (state.promise) { return state.promise; }
        state.promise = new Promise(function(resolve) {
            if (window.mermaid) { resolve(window.mermaid); return; }
            var rq = getRequire();
            if (!rq) {
                try {
                    var s = document.createElement('script');
                    s.src = CDN_PATH;
                    s.onload = function() { resolve(window.mermaid || null); };
                    s.onerror = function() { resolve(null); };
                    document.head.appendChild(s);
                } catch (e) { resolve(null); }
                return;
            }
            try {
                rq.config({ paths: { 'mermaid_cdn': CDN_PATH }, shim: { 'mermaid_cdn': { exports: 'mermaid' } } });
                rq(['mermaid_cdn'], function(mod) {
                    try {
                        var m = (mod && mod.default) ? mod.default : (mod || window.mermaid);
                        if (m && !window.mermaid) { window.mermaid = m; }
                    } catch (e) {}
                    resolve(window.mermaid || null);
                }, function(){ resolve(window.mermaid || null); });
            } catch (e) { resolve(window.mermaid || null); }
        }).then(function(){ initMermaidIfNeeded(); return window.mermaid || null; });
        return state.promise;
    }

    // (removed obsolete ensure(callback) implementation)

    function normalizeMermaidText(text) {
        try {
            var s = String(text || '');
            // Split into lines for aggressive cleanup.
            var lines = s.split(/\r?\n/);

            // 1) Drop common noise lines that LLMs sometimes include (e.g., "cssCopy", "markdownCopy").
            var noiseline = /^\s*[A-Za-z][A-Za-z0-9_-]*Copy\s*$/i;
            var versionline = /^\s*(?:text)?mermaid(?:\s+version.*)?\s*$/i;
            var syntaxerrorline = /^\s*syntax\s+error\s+in\b/i;
            var cleaned = [];
            for (var i = 0; i < lines.length; i++) {
                var ln = lines[i];
                if (noiseline.test(ln)) { continue; }
                if (versionline.test(ln)) { continue; }
                if (syntaxerrorline.test(ln)) { continue; }
                // Ignore stray Markdown fences if present inside captured code.
                if (/^\s*```/.test(ln)) { continue; }
                cleaned.push(ln);
            }
            lines = cleaned;

            // 2) Trim leading non-directive lines before the first Mermaid directive.
            var directive = /^(\s*)(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram(?:-v2)?|erDiagram|journey)\b/;
            var firstIdx = -1;
            for (var j = 0; j < lines.length; j++) {
                if (directive.test(lines[j])) { firstIdx = j; break; }
            }
            if (firstIdx > 0) { lines = lines.slice(firstIdx); }

            var t = lines.join('\n').replace(/^\s+/, '').replace(/\s+$/, '');

            // 3) If the block starts with a subgraph without a leading graph directive, prepend a default.
            var hasDirective = /^\s*(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram(?:-v2)?|erDiagram|journey)\b/.test(t);
            var startsWithSub = /^\s*subgraph\b/.test(t);
            if (!hasDirective && startsWithSub) {
                t = 'graph TD\n' + t;
            }
            return t;
        } catch (_) { return text; }
    }

    function hasMermaidClass(codeEl) {
        try {
            var cls = (codeEl && codeEl.className) ? String(codeEl.className).toLowerCase() : '';
            // Match language-mermaid, lang-mermaid, textmermaid, mermaid, etc.
            return cls.indexOf('mermaid') !== -1;
        } catch (_) { return false; }
    }

    // Convert <pre><code class="language-mermaid">...</code></pre> into <div class="mermaid">...</div>
    function convertMermaidFences(root) {
        var base = root && root.jquery ? root[0] : (root || document);
        try {
            var codes = base.querySelectorAll ? base.querySelectorAll('pre code') : [];
            for (var i = 0; i < codes.length; i++) {
                var code = codes[i];
                if (!hasMermaidClass(code)) { continue; }
                var pre = code.parentNode && code.parentNode.tagName === 'PRE' ? code.parentNode : null;
                if (!pre) { continue; }
                // If a neighboring .mermaid already exists (from previous pass), drop the pre to avoid duplicates.
                var next = pre.nextSibling;
                if (next && next.classList && next.classList.contains('mermaid')) { pre.parentNode.removeChild(pre); continue; }
                var raw = code.textContent || code.innerText || '';
                raw = normalizeMermaidText(raw);
                var div = document.createElement('div');
                div.className = 'mermaid';
                div.textContent = raw;
                pre.parentNode.replaceChild(div, pre);
            }
        } catch (e) {}
    }

    function renderIn(root) {
        var base = root && root.jquery ? root[0] : (root || document);
        convertMermaidFences(base);
        return loadMermaid().then(function(mermaid) {
            try {
                function isVisible(el) {
                    try {
                        if (!el) { return false; }
                        if (el.offsetParent === null) { return false; }
                        var style = window.getComputedStyle ? getComputedStyle(el) : null;
                        if (style && (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0')) { return false; }
                        return true;
                    } catch (_) { return true; }
                }
                var targets = [];
                if (base && base.querySelectorAll) {
                    var nodes = base.querySelectorAll('.mermaid');
                    for (var j = 0; j < nodes.length; j++) {
                        var n = nodes[j];
                        if (n.getAttribute('data-mermaid-processed')) { continue; }
                        if (!isVisible(n)) { n.setAttribute('data-mermaid-deferred', '1'); continue; }
                        targets.push(n);
                        n.setAttribute('data-mermaid-processed', '1');
                    }
                }
                if (!mermaid || !targets.length) { return { rendered: 0, available: !!mermaid }; }
                if (mermaid.run) {
                    try {
                        mermaid.run({ nodes: targets });
                        return { rendered: targets.length, available: true };
                    } catch (e) {}
                }
                if (mermaid.init) {
                    try {
                        mermaid.init(undefined, base);
                        return { rendered: targets.length, available: true };
                    } catch (e2) { return { rendered: 0, available: true, error: e2 }; }
                }
                return { rendered: 0, available: true };
            } catch (e3) {
                return { rendered: 0, available: !!window.mermaid, error: e3 };
            }
        });
    }

    function renderDeferred(root) {
        var base = root && root.jquery ? root[0] : (root || document);
        var nodes = base.querySelectorAll ? base.querySelectorAll('.mermaid[data-mermaid-deferred]') : [];
        var any = false;
        for (var i = 0; i < nodes.length; i++) {
            var n = nodes[i];
            if (n.getAttribute('data-mermaid-processed')) { n.removeAttribute('data-mermaid-deferred'); continue; }
            try {
                var style = window.getComputedStyle ? getComputedStyle(n) : null;
                var visible = !(n.offsetParent === null || (style && (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0')));
                if (visible) { n.removeAttribute('data-mermaid-deferred'); any = true; }
            } catch (e) {}
        }
        if (any) { return renderIn(base); }
        return Promise.resolve({ rendered: 0 });
    }

    function setTheme(theme) {
        try {
            if (!window.mermaid || !window.mermaid.initialize) { return; }
            window.mermaid.initialize({ startOnLoad: false, theme: theme === 'dark' ? 'dark' : 'default' });
        } catch (e) {}
    }

    return {
        ensure: function(cb) { var p = loadMermaid(); if (typeof cb === 'function') { p.then(function(){ try { cb(); } catch(e) {} }).catch(function(){ try { cb(); } catch(e2) {} }); } return p; },
        renderIn: renderIn,
        renderDeferred: renderDeferred,
        setTheme: setTheme
    };
});
