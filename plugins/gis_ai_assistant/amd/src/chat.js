// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AI Chat interface JavaScript module.
 *
 * @module     local_gis_ai_assistant/chat
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/str',
    'local_gis_ai_assistant/ui_helpers',
    'local_gis_ai_assistant/markdown',
    'local_gis_ai_assistant/highlight_loader',
    'local_gis_ai_assistant/mermaid_loader'
], function($, Ajax, Notification, Str, UIHelpers, Markdown, HL, Mermaid) {
    'use strict';

    var ChatUI = {
        selectors: {
            form: '#ai-chat-form',
            input: '#ai-message-input',
            messages: '#ai-chat-messages',
            sendBtn: '#ai-send-button',
            streamBtn: '#ai-send-stream-button',
            typingIndicator: '#ai-typing-indicator',
            container: '.ai-chat-container'
        },

        // Re-render any AI message content that has raw markdown stored once Marked is available.
        upgradeMarkdownRender: function(scope) {
            try {
                if (!window.marked) { return; }
                var root = scope && scope.jquery ? scope : $(this.selectors.messages);
                var nodes = root.find('.ai-message-content[data-md-raw]');
                nodes.each(function() {
                    var $el = $(this);
                    var raw = $el.attr('data-md-raw') || '';
                    try {
                        var html = Markdown.toHtml(raw);
                        $el.html(html);
                    } catch (e) {}
                    try {
                        var target = $el[0] || $el;
                        Mermaid.renderIn(target)
                            .then(function(){ try { HL.highlightIn(target); } catch (e2) {} })
                            .catch(function(){ try { HL.highlightIn(target); } catch (e3) {} });
                    } catch (e4) { try { HL.highlightIn($el); } catch (e5) {} }
                });
            } catch (e) {}
        },
        eventSource: null,
        isProcessing: false,
        themeStorageKey: 'aiChatTheme',

        init: function() {
            // Preload Markdown (Marked) and Mermaid for better formatting & diagrams.
            try { if (Markdown && Markdown.ensure) { Markdown.ensure(); } } catch (e) {}
            try { if (Mermaid && Mermaid.ensure) { Mermaid.ensure(); } } catch (e) {}
            // Call explicitly on ChatUI to avoid context/mis-binding issues.
            ChatUI.bindEvents();
            ChatUI.setupKeyboardShortcuts();
            ChatUI.showWelcomeMessage();
            ChatUI.loadHistory();
            ChatUI.setupInputResize();
            ChatUI.toggleSendButtons();
            ChatUI.applySavedTheme();
            // Try upgrading markdown render after libraries load.
            setTimeout(function(){ ChatUI.upgradeMarkdownRender(); }, 300);
        },

        bindEvents: function() {
            var self = this;

            $(this.selectors.form).on('submit', function(e) {
                e.preventDefault();
                self.sendMessage(false);
            });

            $(this.selectors.sendBtn).on('click', function(e) {
                e.preventDefault();
                self.sendMessage(false);
            });

            $(this.selectors.streamBtn).on('click', function(e) {
                e.preventDefault();
                self.sendMessage(true);
            });

            $(this.selectors.input).on('input', function() {
                self.toggleSendButtons();
            });

            // Theme toggle button in template header.
            $(document).on('click', '#ai-toggle-theme', function() {
                self.toggleTheme();
            });

            // Clear chat button.
            $(document).on('click', '#ai-clear-chat', function() {
                self.handleClearChat();
            });
        },

        setupKeyboardShortcuts: function() {
            var self = this;
            $(this.selectors.input).on('keydown', function(e) {
                if (e.ctrlKey && (e.key === 'Enter' || e.keyCode === 13)) {
                    e.preventDefault();
                    self.sendMessage(false);
                }
            });
        },

        setupInputResize: function() {
            var input = $(this.selectors.input);
            input.on('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        },

        showWelcomeMessage: function() {
            var messagesEl = $(this.selectors.messages);
            if (messagesEl.children().length > 0) {
                return; // Template already provided welcome message.
            }
            var self = this;
            Str.get_string('ai_welcome', 'local_gis_ai_assistant').done(function(welcomeText) {
                var welcomeBubble = UIHelpers.createMessageBubble(welcomeText, 'system');
                messagesEl.append(welcomeBubble);
                self.scrollToBottom();
            });
        },

        toggleSendButtons: function() {
            var hasContent = $(this.selectors.input).val().trim().length > 0;
            var isDisabled = this.isProcessing || !hasContent;
            $(this.selectors.sendBtn).prop('disabled', isDisabled);
            $(this.selectors.streamBtn).prop('disabled', isDisabled);
        },

        sendMessage: function(streaming) {
            if (this.isProcessing) return;

            var message = $(this.selectors.input).val().trim();
            if (!message) return;

            this.isProcessing = true;
            this.toggleSendButtons();

            // Add user message bubble
            var userBubble = UIHelpers.createMessageBubble(message, 'user');
            $(this.selectors.messages).append(userBubble);
            
            // Clear input and reset height
            $(this.selectors.input).val('').trigger('input');
            this.scrollToBottom();

            if (streaming) {
                this.sendStreamingMessage(message);
            } else {
                this.sendRegularMessage(message);
            }
        },

        sendRegularMessage: function(message) {
            var self = this;
            UIHelpers.showLoadingBubble($(this.selectors.messages));
            this.scrollToBottom();

            var request = {
                methodname: 'local_gis_ai_assistant_send_message',
                args: { message: message }
            };

            Ajax.call([request])[0]
                .done(function(response) {
                    UIHelpers.removeLoadingBubbles($(self.selectors.messages));
                    if (response && response.success) {
                        var aiBubble;
                        var raw = response.content || '';
                        var htmlClient = Markdown.toHtml(raw);
                        var html = (htmlClient && htmlClient.trim()) ? htmlClient : (response.content_html || '');
                        aiBubble = UIHelpers.createMessageBubbleHtml(html, 'ai');
                        try { aiBubble.find('.ai-message-content').attr('data-md-raw', raw); } catch (e) {}
                        $(self.selectors.messages).append(aiBubble);
                        // Render diagrams first, then highlight code blocks.
                        try {
                            var target = aiBubble[0] || aiBubble;
                            Mermaid.renderIn(target)
                                .then(function(){ try { HL.highlightIn(target); } catch (e2) {} })
                                .catch(function(){ try { HL.highlightIn(target); } catch (e3) {} });
                        } catch (e) { try { HL.highlightIn(aiBubble); } catch (e4) {} }
                        self.scrollToBottom();
                        // Attempt upgrade if Marked loads later.
                        setTimeout(function(){ self.upgradeMarkdownRender(aiBubble); }, 500);
                    } else {
                        self.handleError(response || { error: 'Unknown error' });
                    }
                })
                .fail(function() {
                    UIHelpers.removeLoadingBubbles($(self.selectors.messages));
                    self.handleError({ error: 'Network error occurred' });
                })
                .always(function() {
                    self.isProcessing = false;
                    self.toggleSendButtons();
                    $(self.selectors.input).focus();
                });
        },

        sendStreamingMessage: function(message) {
            var self = this;
            UIHelpers.showLoadingBubble($(this.selectors.messages));
            this.scrollToBottom();

            var request = {
                methodname: 'local_gis_ai_assistant_send_message_stream',
                args: { message: message }
            };

            Ajax.call([request])[0]
                .done(function(response) {
                    if (response && response.success && response.stream_url) {
                        self.startStreaming(response.stream_url);
                    } else {
                        // Fallback to non-streaming if streaming can't start.
                        UIHelpers.removeLoadingBubbles($(self.selectors.messages));
                        self.sendRegularMessage(message);
                    }
                })
                .fail(function() {
                    // Fallback to non-streaming if streaming can't start.
                    UIHelpers.removeLoadingBubbles($(self.selectors.messages));
                    self.sendRegularMessage(message);
                });
        },

        startStreaming: function(streamUrl) {
            var self = this;
            UIHelpers.removeLoadingBubbles($(this.selectors.messages));
            var aiBubble = UIHelpers.createMessageBubble('', 'ai');
            $(this.selectors.messages).append(aiBubble);
            var contentEl = aiBubble.find('.ai-message-content');
            // Accumulate raw streamed text to preserve exact newlines.
            var streamBuffer = '';
            var progressiveTimer = null;
            function scheduleProgressiveRender() {
                if (progressiveTimer) { return; }
                progressiveTimer = setTimeout(function() {
                    progressiveTimer = null;
                    try {
                        var html = Markdown.toHtml(streamBuffer);
                        contentEl.html(html);
                        // Do not render Mermaid here; diagrams may be incomplete mid-stream.
                        HL.highlightIn(contentEl);
                    } catch (e) {}
                    self.scrollToBottom();
                }, 150);
            }
            this.scrollToBottom();

            this.eventSource = new EventSource(streamUrl);
            this.eventSource.onmessage = function(event) {
                try {
                    var data = JSON.parse(event.data);
                    switch (data.type) {
                        case 'content':
                            streamBuffer += (data.content || '');
                            // Progressively render formatted markdown while streaming (throttled).
                            scheduleProgressiveRender();
                            break;
                        case 'done':
                            // Prefer server-formatted HTML if provided; else render client-side.
                            try {
                                var html2 = (data && data.content_html) ? data.content_html : Markdown.toHtml(streamBuffer);
                                contentEl.html(html2 || '');
                                contentEl.attr('data-md-raw', streamBuffer);
                            } catch (e) {}
                            // Render diagrams first, then re-highlight.
                            try {
                                var target3 = contentEl[0] || contentEl;
                                Mermaid.renderIn(target3)
                                    .then(function(){ try { HL.highlightIn(target3); } catch (e2) {} })
                                    .catch(function(){ try { HL.highlightIn(target3); } catch (e3) {} });
                            } catch (e) { try { HL.highlightIn(contentEl); } catch (e4) {} }
                            self.scrollToBottom();
                            self.eventSource.close();
                            self.eventSource = null;
                            self.isProcessing = false;
                            self.toggleSendButtons();
                            $(self.selectors.input).focus();
                            // Attempt upgrade if Marked loads later.
                            setTimeout(function(){ self.upgradeMarkdownRender(contentEl); }, 500);
                            break;
                        case 'error':
                            self.eventSource.close();
                            self.eventSource = null;
                            // Remove the partial AI bubble and show error (no fallback).
                            try { aiBubble.remove(); } catch (e) {}
                            self.isProcessing = false;
                            self.toggleSendButtons();
                            self.handleError(data);
                            break;
                    }
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.error('Error parsing SSE data:', e);
                }
            };

            this.eventSource.onerror = function() {
                // Connection failed; clean up and show error (no fallback).
                try { self.eventSource.close(); } catch (e) {}
                self.eventSource = null;
                try { aiBubble.remove(); } catch (e) {}
                self.isProcessing = false;
                self.toggleSendButtons();
                self.handleError({ error: 'Streaming connection failed' });
            };

            setTimeout(function() {
                if (self.eventSource) {
                    self.eventSource.close();
                    self.eventSource = null;
                    self.handleError({ error: 'Streaming timeout' });
                    self.isProcessing = false;
                    self.toggleSendButtons();
                }
            }, 120000);
        },

        applySavedTheme: function() {
            var pref = null;
            try { pref = window.localStorage.getItem(this.themeStorageKey); } catch (e) {}
            var $container = $(this.selectors.container).first();
            if (!$container.length) { return; }
            if (pref === 'dark') {
                $container.addClass('ai-dark');
            } else if (pref === 'light') {
                $container.removeClass('ai-dark');
            }
        },

        toggleTheme: function() {
            var $container = $(this.selectors.container).first();
            if (!$container.length) { return; }
            var makingDark = !$container.hasClass('ai-dark');
            $container.toggleClass('ai-dark', makingDark);
            try { window.localStorage.setItem(this.themeStorageKey, makingDark ? 'dark' : 'light'); } catch (e) {}
            // Sync Mermaid theme and re-render any deferred diagrams.
            try { if (Mermaid && Mermaid.setTheme) { Mermaid.setTheme(makingDark ? 'dark' : 'default'); } } catch (e2) {}
            try { if (Mermaid && Mermaid.renderDeferred) { Mermaid.renderDeferred($container[0]); } } catch (e3) {}
            // Re-highlight code blocks to adjust colors under new theme.
            try { if (HL && HL.highlightIn) { HL.highlightIn($container); } } catch (e4) {}
        },

        loadHistory: function() {
            var self = this;
            var request = {
                methodname: 'local_gis_ai_assistant_get_history',
                args: { limit: 100 }
            };
            Ajax.call([request])[0]
                .done(function(response) {
                    if (!response || !response.success || !response.history) { return; }
                    var $messages = $(self.selectors.messages);
                    response.history.forEach(function(entry) {
                        if (entry.message) {
                            var userBubble = UIHelpers.createMessageBubble(entry.message, 'user');
                            $messages.append(userBubble);
                        }
                        var raw = (entry && entry.response) || '';
                        var htmlClient = Markdown.toHtml(raw);
                        var html = (htmlClient && htmlClient.trim()) ? htmlClient : ((entry && entry.response_html) || '');
                        var aiBubbleHtml = UIHelpers.createMessageBubbleHtml(html, 'ai');
                        try { aiBubbleHtml.find('.ai-message-content').attr('data-md-raw', raw); } catch (e) {}
                        $messages.append(aiBubbleHtml);
                        try {
                            var target2 = aiBubbleHtml[0] || aiBubbleHtml;
                            Mermaid.renderIn(target2)
                                .then(function(){ try { HL.highlightIn(target2); } catch (e2) {} })
                                .catch(function(){ try { HL.highlightIn(target2); } catch (e3) {} });
                        } catch (e) { try { HL.highlightIn(aiBubbleHtml); } catch (e4) {} }
                    });
                    self.scrollToBottom();
                    // Attempt upgrade pass after history load.
                    setTimeout(function(){ self.upgradeMarkdownRender($messages); }, 600);
                })
                .fail(function() {
                    // Ignore history load errors silently.
                });
        },

        handleClearChat: function() {
            var self = this;
            Str.get_string('confirm_clear_chat', 'local_gis_ai_assistant').done(function(confirmText) {
                if (!window.confirm(confirmText)) { return; }
                var $messages = $(self.selectors.messages);
                $messages.empty();
                // Re-add welcome message bubble.
                Str.get_string('ai_welcome', 'local_gis_ai_assistant').done(function(welcomeText) {
                    var welcomeBubble = UIHelpers.createMessageBubble(welcomeText, 'system');
                    $messages.append(welcomeBubble);
                    self.scrollToBottom();
                    $(self.selectors.input).focus();
                }).fail(function() {
                    // If string fetch fails, just focus input.
                    $(self.selectors.input).focus();
                });
            });
        },

        handleError: function(error) {
            // Prefer backend-provided messages for clarity.
            var message = (error && (error.error || error.message)) || null;
            if (message) {
                Notification.addNotification({ message: message, type: 'error' });
                return;
            }
            // Fallback to localized string with parameter to avoid showing {$a}.
            var details = 'Unknown error';
            try { details = JSON.stringify(error); } catch (e) {}
            Str.get_string('error_api_request_failed', 'local_gis_ai_assistant', details).done(function(msg) {
                Notification.addNotification({ message: msg, type: 'error' });
            }).fail(function() {
                Notification.addNotification({ message: 'An error occurred', type: 'error' });
            });
        },

        scrollToBottom: function() {
            var container = $(this.selectors.messages);
            container.scrollTop(container[0].scrollHeight);
        }
    };

    return {
        init: function() { ChatUI.init(); }
    };
});
