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
 * AMD module for AI Analytics dashboard
 * @module local_gis_ai_assistant/analytics
 */
define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/str',
    'core/templates',
    'local_gis_ai_assistant/ui_helpers'
], function($, Ajax, Notification, Str, Templates, UIHelpers) {
    'use strict';

    var AnalyticsUI = {
        selectors: {
            root: '#ai-analytics-root',
            periodSelector: '#analytics-period-selector',
            kpiCards: '.analytics-kpi-cards',
            topUsersTable: '#top-users-table',
            modelUsageTable: '#model-usage-table',
            chartCanvas: '#model-usage-chart',
            retryBtn: '#analytics-retry-btn'
        },
        
        currentPeriod: 'week',
        data: null,
        
        /**
         * Initialize the analytics dashboard
         */
        init: function() {
            // Explicitly reference AnalyticsUI to avoid context issues.
            AnalyticsUI.bindEvents();
            AnalyticsUI.loadAnalytics();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            $(document).on('change', this.selectors.periodSelector, function() {
                self.currentPeriod = $(this).val();
                self.loadAnalytics();
            });

            $(document).on('click', this.selectors.retryBtn, function() {
                self.loadAnalytics();
            });
        },

        /**
         * Load analytics data
         */
        loadAnalytics: function() {
            var self = this;
            
            this.showLoadingState();

            var request = {
                methodname: 'local_gis_ai_assistant_get_analytics',
                args: { period: this.currentPeriod }
            };

            Ajax.call([request])[0]
                .done(function(response) {
                    if (response.success) {
                        self.data = response;
                        self.renderDashboard();
                    } else {
                        self.showErrorState(response.error);
                    }
                })
                .fail(function(error) {
                    self.showErrorState('Failed to load analytics data');
                });
        },

        /**
         * Show loading state
         */
        showLoadingState: function() {
            $(this.selectors.root).addClass('loading');
            $('.analytics-skeleton').show();
            $('.analytics-content').hide();
            $(this.selectors.retryBtn).hide();
        },

        /**
         * Show error state
         * @param {string} errorMessage Error message to display
         */
        showErrorState: function(errorMessage) {
            var self = this;
            
            $(this.selectors.root).removeClass('loading');
            $('.analytics-skeleton').hide();
            $('.analytics-content').hide();
            
            Str.get_string('analytics_error_loading', 'local_gis_ai_assistant').done(function(message) {
                Notification.addNotification({
                    message: message,
                    type: 'error'
                });
            });
            
            $(this.selectors.retryBtn).show();
        },

        /**
         * Render the complete dashboard
         */
        renderDashboard: function() {
            $(this.selectors.root).removeClass('loading');
            $('.analytics-skeleton').hide();
            $('.analytics-content').show();

            if (this.isEmpty()) {
                this.showEmptyState();
                return;
            }

            this.renderKPICards();
            this.renderTopUsersTable();
            this.renderModelUsageTable();
            this.renderModelUsageChart();
        },

        /**
         * Check if data is empty
         * @returns {boolean}
         */
        isEmpty: function() {
            return !this.data || this.data.total_requests === 0;
        },

        /**
         * Show empty state
         */
        showEmptyState: function() {
            var self = this;
            Str.get_string('analytics_empty', 'local_gis_ai_assistant').done(function(message) {
                $(self.selectors.root).find('.analytics-content').html(
                    '<div class="analytics-empty-state">' +
                    '<p>' + message + '</p>' +
                    '</div>'
                );
            });
        },

        /**
         * Render KPI cards
         */
        renderKPICards: function() {
            var self = this;
            var kpiContainer = $(this.selectors.kpiCards);
            
            // Total Requests
            Str.get_string('total_requests', 'local_gis_ai_assistant').done(function(label) {
                var card = self.createKPICard(label, UIHelpers.formatNumber(self.data.total_requests));
                kpiContainer.find('.kpi-requests').html(card);
            });

            // Total Tokens
            Str.get_string('total_tokens', 'local_gis_ai_assistant').done(function(label) {
                var card = self.createKPICard(label, UIHelpers.formatNumber(self.data.total_tokens));
                kpiContainer.find('.kpi-tokens').html(card);
            });

            // Average Response Time
            Str.get_strings([
                { key: 'average_response_time', component: 'local_gis_ai_assistant' },
                { key: 'ms_suffix', component: 'local_gis_ai_assistant', param: Math.round(self.data.average_response_time) }
            ]).done(function(strings) {
                var card = self.createKPICard(strings[0], strings[1]);
                kpiContainer.find('.kpi-response-time').html(card);
            });
        },

        /**
         * Create KPI card HTML
         * @param {string} label Card label
         * @param {string} value Card value
         * @returns {string} HTML string
         */
        createKPICard: function(label, value) {
            return '<div class="analytics-kpi-card">' +
                   '<div class="kpi-value">' + value + '</div>' +
                   '<div class="kpi-label">' + label + '</div>' +
                   '</div>';
        },

        /**
         * Render top users table
         */
        renderTopUsersTable: function() {
            var self = this;
            var tableBody = $(this.selectors.topUsersTable).find('tbody');
            
            if (!this.data.top_users || this.data.top_users.length === 0) {
                Str.get_string('analytics_empty', 'local_gis_ai_assistant').done(function(message) {
                    tableBody.html('<tr><td colspan="2" class="text-center">' + message + '</td></tr>');
                });
                return;
            }

            var rows = '';
            this.data.top_users.forEach(function(user) {
                var fullName = user.firstname + ' ' + user.lastname;
                rows += '<tr>' +
                       '<td>' + fullName + '</td>' +
                       '<td>' + UIHelpers.formatNumber(user.request_count) + '</td>' +
                       '</tr>';
            });
            
            tableBody.html(rows);
        },

        /**
         * Render model usage table
         */
        renderModelUsageTable: function() {
            var self = this;
            var tableBody = $(this.selectors.modelUsageTable).find('tbody');
            
            if (!this.data.model_usage || this.data.model_usage.length === 0) {
                Str.get_string('analytics_empty', 'local_gis_ai_assistant').done(function(message) {
                    tableBody.html('<tr><td colspan="3" class="text-center">' + message + '</td></tr>');
                });
                return;
            }

            var rows = '';
            this.data.model_usage.forEach(function(model) {
                rows += '<tr>' +
                       '<td>' + model.model + '</td>' +
                       '<td>' + UIHelpers.formatNumber(model.request_count) + '</td>' +
                       '<td>' + UIHelpers.formatNumber(model.token_count) + '</td>' +
                       '</tr>';
            });
            
            tableBody.html(rows);
        },

        /**
         * Render model usage chart
         */
        renderModelUsageChart: function() {
            if (!this.data.model_usage || this.data.model_usage.length === 0) {
                return;
            }

            var canvas = $(this.selectors.chartCanvas)[0];
            if (!canvas) return;

            var labels = this.data.model_usage.map(function(item) { return item.model; });
            var values = this.data.model_usage.map(function(item) { return item.request_count; });

            UIHelpers.drawBarChart(canvas, labels, values, {
                title: 'Model Usage (Requests)',
                colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                showTooltips: true
            });
        }
    };

    return {
        init: function() {
            AnalyticsUI.init();
        }
    };
});
