define([], function() {
    'use strict';

    var Utils = {
        THEME_KEY: 'ai_chat_theme',

        getSystemTheme: function() {
            try { return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'; } catch (e) { return 'light'; }
        },

        getSavedTheme: function() {
            try { return window.localStorage.getItem(this.THEME_KEY) || this.getSystemTheme(); } catch (e) { return this.getSystemTheme(); }
        },

        applyTheme: function(theme) {
            try { document.documentElement.setAttribute('data-theme', theme); } catch (e) {}
        },

        toggleTheme: function() {
            var current = this.getSavedTheme();
            var next = current === 'dark' ? 'light' : 'dark';
            this.applyTheme(next);
            try { window.localStorage.setItem(this.THEME_KEY, next); } catch (e) {}
            return next;
        },

        injectCssOnce: function(href) {
            try {
                if (!href) { return; }
                var id = 'ai-chat-styles-' + btoa(href).replace(/=/g, '');
                if (document.getElementById(id)) { return; }
                var link = document.createElement('link');
                link.id = id;
                link.rel = 'stylesheet';
                link.type = 'text/css';
                link.href = href;
                document.head.appendChild(link);
            } catch (e) {}
        },

        initTheme: function() {
            var theme = this.getSavedTheme();
            this.applyTheme(theme);

            // Create floating theme toggle button
            try {
                if (document.querySelector('.theme-toggle')) { return; }
                var btn = document.createElement('button');
                btn.className = 'theme-toggle';
                btn.innerHTML = theme === 'dark' ? '☀️' : '🌙';
                btn.title = 'Toggle theme';
                btn.addEventListener('click', function() {
                    var next = Utils.toggleTheme();
                    try { btn.innerHTML = next === 'dark' ? '☀️' : '🌙'; } catch (e) {}
                });
                document.body.appendChild(btn);
            } catch (e) {}
        }
    };

    // Auto-init on DOM ready
    try {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function(){ try { Utils.initTheme(); } catch (e) {} });
        } else {
            Utils.initTheme();
        }
    } catch (e) {}

    return Utils;
});
