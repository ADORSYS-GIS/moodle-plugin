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
 * AI Chat widget for embedding in other pages.
 *
 * @module     local_gis_ai_assistant/chat_widget
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    var ChatWidget = {
        
        /**
         * Initialize the chat widget.
         */
        init: function() {
            this.createWidget();
            this.bindEvents();
        },

        /**
         * Create the floating chat widget.
         */
        createWidget: function() {
            var widgetHtml = 
                '<div id="ai-chat-widget" class="ai-chat-widget">' +
                    '<div class="ai-chat-toggle" id="ai-chat-toggle">' +
                        '<i class="fa fa-robot" aria-hidden="true"></i>' +
                    '</div>' +
                    '<div class="ai-chat-popup" id="ai-chat-popup" style="display: none;">' +
                        '<div class="ai-chat-popup-header">' +
                            '<h4>AI Assistant</h4>' +
                            '<button class="ai-chat-close" id="ai-chat-close">' +
                                '<i class="fa fa-times" aria-hidden="true"></i>' +
                            '</button>' +
                        '</div>' +
                        '<div class="ai-chat-popup-body">' +
                            '<div class="ai-chat-messages-mini" id="ai-chat-messages-mini"></div>' +
                            '<div class="ai-chat-input-mini">' +
                                '<form id="ai-chat-form-mini">' +
                                    '<div class="input-group input-group-sm">' +
                                        '<input type="text" class="form-control" id="ai-message-input-mini" placeholder="Ask me anything..." maxlength="500">' +
                                        '<div class="input-group-append">' +
                                            '<button type="submit" class="btn btn-primary">' +
                                                '<i class="fa fa-paper-plane" aria-hidden="true"></i>' +
                                            '</button>' +
                                        '</div>' +
                                    '</div>' +
                                '</form>' +
                            '</div>' +
                        '</div>' +
                        '<div class="ai-chat-popup-footer">' +
                            '<a href="' + M.cfg.wwwroot + '/local/gis_ai_assistant/index.php" class="btn btn-link btn-sm">Open full chat</a>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            $('body').append(widgetHtml);
            this.addWidgetStyles();
        },

        /**
         * Add CSS styles for the widget.
         */
        addWidgetStyles: function() {
            var styles = `
                <style>
                .ai-chat-widget {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 9999;
                }
                
                .ai-chat-toggle {
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    background: #007bff;
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(0,123,255,0.3);
                    transition: all 0.3s ease;
                    font-size: 24px;
                }
                
                .ai-chat-toggle:hover {
                    background: #0056b3;
                    transform: scale(1.1);
                }
                
                .ai-chat-popup {
                    position: absolute;
                    bottom: 70px;
                    right: 0;
                    width: 350px;
                    height: 400px;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                }
                
                .ai-chat-popup-header {
                    background: #007bff;
                    color: white;
                    padding: 12px 16px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .ai-chat-popup-header h4 {
                    margin: 0;
                    font-size: 16px;
                }
                
                .ai-chat-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 0;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .ai-chat-popup-body {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    min-height: 0; /* Allow inner scroller to scroll within flex container */
                    overflow: hidden; /* Prevent content from pushing footer/input out */
                }
                
                .ai-chat-messages-mini {
                    flex: 1 1 auto;
                    overflow-y: auto;
                    min-height: 0; /* Required for scrollable flex child */
                    padding: 1rem;
                    background: #f8f9fa;
                }
                
                .ai-chat-input-mini {
                    padding: 12px;
                    border-top: 1px solid #dee2e6;
                }
                
                .ai-chat-popup-footer {
                    padding: 8px 12px;
                    border-top: 1px solid #dee2e6;
                    text-align: center;
                }
                
                .ai-message-mini {
                    margin-bottom: 8px;
                    padding: 8px 12px;
                    border-radius: 12px;
                    font-size: 14px;
                    line-height: 1.4;
                }
                
                .ai-message-mini.user {
                    background: #007bff;
                    color: white;
                    margin-left: 20px;
                    text-align: right;
                }
                
                .ai-message-mini.ai {
                    background: white;
                    color: #495057;
                    margin-right: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                
                @media (max-width: 768px) {
                    .ai-chat-popup {
                        width: 300px;
                        height: 350px;
                    }
                    
                    .ai-chat-widget {
                        bottom: 15px;
                        right: 15px;
                    }
                }
                </style>
            `;
            
            $('head').append(styles);
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            var self = this;

            // Use delegated events to be resilient to re-initialisation.
            $(document)
                .off('click.aiwidget_toggle')
                .on('click.aiwidget_toggle', '#ai-chat-toggle', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.togglePopup();
                });

            $(document)
                .off('click.aiwidget_close')
                .on('click.aiwidget_close', '#ai-chat-close', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.hidePopup();
                });

            $(document)
                .off('submit.aiwidget_form')
                .on('submit.aiwidget_form', '#ai-chat-form-mini', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.sendMessage();
                });

            // Close on outside click.
            $(document)
                .off('click.aiwidget_outside')
                .on('click.aiwidget_outside', function(e) {
                    if (!$(e.target).closest('.ai-chat-widget').length) {
                        self.hidePopup();
                    }
                });
        },

        /**
         * Toggle chat popup visibility.
         */
        togglePopup: function() {
            var popup = $('#ai-chat-popup');
            if (popup.is(':visible')) {
                this.hidePopup();
            } else {
                this.showPopup();
            }
        },

        /**
         * Show chat popup.
         */
        showPopup: function() {
            $('#ai-chat-popup').fadeIn(200);
            $('#ai-message-input-mini').focus();
        },

        /**
         * Hide chat popup.
         */
        hidePopup: function() {
            $('#ai-chat-popup').fadeOut(200);
        },

        /**
         * Send message to AI.
         */
        sendMessage: function() {
            var self = this;
            var input = $('#ai-message-input-mini');
            var message = input.val().trim();

            if (!message) {
                return;
            }

            // Add user message.
            this.addMessage(message, 'user');
            
            // Clear input.
            input.val('');

            // Show loading.
            this.addMessage('...', 'ai', true);

            // Make API call.
        var request = {
            methodname: 'local_gis_ai_assistant_send_message',
            args: {
                message: message,
                model: '',
                temperature: 0.7,
                max_tokens: 150 // Shorter responses for widget
            }
        };

        Ajax.call([request])[0]
            .done(function(response) {
                // Remove loading message.
                $('.ai-message-mini.loading').remove();
                if (response && response.success) {
                    self.addMessage(response.content, 'ai');
                } else {
                    var err = (response && response.error) ? response.error : 'Unknown error';
                    self.addMessage('Sorry, I encountered an error: ' + err, 'ai');
                }
            })
            .fail(function(error) {
                // Remove loading message.
                $('.ai-message-mini.loading').remove();
                self.addMessage('Sorry, I could not process your request.', 'ai');
                if (window.console) {
                    console.error('AI widget send_message AJAX error:', error);
                }
            });
        },

        /**
         * Add message to mini chat.
         */
        addMessage: function(content, type, isLoading) {
            var container = $('#ai-chat-messages-mini');
            var loadingClass = isLoading ? ' loading' : '';
            
            var messageHtml = '<div class="ai-message-mini ' + type + loadingClass + '">' + 
                              this.escapeHtml(content) + '</div>';
            
            container.append(messageHtml);
            container.scrollTop(container[0].scrollHeight);
        },

        /**
         * Escape HTML to prevent XSS.
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Refresh the Moodle session.
         */
        refreshSession: function() {
            var request = {
                methodname: 'core_session_time_remaining',
                args: {}
            };

            Ajax.call([request])[0]
                .done(function(response) {
                    // Session refreshed successfully.
                })
                .fail(function(error) {
                    // Handle session refresh failure (e.g., redirect to login page).
                    console.error('Failed to refresh session:', error);
                    // Optionally, display a message to the user.
                    // alert('Your session has expired. Please refresh the page.');
                });
        }
    };

    return {
        init: function() {
            ChatWidget.init();
             // Refresh session every 30 minutes.
            setInterval(ChatWidget.refreshSession, 1800000);
        }
    };
});
