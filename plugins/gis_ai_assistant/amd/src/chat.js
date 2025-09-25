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
    'local_gis_ai_assistant/ui_helpers'
], function($, Ajax, Notification, Str, UIHelpers) {
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
        eventSource: null,
        isProcessing: false,
        themeStorageKey: 'aiChatTheme',

        init: function() {
            this.bindEvents();
            this.setupKeyboardShortcuts();
            this.showWelcomeMessage();
            this.setupInputResize();
            this.toggleSendButtons();
            this.applySavedTheme();
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
                        var aiBubble = UIHelpers.createMessageBubble(response.content, 'ai');
                        $(self.selectors.messages).append(aiBubble);
                        self.scrollToBottom();
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
                        UIHelpers.removeLoadingBubbles($(self.selectors.messages));
                        self.handleError(response || { error: 'Failed to start streaming' });
                        self.isProcessing = false;
                        self.toggleSendButtons();
                    }
                })
                .fail(function() {
                    UIHelpers.removeLoadingBubbles($(self.selectors.messages));
                    self.handleError({ error: 'Failed to start streaming' });
                    self.isProcessing = false;
                    self.toggleSendButtons();
                });
        },

        startStreaming: function(streamUrl) {
            var self = this;
            UIHelpers.removeLoadingBubbles($(this.selectors.messages));
            var aiBubble = UIHelpers.createMessageBubble('', 'ai');
            $(this.selectors.messages).append(aiBubble);
            var contentEl = aiBubble.find('.ai-message-content');
            this.scrollToBottom();

            this.eventSource = new EventSource(streamUrl);
            this.eventSource.onmessage = function(event) {
                try {
                    var data = JSON.parse(event.data);
                    switch (data.type) {
                        case 'content':
                            contentEl.append(document.createTextNode(data.content));
                            self.scrollToBottom();
                            break;
                        case 'done':
                            self.eventSource.close();
                            self.eventSource = null;
                            self.isProcessing = false;
                            self.toggleSendButtons();
                            $(self.selectors.input).focus();
                            break;
                        case 'error':
                            self.eventSource.close();
                            self.eventSource = null;
                            self.handleError(data);
                            self.isProcessing = false;
                            self.toggleSendButtons();
                            break;
                    }
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.error('Error parsing SSE data:', e);
                }
            };

            this.eventSource.onerror = function() {
                self.eventSource.close();
                self.eventSource = null;
                self.handleError({ error: 'Streaming connection failed' });
                self.isProcessing = false;
                self.toggleSendButtons();
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
