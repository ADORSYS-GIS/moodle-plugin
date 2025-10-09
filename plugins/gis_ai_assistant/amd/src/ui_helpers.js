/**
 * UI Helpers for AI Assistant
 * @module local_gis_ai_assistant/ui_helpers
 */
define(['jquery'], function($) {
    'use strict';

    return {
        /**
         * Show loading bubble in messages container
         * @param {jQuery} container Messages container
         */
        showLoadingBubble: function(container) {
            var loadingBubble = $('<div class="ai-message ai-message-bubble ai-loading-bubble ai-ai-message">' +
                                '<div class="ai-message-avatar ai-avatar"></div>' +
                                '<div class="ai-message-content">' +
                                '<div class="ai-typing-dots">' +
                                '<span></span><span></span><span></span>' +
                                '</div>' +
                                '</div>' +
                                '</div>');
            container.append(loadingBubble);
        },

        /**
         * Remove loading bubbles from container
         * @param {jQuery} container Messages container
         */
        removeLoadingBubbles: function(container) {
            container.find('.ai-loading-bubble').remove();
        },

        /**
         * Create a message bubble
         * @param {string} content Message content
         * @param {string} type Message type (user|ai|system)
         * @returns {jQuery} Message bubble element
         */
        createMessageBubble: function(content, type) {
            var avatarClass = type === 'user' ? 'user-avatar' : 'ai-avatar';
            var bubbleClass = 'ai-message ai-message-bubble ai-' + type + '-message';
            
            var bubble = $('<div class="' + bubbleClass + '">' +
                          '<div class="ai-message-avatar ' + avatarClass + '"></div>' +
                          '<div class="ai-message-content">' + this.escapeHtml(content) + '</div>' +
                          '</div>');

            // Add timestamp
            var timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            bubble.find('.ai-message-content').append(
                '<div class="ai-message-timestamp">' + timestamp + '</div>'
            );

            return bubble;
        },

        /**
         * Create a message bubble from already formatted/safe HTML
         * The provided HTML is expected to be sanitized on the server (format_text with noclean=false)
         * @param {string} contentHtml HTML content
         * @param {string} type Message type (user|ai|system)
         * @returns {jQuery} Message bubble element
         */
        createMessageBubbleHtml: function(contentHtml, type) {
            var avatarClass = type === 'user' ? 'user-avatar' : 'ai-avatar';
            var bubbleClass = 'ai-message ai-message-bubble ai-' + type + '-message';

            var bubble = $('<div class="' + bubbleClass + '">' +
                          '<div class="ai-message-avatar ' + avatarClass + '"></div>' +
                          '<div class="ai-message-content"></div>' +
                          '</div>');

            bubble.find('.ai-message-content').html(contentHtml);

            var timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            bubble.find('.ai-message-content').append(
                '<div class="ai-message-timestamp">' + timestamp + '</div>'
            );

            return bubble;
        },

        /**
         * Format number with proper separators
         * @param {number} num Number to format
         * @returns {string} Formatted number
         */
        formatNumber: function(num) {
            if (typeof num !== 'number') return '0';
            return num.toLocaleString();
        },

        /**
         * Draw a simple bar chart on canvas
         * @param {HTMLCanvasElement} canvas Canvas element
         * @param {Array} labels Chart labels
         * @param {Array} values Chart values
         * @param {Object} options Chart options
         */
        drawBarChart: function(canvas, labels, values, options) {
            var ctx = canvas.getContext('2d');
            var opts = Object.assign({
                title: '',
                colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                showTooltips: false,
                padding: 40
            }, options);

            // Set canvas size
            var dpr = window.devicePixelRatio || 1;
            var rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);

            var width = rect.width;
            var height = rect.height;
            var chartWidth = width - (opts.padding * 2);
            var chartHeight = height - (opts.padding * 2) - 40; // Extra space for title

            // Clear canvas
            ctx.clearRect(0, 0, width, height);

            if (values.length === 0) return;

            var maxValue = Math.max.apply(Math, values);
            if (maxValue === 0) maxValue = 1;

            var barWidth = chartWidth / values.length * 0.8;
            var barSpacing = chartWidth / values.length * 0.2;

            // Draw title
            if (opts.title) {
                ctx.fillStyle = '#374151';
                ctx.font = '14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(opts.title, width / 2, 20);
            }

            // Draw bars
            values.forEach(function(value, index) {
                var barHeight = (value / maxValue) * chartHeight;
                var x = opts.padding + (index * (barWidth + barSpacing)) + (barSpacing / 2);
                var y = opts.padding + 30 + (chartHeight - barHeight);

                // Draw bar
                ctx.fillStyle = opts.colors[index % opts.colors.length];
                ctx.fillRect(x, y, barWidth, barHeight);

                // Draw value on top of bar
                ctx.fillStyle = '#374151';
                ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(value.toString(), x + barWidth / 2, y - 5);

                // Draw label
                ctx.fillStyle = '#6B7280';
                ctx.font = '11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                ctx.textAlign = 'center';
                
                // Truncate long labels
                var label = labels[index];
                if (label.length > 10) {
                    label = label.substring(0, 10) + '...';
                }
                
                ctx.fillText(label, x + barWidth / 2, opts.padding + 30 + chartHeight + 15);
            });

            // Add tooltip functionality if enabled
            if (opts.showTooltips) {
                this.addChartTooltips(canvas, labels, values, {
                    barWidth: barWidth,
                    barSpacing: barSpacing,
                    padding: opts.padding,
                    chartHeight: chartHeight
                });
            }
        },

        /**
         * Add tooltip functionality to chart
         * @param {HTMLCanvasElement} canvas Canvas element
         * @param {Array} labels Chart labels
         * @param {Array} values Chart values
         * @param {Object} layout Layout parameters
         */
        addChartTooltips: function(canvas, labels, values, layout) {
            var $canvas = $(canvas);
            var tooltip = $('<div class="chart-tooltip"></div>');
            $('body').append(tooltip);

            $canvas.on('mousemove', function(e) {
                var rect = canvas.getBoundingClientRect();
                var x = e.clientX - rect.left;
                var y = e.clientY - rect.top;

                // Check if mouse is over a bar
                var barIndex = -1;
                for (var i = 0; i < values.length; i++) {
                    var barX = layout.padding + (i * (layout.barWidth + layout.barSpacing)) + (layout.barSpacing / 2);
                    if (x >= barX && x <= barX + layout.barWidth) {
                        barIndex = i;
                        break;
                    }
                }

                if (barIndex >= 0) {
                    var content = '<strong>' + labels[barIndex] + '</strong><br>' +
                                'Requests: ' + values[barIndex];
                    tooltip.html(content).show();
                    tooltip.css({
                        left: e.pageX + 10,
                        top: e.pageY - 10
                    });
                    $canvas.css('cursor', 'pointer');
                } else {
                    tooltip.hide();
                    $canvas.css('cursor', 'default');
                }
            });

            $canvas.on('mouseleave', function() {
                tooltip.hide();
                $canvas.css('cursor', 'default');
            });
        },

        /**
         * Escape HTML entities
         * @param {string} text Text to escape
         * @returns {string} Escaped text
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        
    };
});
