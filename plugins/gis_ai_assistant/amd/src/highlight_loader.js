// highlight_loader.js
// AMD module to load Highlight.js and apply syntax highlighting safely.

define([], function() {
    'use strict';

    // Use browser bundles only; avoid AMD/CommonJS builds that require 'core'.
    const LOCAL_PATH = '/local/gis_ai_assistant/assets/highlight.min.js';
    const CDN_PATHS = [
        // Prefer cdnjs (often whitelisted in CSPs)
        'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js',
        // Unpkg CDN distribution bundle
        'https://unpkg.com/@highlightjs/cdn-assets@11.9.0/highlight.min.js',
        // jsDelivr CDN distribution bundle
        'https://cdn.jsdelivr.net/npm/@highlightjs/cdn-assets@11.9.0/highlight.min.js',
        // Last resort: jsDelivr build bundle
        'https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/build/highlight.min.js'
    ];
    const state = { promise: null };

    function loadHighlight() {
        if (state.promise) return state.promise;

        state.promise = new Promise((resolve) => {
            if (window.hljs) return resolve(window.hljs);

            const tryUrls = (urls) => {
                if (!urls.length) return resolve(null);
                const url = urls.shift();
                // Configure a temporary module id with shim to export window.hljs if needed
                if (typeof window.requirejs !== 'object' || typeof window.requirejs.config !== 'function') {
                    console.warn('RequireJS config unavailable; cannot load', url);
                    tryUrls(urls);
                    return;
                }
                var id = 'ai_hljs_' + Math.floor(Math.random() * 1e6);
                var pathNoExt = url.replace(/\.js($|\?)/, '');
                try {
                    window.requirejs.config({
                        paths: (function(){ var o={}; o[id] = pathNoExt; return o; })(),
                        shim: (function(){ var o={}; o[id] = { exports: 'hljs' }; return o; })()
                    });
                    window.require([id], function(mod) {
                        var hl = (mod && typeof mod.highlightElement === 'function') ? mod : window.hljs;
                        if (hl && typeof hl.highlightElement === 'function') {
                            resolve(hl);
                        } else {
                            tryUrls(urls);
                        }
                    }, function(err) {
                        console.warn('Failed to load Highlight.js via RequireJS from', url, err);
                        tryUrls(urls);
                    });
                } catch (e) {
                    console.warn('RequireJS config/load threw for', url, e);
                    tryUrls(urls);
                }
            };

            tryUrls([ LOCAL_PATH, ...CDN_PATHS ]);
        });

        return state.promise;
    }

    function apply(root) {
        return loadHighlight().then((hljs) => {
            if (!hljs) {
                console.warn('Highlight.js not loaded, skipping syntax highlighting.');
                return;
            }

            const blocks = (root || document).querySelectorAll('pre code');
            blocks.forEach((block) => {
                try {
                    hljs.highlightElement(block);
                } catch (e) {
                    console.warn('Highlight failed on block:', e);
                }
            });
        });
    }

    return { load: loadHighlight, apply };
});
