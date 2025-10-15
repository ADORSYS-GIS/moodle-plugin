// markdown_engine.js
// A robust Markdown engine inspired by VSCode Markdown Preview Enhanced.
// - Uses markdown-it with optional plugins (task-lists, footnote, anchor, container, emoji)
// - Sanitizes with DOMPurify (fallback to conservative sanitizer)
// - Progressive enhancement: highlight.js only (Mermaid removed)
//
// Public API:
//   ensure(): Promise<void>
//   render(md: string): string  // sync after ensure, returns sanitized HTML
//   renderAsync(md: string): Promise<string>
//   enhance(root: HTMLElement): Promise<void> // apply syntax highlighting

define([
    'local_gis_ai_assistant/highlight_loader'
], function(HighlightLoader) {
    'use strict';

    var CONFIG = {
        // Core libs
        markdownItCdn: 'https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js',
        dompurifyCdn: 'https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js',
        // Plugins (optional)
        footnoteCdn: 'https://cdn.jsdelivr.net/npm/markdown-it-footnote@3.0.3/dist/markdown-it-footnote.min.js',
        taskListsCdn: 'https://cdn.jsdelivr.net/npm/markdown-it-task-lists@2.1.1/dist/markdown-it-task-lists.min.js',
        anchorCdn: 'https://cdn.jsdelivr.net/npm/markdown-it-anchor@8.6.7/dist/markdownItAnchor.umd.js',
        containerCdn: 'https://cdn.jsdelivr.net/npm/markdown-it-container@3.0.0/dist/markdown-it-container.min.js',
        emojiCdn: 'https://cdn.jsdelivr.net/npm/markdown-it-emoji@2.0.2/dist/markdown-it-emoji-bare.min.js'
    };

    var state = {
        loading: false,
        ready: false,
        promise: null,
        mdFactory: null,
        mdInstance: null,
        DOMPurify: null,
        plugins: {}
    };

    function injectScript(url) {
        return new Promise(function(resolve, reject) {
            // Prefer RequireJS to bind anonymous define() correctly.
            if (typeof window.require === 'function') {
                try {
                    window.require([url], function() { resolve(url); }, function(err) {
                        // Fallback to script tag below on failure
                        tryInjectViaScript();
                    });
                    return;
                } catch (_) { /* fall through to script tag */ }
            }

            tryInjectViaScript();

            function tryInjectViaScript() {
                try {
                    var s = document.createElement('script');
                    s.async = true;
                    s.src = url;

                    // In Moodle (RequireJS present), UMD bundles may try to anonymous define(),
                    // causing "Mismatched anonymous define". Temporarily disable AMD detection.
                    var hadDefine = typeof window.define === 'function' && window.define.amd;
                    var savedDefine = hadDefine ? window.define : null;
                    if (hadDefine) { try { window.define = undefined; } catch (e) {} }

                    s.onload = function() {
                        if (hadDefine) { try { window.define = savedDefine; } catch (e) {} }
                        resolve(url);
                    };
                    s.onerror = function() {
                        if (hadDefine) { try { window.define = savedDefine; } catch (e) {} }
                        reject(new Error('Failed to load ' + url));
                    };
                    document.head.appendChild(s);
                } catch (e) { reject(e); }
            }
        });
    }

    function ensure() {
        if (state.promise) { return state.promise; }
        state.promise = new Promise(function(resolve) {
            var chain = Promise.resolve();

            // markdown-it
            if (!window.markdownit) {
                chain = chain.then(function(){ return injectScript(CONFIG.markdownItCdn).catch(function(){ /* ignore */ }); });
            }
            // DOMPurify
            if (!window.DOMPurify) {
                chain = chain.then(function(){ return injectScript(CONFIG.dompurifyCdn).catch(function(){ /* ignore */ }); });
            }
            // Plugins (best-effort)
            var pluginUrls = [CONFIG.footnoteCdn, CONFIG.taskListsCdn, CONFIG.anchorCdn, CONFIG.containerCdn, CONFIG.emojiCdn];
            chain = chain.then(function(){
                var p = Promise.resolve();
                pluginUrls.forEach(function(u){ p = p.then(function(){ return injectScript(u).catch(function(){ /* ignore */ }); }); });
                return p;
            });

            chain.then(function(){
                state.mdFactory = window.markdownit || null;
                state.DOMPurify = window.DOMPurify || null;
                state.plugins = {
                    footnote: window.markdownitFootnote || window.markdownit_footnote || null,
                    tasklists: window.markdownitTaskLists || window.markdownit_task_lists || null,
                    anchor: window.markdownitAnchor || window.markdownItAnchor || null,
                    container: window.markdownitContainer || window.markdownit_container || null,
                    emoji: window.markdownitEmoji || window.markdownit_emoji || null
                };
                state.ready = !!state.mdFactory;
                resolve();
            }).catch(function(){ resolve(); });
        });
        return state.promise;
    }

    function buildInstance() {
        if (state.mdInstance) { return state.mdInstance; }
        if (!state.mdFactory) { return null; }
        try {
            var md = state.mdFactory({
                html: false,
                linkify: true,
                breaks: true,
                typographer: true
            });
            // Plugins best-effort
            try { if (state.plugins.footnote) { md.use(state.plugins.footnote); } } catch (_) {}
            try { if (state.plugins.tasklists) { md.use(state.plugins.tasklists, { enabled: true, label: true }); } } catch (_) {}
            try { if (state.plugins.anchor) { md.use(state.plugins.anchor, { permalink: false }); } } catch (_) {}
            try {
                if (state.plugins.container) {
                    ['info','tip','note','warning','danger'].forEach(function(name){
                        try { md.use(state.plugins.container, name); } catch(_) {}
                    });
                }
            } catch (_) {}
            try { if (state.plugins.emoji) { md.use(state.plugins.emoji); } } catch(_) {}

            state.mdInstance = md;
            return md;
        } catch (e) { return null; }
    }

    function conservativeSanitize(html) {
        try {
            var div = document.createElement('div');
            div.innerHTML = String(html || '');
            var bad = ['script','iframe','object','embed','link','style','meta'];
            bad.forEach(function(tag){ var nodes = div.getElementsByTagName(tag); for (var i = nodes.length - 1; i >= 0; i--) { nodes[i].remove(); } });
            var walker = document.createTreeWalker(div, NodeFilter.SHOW_ELEMENT, null, false);
            while (walker.nextNode()) {
                var el = walker.currentNode;
                var attrs = Array.prototype.slice.call(el.attributes || []);
                attrs.forEach(function(a){ var n=a.name.toLowerCase(); var v=(a.value||'').toLowerCase(); if (n.indexOf('on')===0 || v.indexOf('javascript:')===0) { el.removeAttribute(a.name); } });
                if (el.tagName === 'A' && el.getAttribute('target') === '_blank') { el.setAttribute('rel','noopener noreferrer'); }
            }
            return div.innerHTML;
        } catch (e) { return String(html || ''); }
    }

    function renderSync(mdText) {
        var md = buildInstance();
        if (!md) {
            // Fallback: escape only
            var d = document.createElement('div');
            d.textContent = String(mdText || '');
            return d.innerHTML;
        }
        var raw = String(mdText || '');
        var html = md.render(raw);
        if (state.DOMPurify && state.DOMPurify.sanitize) { return state.DOMPurify.sanitize(html); }
        return conservativeSanitize(html);
    }

    function renderAsync(mdText) {
        return ensure().then(function(){ return renderSync(mdText); });
    }

    function enhance(root) {
        // Only syntax highlight; Mermaid is no longer used
        return HighlightLoader.apply(root);
    }

    return {
        ensure: ensure,
        render: renderSync,
        renderAsync: renderAsync,
        enhance: enhance
    };
});
