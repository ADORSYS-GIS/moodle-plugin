define([
    'jquery',
    'local_gis_ai_assistant/chat_core',
    'local_gis_ai_assistant/chat_renderer',
    'local_gis_ai_assistant/utils'
], function($, ChatCore, Renderer, Utils) {
    'use strict';

    var UI = (function() {
        var $container, $messages, $input, $send, $theme;
        var streamingBuffer = '';
        var currentStreamingEl = null;

        function createWidget() {
            // Avoid duplicate
            if ($('#ai-chat-widget').length) { return; }

            var html = '\
                <div id="ai-chat-widget" class="ai-chat-widget">\
                    <div class="ai-chat-header">\
                        <div class="ai-title">🤖 GIS AI</div>\
                        <div class="ai-actions">\
                            <button id="ai-toggle-theme" title="Toggle theme">🌓</button>\
                        </div>\
                    </div>\
                    <div id="ai-chat-messages" class="ai-chat-messages" role="log" aria-live="polite"></div>\
                    <div class="ai-chat-input-area">\
                        <textarea id="ai-message-input" class="ai-message-input" rows="2" placeholder="Ask me anything..."></textarea>\
                        <button id="ai-send-button" class="btn btn-primary">Send</button>\
                    </div>\
                </div>';
            $('body').append(html);
            $container = $('#ai-chat-widget');
            $messages = $('#ai-chat-messages');
            $input = $('#ai-message-input');
            $send = $('#ai-send-button');
            $theme = $('#ai-toggle-theme');

            try { $messages.addClass('chat-container'); } catch (e) {}

            // Wire up
            $send.on('click', function(e){ e.preventDefault(); submitMessage(false); });
            $input.on('keydown', function(e){ if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submitMessage(false); }});
            $theme.on('click', function(){ toggleTheme(); });

            // Start renderer observers
            Renderer.observe($container[0]);
        }

        function submitMessage(streaming) {
            var text = $input.val().trim();
            if (!text) return;
            $input.val('').trigger('input');
            if (streaming) {
                ChatCore.startStreaming(text);
            } else {
                ChatCore.sendMessage(text);
            }
        }

        function appendBubble(role, htmlOrText, opts) {
            opts = opts || {};
            // Map role to ChatGPT-like classes
            var roleClass = (role === 'ai' || role === 'assistant') ? 'assistant' : (role || 'system');
            var $b = $('<div class="chat-bubble"></div>').addClass(roleClass);
            if (opts.raw) {
                // keep raw markdown in data attribute so renderer can convert
                var el = document.createElement('div');
                el.setAttribute('data-md-raw', htmlOrText);
                $b.append(el);
            } else {
                $b.html(htmlOrText);
            }
            $messages.append($b);
            // Run renderer on this bubble (markdown-it + highlight)
            Renderer.renderIn($b[0]).then(function(){ /* nothing */ }).catch(function(){ /* nothing */ });
            scrollToBottom();
            return $b;
        }

        function scrollToBottom() {
            try { $messages[0].scrollTop = $messages[0].scrollHeight; } catch (e) {}
        }

        // Event handlers from ChatCore
        function wireCoreEvents() {
            ChatCore.on('message_added', function(msg) {
                if (msg.role === 'user') {
                    appendBubble('user', escapeHtml(msg.content));
                } else if (msg.role === 'assistant') {
                    // For streaming started we may get 'assistant' with empty content first
                    if (msg.streaming) {
                        currentStreamingEl = appendBubble('assistant', '', {});
                        streamingBuffer = '';
                    } else {
                        // Prefer raw markdown via markdown-it; fallback to server HTML
                        if (msg.content) {
                            appendBubble('assistant', msg.content, { raw: true });
                        } else if (msg.raw) {
                            appendBubble('assistant', msg.raw, { raw: true });
                        } else if (msg.content_html) {
                            appendBubble('assistant', msg.content_html, {});
                        } else {
                            appendBubble('assistant', '', {});
                        }
                    }
                }
            });

            ChatCore.on('message_stream_chunk', function(data) {
                streamingBuffer = data.buffer || (streamingBuffer + (data.content || ''));
                if (!currentStreamingEl) {
                    currentStreamingEl = appendBubble('assistant', '', {});
                }
                try {
                    // progressively update as text (use data-md-text so renderer can convert later)
                    var $content = $(currentStreamingEl).find('[data-md-text]');
                    if (!$content.length) {
                        var el = document.createElement('div');
                        el.setAttribute('data-md-text', streamingBuffer);
                        $(currentStreamingEl).empty().append(el);
                    } else {
                        $content.attr('data-md-text', streamingBuffer);
                    }
                    // Attempt a lightweight sync render for speed
                    Renderer.renderIn(currentStreamingEl);
                } catch (e) {}
                scrollToBottom();
            });

            ChatCore.on('message_stream_done', function(data) {
                if (!currentStreamingEl) {
                    currentStreamingEl = appendBubble('assistant', '', {});
                }
                try {
                    // Prefer raw markdown; fall back to server HTML
                    var rawFinal = data.buffer || streamingBuffer || '';
                    if (rawFinal) {
                        var el = document.createElement('div');
                        el.setAttribute('data-md-raw', rawFinal);
                        currentStreamingEl.innerHTML = '';
                        currentStreamingEl.appendChild(el);
                    } else if (data.content_html) {
                        currentStreamingEl.innerHTML = data.content_html;
                    }
                    Renderer.renderIn(currentStreamingEl);
                } catch (e) {}
                currentStreamingEl = null;
                streamingBuffer = '';
                scrollToBottom();
            });

            ChatCore.on('message_stream_error', function(data) {
                appendBubble('system', 'Stream error: ' + (data && data.error ? data.error : 'unknown'));
                currentStreamingEl = null;
                streamingBuffer = '';
                scrollToBottom();
            });
        }

        function toggleTheme() {
            // toggle body attribute to allow CSS to switch
            var current = document.documentElement.getAttribute('data-theme') || 'dark';
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            // Renderer no longer needs per-theme adjustments
        }

        function escapeHtml(s) {
            var d = document.createElement('div');
            d.textContent = String(s || '');
            return d.innerHTML;
        }

        function init() {
            try { if (Utils && Utils.injectCssOnce) { Utils.injectCssOnce(M.cfg.wwwroot + '/local/gis_ai_assistant/amd/src/styles.css'); } } catch (e) {}
            createWidget();
            wireCoreEvents();
            // load history & render
            ChatCore.getHistory(100).then(function(hist) {
                hist.forEach(function(h) {
                    if (h.message) { appendBubble('user', escapeHtml(h.message)); }
                    var raw = h.response || '';
                    if (raw) {
                        appendBubble('assistant', raw, { raw: true });
                    } else if (h.response_html) {
                        appendBubble('assistant', h.response_html, {});
                    }
                });
                // after history inserted, run a global render pass
                Renderer.renderIn($messages[0]);
                // start observing for late nodes
                Renderer.observe($messages[0]);
            }).catch(function(){});
        }

        return { init: init };
    })();

    return UI;
});
