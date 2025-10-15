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
    'local_gis_ai_assistant/ui_helpers',
    'core/chartjs'  // Moodle core Chart.js integration (loads global window.Chart)
], function($, Ajax, Notification, Str, Templates, UIHelpers) {
    'use strict';

    var AnalyticsUI = {
        selectors: {
            root: '#ai-analytics-root',
            periodSelector: '#analytics-period-selector',
            exportBtn: '#analytics-export-btn',
            exportFormat: '#analytics-export-format',
            retryBtn: '#analytics-retry-btn',
            // Containers
            dashboardContent: '.dashboard-content',
            skeleton: '.skeleton-loading',
            kpiGrid: '.kpi-grid',
            // Charts
            usageOverTimeChart: '#usage-over-time',
            modelDistributionPie: '#model-distribution',
            // Tables
            topUsersTable: '#top-users-table tbody'
        },

        currentPeriod: 'week',
        data: null,
        charts: {},  // Store Chart.js instances for destroy/update

        /**
         * Initialize the analytics dashboard
         */
        init: function() {
            // Align currentPeriod with the selector's current value
            var selected = $(this.selectors.periodSelector).val();
            if (selected) {
                this.currentPeriod = selected;
            }
            this.bindEvents();
            this.loadAnalytics();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            $(this.selectors.periodSelector).on('change', function() {
                self.currentPeriod = $(this).val();
                self.loadAnalytics();
            });

            $(this.selectors.exportBtn).on('click', function() {
                self.handleExport();
            });

            $(this.selectors.retryBtn).on('click', function() {
                self.loadAnalytics();
            });
        },

        /**
         * Load analytics data from backend
         */
        loadAnalytics: function() {
            var self = this;
            this.showLoadingState();

            Ajax.call([{
                methodname: 'local_gis_ai_assistant_get_analytics',
                args: { period: this.currentPeriod }
            }])[0]
                .done(function(response) {
                    if (response && response.success) {
                        // Backend returns fields at the top level, not under data
                        self.data = response;
                        self.renderDashboard();
                    } else {
                        self.showErrorState((response && response.error) || 'Unknown error');
                    }
                })
                .fail(function(error) {
                    self.showErrorState('Network error: ' + error.message);
                });
        },

        /**
         * Show loading skeleton
         */
        showLoadingState: function() {
            $(this.selectors.root).addClass('loading');
            $(this.selectors.skeleton).show();
            $(this.selectors.dashboardContent).hide();
            $(this.selectors.retryBtn).hide();
            $(this.selectors.exportBtn).prop('disabled', true);
        },

        /**
         * Show error state with message
         * @param {string} message
         */
        showErrorState: function(message) {
            $(this.selectors.root).removeClass('loading');
            $(this.selectors.skeleton).hide();
            $(this.selectors.dashboardContent).hide();
            Notification.addNotification({ message: message, type: 'error' });
            $(this.selectors.retryBtn).show();
            $(this.selectors.exportBtn).prop('disabled', true);
        },

        /**
         * Render the full dashboard
         */
        renderDashboard: function() {
            $(this.selectors.root).removeClass('loading');
            $(this.selectors.skeleton).hide();
            $(this.selectors.dashboardContent).show();
            $(this.selectors.retryBtn).hide();
            $(this.selectors.exportBtn).prop('disabled', false);

            if (this.isEmptyData()) {
                this.showEmptyState();
                return;
            }

            this.renderKPICards();
            this.renderTables();
            this.renderCharts();
        },

        /**
         * Check if data is empty
         * @returns {boolean}
         */
        isEmptyData: function() {
            return !this.data || !('total_requests' in this.data) || this.data.total_requests === 0;
        },

        /**
         * Show empty state message
         */
        showEmptyState: function() {
            var container = $(this.selectors.dashboardContent);
            Str.get_string('analytics_empty', 'local_gis_ai_assistant').done(function(msg) {
                container.html(`<div class="empty-state" aria-live="assertive">${msg}</div>`).show();
            });
        },

        /**
         * Render KPI cards with trends/icons
         */
        renderKPICards: function() {
            var self = this;
            var kpis = {
                total_requests: this.data.total_requests || 0,
                total_tokens: this.data.total_tokens || 0,
                average_response_time: this.data.average_response_time || 0,
                active_users: (this.data.top_users || []).length || 0
            };
            var $grid = $(this.selectors.kpiGrid);
            if (!$grid.length) { return; }
            $grid.empty();

            var trendUp = '<span class="kpi-trend up">▲</span>';
            var trendDown = '<span class="kpi-trend down">▼</span>';

            // Total Requests
            Str.get_string('total_requests', 'local_gis_ai_assistant').done(function(label) {
                $grid.append(self.createKPICard(label, UIHelpers.formatNumber(kpis.total_requests), '', 'primary'));
            });

            // Total Tokens
            Str.get_string('total_tokens', 'local_gis_ai_assistant').done(function(label) {
                $grid.append(self.createKPICard(label, UIHelpers.formatNumber(kpis.total_tokens), '', 'success'));
            });

            // Avg Response Time
            Str.get_string('avg_response_time', 'local_gis_ai_assistant').done(function(label) {
                var value = UIHelpers.formatTime(kpis.average_response_time);
                $grid.append(self.createKPICard(label, value, '', 'info'));
            });

            // Active Users
            Str.get_string('active_users', 'local_gis_ai_assistant').done(function(label) {
                $grid.append(self.createKPICard(label, UIHelpers.formatNumber(kpis.active_users), '', 'warning'));
            });
        },

        /**
         * Create KPI card HTML
         * @param {string} label
         * @param {string} value
         * @param {string} trend HTML
         * @param {string} colorClass e.g., 'primary'
         * @returns {string}
         */
        createKPICard: function(label, value, trend, colorClass) {
            return `
                <div class="kpi-card bg-${colorClass}" role="presentation">
                    <div class="kpi-value">${value}</div>
                    <div class="kpi-label">${label}</div>
                    <div class="kpi-trend-slot">${trend || ''}</div>
                </div>
            `;
        },

        /**
         * Render tables (top users, model usage)
         */
        renderTables: function() {
            var $tbody = $(this.selectors.topUsersTable);
            if (!$tbody.length) { return; }
            var rows = (this.data.top_users || []).map(function(user) {
                var name = [user.firstname || '', user.lastname || ''].join(' ').trim();
                return `
                <tr>
                    <td>${name || '-'}</td>
                    <td>${UIHelpers.formatNumber(user.request_count || 0)}</td>
                </tr>`;
            }).join('');
            $tbody.html(rows || '<tr><td colspan="2" class="text-center">No data</td></tr>');
        },

        /**
         * Render all charts using Moodle Chart.js
         */
        renderCharts: function() {
            this.destroyCharts();  // Clean up previous instances

            // Usage Over Time (Line Chart)
            var $usageCanvas = $(this.selectors.usageOverTimeChart);
            var $usageCard = $usageCanvas.closest('.chart-card');
            if (this.data.time_series && $usageCanvas.length) {
                var lineData = {
                    labels: this.data.time_series.map(function(d) { return d.date; }),
                    datasets: [{
                        label: 'Requests',
                        data: this.data.time_series.map(function(d) { return d.requests; }),
                        borderColor: '#3B82F6',
                        tension: 0.1
                    }]
                };
                var ctxUsage = $usageCanvas[0].getContext('2d');
                this.charts.usageOverTime = new window.Chart(ctxUsage, {
                    type: 'line',
                    data: lineData,
                    options: this.getChartOptions('Usage Over Time', true)
                });
                $usageCard.show();
            } else if ($usageCard.length) {
                $usageCard.hide();
            }

            // Model Distribution (Pie Chart)
            var $modelCanvas = $(this.selectors.modelDistributionPie);
            var $modelCard = $modelCanvas.closest('.chart-card');
            if (this.data.model_usage && $modelCanvas.length && this.data.model_usage.length) {
                var pieData = {
                    labels: this.data.model_usage.map(function(m) { return m.model; }),
                    datasets: [{
                        data: this.data.model_usage.map(function(m) { return m.request_count; }),
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']
                    }]
                };
                var ctxModel = $modelCanvas[0].getContext('2d');
                this.charts.modelDistribution = new window.Chart(ctxModel, {
                    type: 'pie',
                    data: pieData,
                    options: this.getChartOptions('Model Distribution')
                });
                $modelCard.show();
            } else if ($modelCard.length) {
                $modelCard.hide();
            }
        },

        /**
         * Get common Chart.js options (responsive, tooltips, etc.)
         * @param {string} title
         * @param {boolean} enableZoom
         * @returns {object}
         */
        getChartOptions: function(title, enableZoom = false) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: title },
                    tooltip: { enabled: true },
                    legend: { display: true, position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true }
                },
                animation: { duration: 1000 }
                // Add zoom if enableZoom: plugins: { zoom: { ... } } – requires chartjs-plugin-zoom if not core
            };
        },

        /**
         * Render heatmap using Canvas (simple color grid)
         * @param {array} data 2D array [day, hour, count]
         */
        renderHeatmap: function(data) {
            let canvas = $(this.selectors.userActivityHeatmap)[0];
            let ctx = canvas.getContext('2d');
            let width = canvas.width, height = canvas.height;
            let cellW = width / 24, cellH = height / 7;  // 24 hours, 7 days
            data.forEach(([day, hour, count]) => {
                let color = this.getHeatColor(count);  // e.g., rgba based on max
                ctx.fillStyle = color;
                ctx.fillRect(hour * cellW, day * cellH, cellW, cellH);
            });
            // Add labels/tooltips via mouseover event
            canvas.addEventListener('mousemove', e => {
                // Calculate cell, show tooltip with count
            });
        },

        /**
         * Get color for heatmap value (green to red)
         * @param {number} value
         * @returns {string} rgba
         */
        getHeatColor: function(value) {
            let max = Math.max(...this.data.heatmap.map(d => d[2]));
            let intensity = value / max;
            let r = Math.floor(255 * intensity);
            let g = Math.floor(255 * (1 - intensity));
            return `rgba(${r}, ${g}, 0, 0.7)`;
        },

        /**
         * Handle export (trigger adhoc task)
         */
        handleExport: function() {
            var format = $(this.selectors.exportFormat).val() || 'csv';
            if (!this.data) {
                Notification.addNotification({ message: 'No data to export', type: 'warning' });
                return;
            }
            if (format === 'json') {
                var blob = new Blob([JSON.stringify(this.data, null, 2)], { type: 'application/json' });
                this.downloadBlob('ai-analytics-' + this.currentPeriod + '.json', blob);
                return;
            }

            // CSV export aligned with backend schema
            var rows = [];
            rows.push(['type','period','name_or_model','requests','tokens','avg_response_ms'].join(','));

            // Summary row
            rows.push(['summary', this.data.period || this.currentPeriod, '', (this.data.total_requests||0), (this.data.total_tokens||0), (this.data.average_response_time||0)].join(','));

            // Model usage
            (this.data.model_usage || []).forEach(function(m) {
                rows.push(['model', '', m.model, (m.request_count||0), (m.token_count||0), ''].join(','));
            });

            // Top users
            (this.data.top_users || []).forEach(function(u) {
                var name = [u.firstname||'', u.lastname||''].join(' ').trim();
                rows.push(['user', '', name, (u.request_count||0), '', ''].join(','));
            });

            var csv = rows.join('\n');
            var blobcsv = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            this.downloadBlob('ai-analytics-' + this.currentPeriod + '.csv', blobcsv);
        },

        /**
         * Download a blob as a file
         * @param {string} filename
         * @param {Blob} blob
         */
        downloadBlob: function(filename, blob) {
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        /**
         * Destroy existing charts for re-render
         */
        destroyCharts: function() {
            Object.values(this.charts).forEach(chart => chart.destroy());
            this.charts = {};
        }
    };

    return {
        init: function() { AnalyticsUI.init(); }
    };
});