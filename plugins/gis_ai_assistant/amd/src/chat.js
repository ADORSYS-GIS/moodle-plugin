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

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    'use strict';

    var Chat = {
        
        /**
         * Initialize the chat interface.
         */
        init: function() {
            this.bindEvents();
            this.autoResizeTextarea();
            this.focusInput();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            var self = this;

            // Send message on form submit.
            $('#ai-chat-form').on('submit', function(e) {
                e.preventDefault();
                self.sendMessage();
            });

            // Send message on Ctrl+Enter.
            $('#ai-message-input').on('keydown', function(e) {
                if (e.ctrlKey && e.keyCode === 13) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Clear chat.
            $('#ai-clear-chat').on('click', function() {
                self.clearChat();
            });

            // Send streaming message on stream button click.
            $('#ai-send-stream-button').on('click', function() {
                self.sendStreamingMessage();
            });

            // Auto-resize textarea.
            $('#ai-message-input').on('input', function() {
                self.autoResizeTextarea();
            });
        },

        /**
         * Send message to AI.
         */
        sendMessage: function() {
            var self = this;
            var input = $('#ai-message-input');
            var message = input.val().trim();

            if (!message) {
                return;
            }

            // Disable input and show loading.
            this.setLoading(true);
            
            // Add user message to chat.
            this.addMessage(message, 'user');
            
            // Clear input.
            input.val('');
            this.autoResizeTextarea();

            // Show typing indicator.
            this.showTypingIndicator();

            // Make API call.
            var request = {
                methodname: 'local_gis_ai_assistant_send_message',
                args: {
                    message: message,
                    model: '',
                    temperature: 0.7,
                    max_tokens: 0
                }
            };

            Ajax.call([request])[0]
                .done(function(response) {
                    self.hideTypingIndicator();
                    
                    if (response.success) {
                        self.addMessage(response.content, 'ai');
                        self.logUsage(response.usage);
                    } else {
                        self.showError(response.error || 'Unknown error occurred');
                    }
                })
                .fail(function(error) {
                    self.hideTypingIndicator();
                    var msg = (error && (error.message || error.error || error.debuginfo || error.exception)) || 'Unknown error';
                    console.error('AI send_message AJAX error:', error);
                    self.showError('Failed to send message: ' + msg);
                })
                .always(function() {
                    self.setLoading(false);
                });
        },

        /**
         * Send streaming message to AI.
         */
        sendStreamingMessage: function() {
            var self = this;
            var input = $('#ai-message-input');
            var message = input.val().trim();

            if (!message) {
                return;
            }

            // Disable input and show loading.
            this.setLoading(true);
            
            // Add user message to chat.
            this.addMessage(message, 'user');
            
            // Clear input.
            input.val('');
            this.autoResizeTextarea();

            // Show typing indicator.
            this.showTypingIndicator();

            // Make API call to get stream session.
            var request = {
                methodname: 'local_gis_ai_assistant_send_message_stream',
                args: {
                    message: message,
                    model: '',
                    temperature: 0.7,
                    max_tokens: 0
                }
            };

            Ajax.call([request])[0]
                .done(function(response) {
                    if (response.success) {
                        self.handleStreamingResponse(response.session_id);
                    } else {
                        self.hideTypingIndicator();
                        self.showError(response.error || 'Unknown error occurred');
                        self.setLoading(false);
                    }
                })
                .fail(function(error) {
                    self.hideTypingIndicator();
                    var msg = (error && (error.message || error.error || error.debuginfo || error.exception)) || 'Unknown error';
                    console.error('AI send_message_stream AJAX error:', error);
                    self.showError('Failed to start streaming: ' + msg);
                    self.setLoading(false);
                });
        },

        /**
         * Handle streaming response.
         */
        handleStreamingResponse: function(sessionId) {
            var self = this;
            
            // Hide typing indicator and add AI message container.
            this.hideTypingIndicator();
            var messageElement = this.addMessage('', 'ai');
            var contentElement = messageElement.find('.ai-message-content p');

            // Create EventSource for streaming.
            var streamUrl = M.cfg.wwwroot + '/local/gis_ai_assistant/stream.php?session=' + sessionId;
            var eventSource = new EventSource(streamUrl);

            eventSource.onmessage = function(event) {
                var data;
                try {
                    data = JSON.parse(event.data);
                } catch (e) {
                    console.error('Invalid SSE payload:', event.data);
                    return;
                }
                
                if (data.type === 'content') {
                    // Append content to message.
                    var currentContent = contentElement.text();
                    contentElement.text(currentContent + data.content);
                    self.scrollToBottom();
                } else if (data.type === 'done') {
                    // Stream finished.
                    eventSource.close();
                    self.setLoading(false);
                    if (data.usage) {
                        self.logUsage(data.usage);
                    }
                } else if (data.type === 'error') {
                    // Error occurred.
                    eventSource.close();
                    self.showError(data.error);
                    self.setLoading(false);
                }
            };

            eventSource.onerror = function() {
                eventSource.close();
                self.showError('Streaming connection failed');
                self.setLoading(false);
            };

            // Close stream after timeout.
            setTimeout(function() {
                if (eventSource.readyState !== EventSource.CLOSED) {
                    eventSource.close();
                    self.setLoading(false);
                }
            }, 120000); // 2 minutes timeout.
        },

        /**
         * Add message to chat.
         */
        addMessage: function(content, type) {
            var messagesContainer = $('#ai-chat-messages');
            var avatar = type === 'user' ? 
                '<i class="fa fa-user" aria-hidden="true"></i>' : 
                '<i class="fa fa-robot" aria-hidden="true"></i>';

            var messageHtml = 
                '<div class="ai-message ai-' + type + '-message">' +
                    '<div class="ai-message-avatar">' + avatar + '</div>' +
                    '<div class="ai-message-content">' +
                        '<p>' + this.escapeHtml(content) + '</p>' +
                    '</div>' +
                '</div>';

            var messageElement = $(messageHtml);
            messagesContainer.append(messageElement);
            this.scrollToBottom();
            
            return messageElement;
        },

        /**
         * Show typing indicator.
         */
        showTypingIndicator: function() {
            $('#ai-typing-indicator').show();
            this.scrollToBottom();
        },

        /**
         * Hide typing indicator.
         */
        hideTypingIndicator: function() {
            $('#ai-typing-indicator').hide();
        },

        /**
         * Show error message.
         */
        showError: function(message) {
            this.addMessage('Error: ' + message, 'ai');
            Notification.addNotification({
                message: message,
                type: 'error'
            });
        },

        /**
         * Clear chat messages.
         */
        clearChat: function() {
            var self = this;
            
            Str.get_string('confirm_clear_chat', 'local_gis_ai_assistant').done(function(confirmText) {
                if (confirm(confirmText)) {
                    $('#ai-chat-messages').empty();
                    
                    // Add welcome message back.
                    Str.get_string('ai_welcome', 'local_gis_ai_assistant').done(function(welcomeText) {
                        var welcomeHtml = 
                            '<div class="ai-message ai-system-message">' +
                                '<div class="ai-message-avatar">' +
                                    '<i class="fa fa-robot" aria-hidden="true"></i>' +
                                '</div>' +
                                '<div class="ai-message-content">' +
                                    '<p>' + welcomeText + '</p>' +
                                '</div>' +
                            '</div>';
                        $('#ai-chat-messages').append(welcomeHtml);
                    });
                    
                    self.focusInput();
                }
            });
        },

        /**
         * Set loading state.
         */
        setLoading: function(loading) {
            var input = $('#ai-message-input');
            var button = $('#ai-send-button');

            if (loading) {
                input.prop('disabled', true);
                button.prop('disabled', true);
                button.html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
            } else {
                input.prop('disabled', false);
                button.prop('disabled', false);
                button.html('<i class="fa fa-paper-plane" aria-hidden="true"></i>');
                this.focusInput();
            }
        },

        /**
         * Auto-resize textarea.
         */
        autoResizeTextarea: function() {
            var textarea = $('#ai-message-input')[0];
            if (textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
            }
        },

        /**
         * Focus input field.
         */
        focusInput: function() {
            setTimeout(function() {
                $('#ai-message-input').focus();
            }, 100);
        },

        /**
         * Scroll to bottom of messages.
         */
        scrollToBottom: function() {
            var container = $('#ai-chat-messages');
            container.scrollTop(container[0].scrollHeight);
        },

        /**
         * Log token usage for analytics.
         */
        logUsage: function(usage) {
            if (usage && usage.total_tokens) {
                console.log('AI Usage:', usage);
                // Could send analytics data here if needed.
            }
        },

        /**
         * Escape HTML to prevent XSS.
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    return {
        init: function() {
            Chat.init();
        }
    };
});
