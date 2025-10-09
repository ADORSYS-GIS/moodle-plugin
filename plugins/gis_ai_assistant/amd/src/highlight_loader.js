// AMD module to ensure highlight.js is available and to highlight code blocks in a root element.
// Usage:
// define(['local_gis_ai_assistant/highlight_loader'], function(HL) {
//   HL.highlightIn($(root));
// });

define([], function() {
    'use strict';

    var state = {
        loading: false,
        queue: [],
        loaded: function() { return !!window.hljs; }
    };

    function getRequire() {
        return (typeof requirejs !== 'undefined') ? requirejs : ((typeof require !== 'undefined') ? require : null);
    }

    function tryLoadRequire() {
        if (state.loaded()) { flushQueue(); return; }
        var rq = getRequire();
        // Fallback: inject <script> if AMD not available
        if (!rq) {
            try {
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js';
                s.onload = function(){ state.loading = false; flushQueue(); };
                s.onerror = function(){ state.loading = false; flushQueue(); };
                ensureCss();
                document.head.appendChild(s);
            } catch (e) { state.loading = false; flushQueue(); }
            return;
        }
        try {
            rq.config({ paths: { 'hljs_cdn': 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min' } });
            ensureCss();
            rq(['hljs_cdn'], function(mod) {
                try {
                    var hl = mod && mod.highlightElement ? mod : (mod && mod.default && mod.default.highlightElement ? mod.default : null);
                    if (hl) { window.hljs = hl; }
                } catch (e) {}
                state.loading = false; flushQueue();
            }, function(){ state.loading = false; flushQueue(); });
        } catch (e) { state.loading = false; flushQueue(); }
    }

    function flushQueue() {
        var cbs = state.queue.slice();
        state.queue.length = 0;
        if (!state.loaded()) { return; }
        for (var i = 0; i < cbs.length; i++) {
            try { cbs[i](); } catch (e) {}
        }
    }

    function ensureCss() {
        try {
            var root = (window.M && M.cfg && M.cfg.wwwroot) ? M.cfg.wwwroot : '';
            if (!root) { return; }
            var href = root + '/local/gis_ai_assistant/styles/highlight.css';
            var exists = false;
            var links = document.getElementsByTagName('link');
            for (var i = 0; i < links.length; i++) {
                if ((links[i].rel || '').toLowerCase() === 'stylesheet' && links[i].href && links[i].href.indexOf('/local/gis_ai_assistant/styles/highlight.css') !== -1) {
                    exists = true; break;
                }
            }
            if (!exists) {
                var l = document.createElement('link');
                l.rel = 'stylesheet';
                l.href = href;
                document.head.appendChild(l);
            }
        } catch (e) {}
    }

    function ensure(callback) {
        return new Promise(function(resolve) {
            if (state.loaded()) { try { callback && callback(); } catch (e) {} resolve(); return; }
            state.queue.push(function() { try { callback && callback(); } catch (e) {} try { resolve(); } catch (_) {} });
            if (state.loading) { return; }
            state.loading = true;
            // Ensure CSS theme is available even if page did not include it.
            ensureCss();
            tryLoadRequire();
        });
    }

    function detectLanguage(block) {
        try {
            // Prefer explicit language-* class if present.
            var classes = Array.prototype.slice.call(block.classList || []);
            for (var i = 0; i < classes.length; i++) {
                var m = classes[i].match(/^(?:language|lang)-([a-z0-9_+\-]+)/i);
                if (m) { return m[1]; }
            }
            // highlight.js often adds a language-xxx class after highlighting.
            if (block.className && /language-([a-z0-9_+\-]+)/i.test(block.className)) {
                return (block.className.match(/language-([a-z0-9_+\-]+)/i) || [,''])[1];
            }
            // Fallback label.
            return 'text';
        } catch (_) { return 'text'; }
    }

    function isAsciiDiagram(text) {
        try {
            if (!text) { return false; }
            var s = String(text);
            // Heuristic: high ratio of box/line drawing symbols and very few letters.
            var diagramChars = (s.match(/[\+\-\|=_\/\\\[\]\(\)<>\.\:]/g) || []).length;
            var letters = (s.match(/[A-Za-z]/g) || []).length;
            var newlines = (s.match(/\n/g) || []).length;
            if (newlines < 2) { return false; }
            return diagramChars > (letters * 2);
        } catch (_) { return false; }
    }

    // Ensure a <code> element does not contain live HTML. If found, convert it to plain text content.
    function sanitizeCodeBlock(block) {
        try {
            // Skip if already highlighted (hljs injects spans intentionally).
            if (block.classList && block.classList.contains('hljs')) { return; }
            // If it contains any child elements (spans, br, em, etc.), strip to text.
            if (block.children && block.children.length > 0) {
                var txt = block.innerText || block.textContent || '';
                block.textContent = txt;
                return;
            }
            // If raw HTML tags appear in innerHTML (and not escaped), strip to text.
            var html = block.innerHTML || '';
            if (/<[A-Za-z!/]/.test(html) && html.indexOf('&lt;') === -1) {
                var txt2 = block.textContent || '';
                block.textContent = txt2;
            }
        } catch (e) {}
    }

    function ensureWrapped(block) {
        try {
            var pre = block && block.parentNode && block.parentNode.tagName === 'PRE' ? block.parentNode : null;
            if (!pre) { return; }
            // If already wrapped anywhere up the tree, skip.
            var p = pre;
            while (p) {
                if (p.classList && p.classList.contains('ai-codeblock')) { return; }
                p = p.parentNode;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'ai-codeblock';

            var header = document.createElement('div');
            header.className = 'ai-codeblock-header';

            var lang = detectLanguage(block);
            var label = document.createElement('span');
            label.className = 'ai-codeblock-lang';
            label.textContent = (lang || 'text').toLowerCase();

            var copy = document.createElement('button');
            copy.type = 'button';
            copy.className = 'ai-codeblock-copy';
            copy.textContent = 'Copy';
            copy.addEventListener('click', function() {
                try {
                    var text = block && block.innerText ? block.innerText : (block.textContent || '');
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function(){
                            copy.textContent = 'Copied';
                            setTimeout(function(){ copy.textContent = 'Copy'; }, 1500);
                        }).catch(function(){
                            var ta = document.createElement('textarea');
                            ta.value = text; document.body.appendChild(ta); ta.select();
                            try { document.execCommand('copy'); } catch (e) {}
                            document.body.removeChild(ta);
                            copy.textContent = 'Copied';
                            setTimeout(function(){ copy.textContent = 'Copy'; }, 1500);
                        });
                    } else {
                        var ta2 = document.createElement('textarea');
                        ta2.value = text; document.body.appendChild(ta2); ta2.select();
                        try { document.execCommand('copy'); } catch (e) {}
                        document.body.removeChild(ta2);
                        copy.textContent = 'Copied';
                        setTimeout(function(){ copy.textContent = 'Copy'; }, 1500);
                    }
                } catch (e) {}
            });

            header.appendChild(label);
            header.appendChild(copy);

            var codewrap = document.createElement('div');
            codewrap.className = 'ai-codeblock-code';

            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(header);
            wrapper.appendChild(codewrap);
            codewrap.appendChild(pre);
        } catch (e) {}
    }

    function highlightIn(root) {
        var $root = root;
        // Accept either jQuery or a plain Element.
        var findBlocks = function(cb) {
            if ($root && typeof $root.find === 'function') {
                $root.find('pre code').each(function(i, block) { cb(block); });
            } else {
                var el = $root && $root.querySelectorAll ? $root : document;
                var nodes = el.querySelectorAll('pre code');
                for (var i = 0; i < nodes.length; i++) { cb(nodes[i]); }
            }
        };
        ensure(function() {
            try {
                findBlocks(function(block) {
                    try {
                        // Skip mermaid code blocks defensively (they should be converted before highlighting).
                        if (block.classList && (block.classList.contains('language-mermaid') || block.classList.contains('lang-mermaid'))) {
                            return;
                        }
                        // Configure hljs to ignore warnings and avoid console noise.
                        if (window.hljs && window.hljs.configure) {
                            window.hljs.configure({ ignoreUnescapedHTML: true });
                        }
                        // Strip any live HTML inside code block to avoid security warnings.
                        sanitizeCodeBlock(block);
                        // Decide language before highlighting to keep consistency.
                        var lang = detectLanguage(block);
                        if (!block.classList.contains('language-') && (!lang || lang === 'text')) {
                            var content = block.innerText || block.textContent || '';
                            if (isAsciiDiagram(content)) {
                                block.classList.add('language-plaintext');
                            }
                        }
                        // Highlight if needed.
                        if (!(block.classList && block.classList.contains('hljs'))) {
                            window.hljs.highlightElement(block);
                        }
                        // Wrap with header/copy and consistent container.
                        ensureWrapped(block);
                    } catch (e) {}
                });
            } catch (e) {}
        });
    }

    return {
        ensure: ensure,
        highlightIn: highlightIn
    };
});
