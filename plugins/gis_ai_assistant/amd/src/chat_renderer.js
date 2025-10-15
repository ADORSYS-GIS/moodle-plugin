define([
    'local_gis_ai_assistant/markdown_engine'
], function(MarkdownEngine) {
    'use strict';

    // Internal state
    var state = {
        loading: false,
        promise: null,
        observersStarted: false
    };

    /* -------------------------
       Ensure markdown engine dependencies (idempotent)
    --------------------------*/
    function ensure() {
        if (state.promise) { return state.promise; }
        state.promise = (MarkdownEngine && MarkdownEngine.ensure ? MarkdownEngine.ensure() : Promise.resolve())
            .then(function(){ state.loading = false; return {}; });
        return state.promise;
    }

    /* -------------------------
       Markdown synchronous best-effort render (if marked is not present)
    --------------------------*/
    function toHtmlSync(md) {
        try {
            if (MarkdownEngine && MarkdownEngine.render) {
                return MarkdownEngine.render(md);
            }
        } catch (e) { /* fallthrough to fallback */ }

        // Very small safe fallback (escape + minimal fences)
        try {
            var esc = escapeHtml(String(md || ''));
            // preserve fenced code blocks (simple)
            esc = esc.replace(/```([a-zA-Z0-9_+\-]*)\n([\s\S]*?)```/g, function(_, lang, code){
                return '<pre><code class="language-' + (lang || '') + '">' + escapeHtml(code) + '</code></pre>';
            });
            return esc.replace(/\n/g, '<br>');
        } catch (e2) { return escapeHtml(String(md || '')); }
    }

    /* -------------------------
       Async toHtml (waits for marked if possible)
    --------------------------*/
    function toHtml(md) {
        return ensure().then(function() {
            if (MarkdownEngine && MarkdownEngine.renderAsync) {
                return MarkdownEngine.renderAsync(md).catch(function(){ return toHtmlSync(md); });
            }
            return toHtmlSync(md);
        });
    }

    /* -------------------------
       Helper: escapeHtml
    --------------------------*/
    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = String(text || '');
        return d.innerHTML;
    }

    // (Legacy conversions and manual highlight removed)

    /* -------------------------
       Main render pipeline: given a root (element or jQuery) it will:
       - take innerText or provided markdown,
        - run toHtmlSync/toHtml as appropriate,
       - insert HTML into container(s),
       - apply syntax highlighting via markdown_engine.
       Returns Promise.
    --------------------------*/
    function renderIn(rootOrElement) {
        var base = rootOrElement && rootOrElement.jquery ? rootOrElement[0] : (rootOrElement || document);
        try {
            // Convert any raw markdown elements: in our UI we may place elements with data-md-raw attribute
            var mdnodes = base.querySelectorAll ? base.querySelectorAll('[data-md-raw]') : [];
            var p = Promise.resolve();
            for (var i = 0; i < mdnodes.length; i++) {
                (function(el){
                    p = p.then(function() {
                        var raw = el.getAttribute('data-md-raw') || '';
                        return toHtml(raw).then(function(html){
                            el.innerHTML = html;
                        }).catch(function(){ el.innerHTML = toHtmlSync(raw); });
                    });
                })(mdnodes[i]);
            }

            // Also convert plain text nodes (elements with data-md-text) — helpful for streaming buffers
            var textnodes = base.querySelectorAll ? base.querySelectorAll('[data-md-text]') : [];
            for (i = 0; i < textnodes.length; i++) {
                (function(el) {
                    p = p.then(function(){
                        var raw = el.getAttribute('data-md-text') || '';
                        return toHtml(raw).then(function(html){ el.innerHTML = html; }).catch(function(){ el.innerHTML = toHtmlSync(raw); });
                    });
                })(textnodes[i]);
            }

            return p.then(function() {
                if (MarkdownEngine && MarkdownEngine.enhance) {
                    return MarkdownEngine.enhance(base).then(function(){ return { success: true }; });
                }
                return Promise.resolve({ success: true });
            });
        } catch (e) {
            return Promise.resolve({ success: false, error: e });
        }
    }

    /* -------------------------
       Start MutationObserver + IntersectionObserver to handle late-added nodes and deferred mermaid
    --------------------------*/
    function observe(root) {
        try {
            if (state.observersStarted) { return; }
            var base = root && root.jquery ? root[0] : (root || document.body);
            var mo = new MutationObserver(function(mutations) {
                var shouldRun = false;
                mutations.forEach(function(m) {
                    if (m.addedNodes && m.addedNodes.length) {
                        for (var j = 0; j < m.addedNodes.length; j++) {
                            var n = m.addedNodes[j];
                            if (n.nodeType !== 1) { continue; }
                            if (n.querySelector && (n.querySelector('pre code') || (n.hasAttribute && (n.hasAttribute('data-md-raw') || n.hasAttribute('data-md-text'))))) {
                                shouldRun = true;
                            }
                        }
                    }
                });
                if (shouldRun) { renderIn(base).catch(function(){}); }
            });
            mo.observe(base, { childList: true, subtree: true });
            state.observersStarted = true;
            state._mo = mo;
        } catch (e) {}
    }

    /* -------------------------
       Public API
    --------------------------*/
    return {
        ensure: ensure,
        toHtmlSync: toHtmlSync,
        toHtml: toHtml,
        renderIn: renderIn,
        observe: observe
    };
});
