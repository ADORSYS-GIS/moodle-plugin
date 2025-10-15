// mermaid_loader.js
// AMD module to load Mermaid and render mermaid diagrams inside a root element.
// Converts <pre><code class="language-mermaid">...</code></pre> into <div class="mermaid">...</div> and renders them.

define([], function() {
    'use strict';

    const LOCAL_PATH = '/local/gis_ai_assistant/assets/mermaid.min.js';
    const CDN_PATHS = [
        'https://cdn.jsdelivr.net/npm/mermaid@10.9.1/dist/mermaid.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/mermaid/10.9.1/mermaid.min.js'
    ];
    const state = { promise: null };

    function detectTheme(preferFromDom) {
        try {
            if (preferFromDom) {
                if (document.querySelector('.ai-dark')) return 'dark';
                if (document.querySelector('.ai-light')) return 'default';
            }
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
                return 'dark';
        } catch (_) {}
        return 'default';
    }

    function initMermaidIfNeeded() {
        try {
            if (!window.mermaid || window.mermaid.__ai_inited) return;
            const theme = detectTheme(true);
            window.mermaid.initialize({
                startOnLoad: false,
                theme: theme === 'dark' ? 'dark' : 'default'
            });
            window.mermaid.__ai_inited = true;
        } catch (e) {
            console.warn('Mermaid initialization failed', e);
        }
    }

    function loadMermaid() {
        if (state.promise) return state.promise;

        state.promise = new Promise((resolve) => {
            if (window.mermaid) {
                initMermaidIfNeeded();
                resolve(window.mermaid);
                return;
            }

            const tryUrls = (urls) => {
                if (!urls.length) return resolve(null);
                const url = urls.shift();
                const script = document.createElement('script');
                script.async = true;
                script.src = url;

                script.onload = () => {
                    initMermaidIfNeeded();
                    resolve(window.mermaid || null);
                };
                script.onerror = () => {
                    console.warn('Failed to load Mermaid from', url);
                    script.remove();
                    tryUrls(urls);
                };
                document.head.appendChild(script);
            };

            tryUrls([LOCAL_PATH, ...CDN_PATHS]);
        });

        return state.promise;
    }

    function normalizeMermaidText(text) {
        try {
            let s = String(text || '');
            const lines = s.split(/\r?\n/);
            const cleaned = lines.filter((ln) => {
                if (/^\s*[A-Za-z][A-Za-z0-9_-]*Copy\s*$/.test(ln)) return false;
                if (/^\s*(?:text)?mermaid(?:\s+version.*)?\s*$/i.test(ln)) return false;
                if (/^\s*syntax\s+error\s+in\b/i.test(ln)) return false;
                if (/^\s*```/.test(ln)) return false;
                return true;
            });

            let joined = cleaned.join('\n').trim();
            if (!/^(\s*)(graph|flowchart|sequenceDiagram|classDiagram|stateDiagram(?:-v2)?|erDiagram|journey)\b/m.test(joined)) {
                if (/^\s*subgraph\b/m.test(joined) || /-->|==>|-\.-\>|==/m.test(joined))
                    joined = 'graph TD\n' + joined;
            }

            return joined;
        } catch (_) {
            return text;
        }
    }

    function render(root) {
        return loadMermaid().then((mermaid) => {
            if (!mermaid) {
                console.warn('Mermaid unavailable');
                return;
            }

            const nodes = (root || document).querySelectorAll('.mermaid, pre code.language-mermaid');
            nodes.forEach((node) => {
                let text = node.textContent || '';
                text = normalizeMermaidText(text);

                // Replace <pre><code> with <div class="mermaid">
                if (node.tagName === 'CODE' && node.parentNode.tagName === 'PRE') {
                    const div = document.createElement('div');
                    div.className = 'mermaid';
                    div.textContent = text;
                    node.parentNode.replaceWith(div);
                    node = div;
                } else {
                    node.textContent = text;
                    node.classList.add('mermaid');
                }
            });

            try {
                mermaid.init(undefined, document.querySelectorAll('.mermaid'));
            } catch (e) {
                console.warn('Mermaid render error:', e);
            }
        });
    }

    return { load: loadMermaid, render, normalizeText: normalizeMermaidText };
});
