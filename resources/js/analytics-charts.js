import { Chart, registerables } from 'chart.js';

// Register Chart.js components
Chart.register(...registerables);

class AnalyticsCharts {
    constructor() {
        this.charts = {};
        this.defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        title: function(context) {
                            const date = new Date(context[0].label);
                            return date.toLocaleDateString('en-US', { 
                                weekday: 'long', 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric' 
                            });
                        },
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            
                            // Format numbers based on the metric type
                            if (context.dataset.label.includes('Revenue')) {
                                label += '$' + context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2 });
                            } else if (context.dataset.label.includes('Rate')) {
                                label += context.parsed.y.toFixed(1) + '%';
                            } else {
                                label += context.parsed.y.toLocaleString('en-US');
                            }
                            
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        parser: 'YYYY-MM-DD',
                        tooltipFormat: 'MMM DD, YYYY',
                        displayFormats: {
                            day: 'MMM DD',
                            week: 'MMM DD',
                            month: 'MMM YYYY'
                        }
                    },
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.1)',
                    },
                    ticks: {
                        color: '#6b7280',
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.1)',
                    },
                    ticks: {
                        color: '#6b7280',
                        callback: function(value, index, values) {
                            // Format y-axis labels based on the value range
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return (value / 1000).toFixed(1) + 'K';
                            }
                            return value.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            elements: {
                point: {
                    radius: 4,
                    hoverRadius: 6,
                    borderWidth: 2,
                },
                line: {
                    borderWidth: 3,
                    tension: 0.1
                }
            }
        };
    }

    /**
     * Create a time-series line chart for analytics data
     * @param {string} canvasId - The ID of the canvas element
     * @param {Array} data - Analytics data array
     * @param {Object} options - Chart configuration options
     */
    createTimeSeriesChart(canvasId, data, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas element with ID '${canvasId}' not found`);
            return null;
        }

        // Destroy existing chart if it exists
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        const ctx = canvas.getContext('2d');
        
        // Process data for Chart.js format
        const chartData = this.processAnalyticsData(data);
        
        const config = {
            type: 'line',
            data: chartData,
            options: {
                ...this.defaultOptions,
                ...options,
                scales: {
                    ...this.defaultOptions.scales,
                    ...(options.scales || {})
                }
            }
        };

        this.charts[canvasId] = new Chart(ctx, config);
        return this.charts[canvasId];
    }

    /**
     * Process analytics data into Chart.js format
     * @param {Array} data - Raw analytics data
     * @returns {Object} Chart.js data object
     */
    processAnalyticsData(data) {
        if (!Array.isArray(data) || data.length === 0) {
            return { labels: [], datasets: [] };
        }

        // Extract labels (dates) and sort by date
        const sortedData = data.sort((a, b) => new Date(a.start || a.date) - new Date(b.start || b.date));
        const labels = sortedData.map(item => item.start || item.date);

        // Define dataset configurations
        const datasets = [
            {
                label: 'Clicks',
                data: sortedData.map(item => item.clicks || 0),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: false,
                tension: 0.1
            },
            {
                label: 'Leads',
                data: sortedData.map(item => item.leads || 0),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: false,
                tension: 0.1
            },
            {
                label: 'Sales',
                data: sortedData.map(item => item.sales || 0),
                borderColor: 'rgb(139, 92, 246)',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                fill: false,
                tension: 0.1
            },
            {
                label: 'Revenue',
                data: sortedData.map(item => item.saleAmount || item.revenue || 0),
                borderColor: 'rgb(245, 158, 11)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: false,
                tension: 0.1,
                yAxisID: 'y1'
            }
        ];

        // Filter out datasets with all zero values
        const activeDatasets = datasets.filter(dataset => 
            dataset.data.some(value => value > 0)
        );

        return {
            labels,
            datasets: activeDatasets
        };
    }

    /**
     * Create a metrics comparison chart
     * @param {string} canvasId - The ID of the canvas element
     * @param {Object} metrics - Processed metrics object
     * @param {Array} data - Raw analytics data for trend
     */
    createMetricsChart(canvasId, metrics, data = []) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas element with ID '${canvasId}' not found`);
            return null;
        }

        // Destroy existing chart if it exists
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        const ctx = canvas.getContext('2d');
        
        const config = {
            type: 'doughnut',
            data: {
                labels: ['Clicks', 'Leads', 'Sales'],
                datasets: [{
                    data: [
                        metrics.total_clicks || 0,
                        metrics.total_leads || 0,
                        metrics.total_sales || 0
                    ],
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(139, 92, 246)'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        };

        this.charts[canvasId] = new Chart(ctx, config);
        return this.charts[canvasId];
    }

    /**
     * Update chart data
     * @param {string} canvasId - The ID of the canvas element
     * @param {Array} newData - New analytics data
     */
    updateChart(canvasId, newData) {
        if (!this.charts[canvasId]) {
            console.error(`Chart with ID '${canvasId}' not found`);
            return;
        }

        const chart = this.charts[canvasId];
        const chartData = this.processAnalyticsData(newData);
        
        chart.data = chartData;
        chart.update('active');
    }

    /**
     * Destroy a specific chart
     * @param {string} canvasId - The ID of the canvas element
     */
    destroyChart(canvasId) {
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
            delete this.charts[canvasId];
        }
    }

    /**
     * Destroy all charts
     */
    destroyAllCharts() {
        Object.keys(this.charts).forEach(canvasId => {
            this.destroyChart(canvasId);
        });
    }

    /**
     * Resize all charts
     */
    resizeCharts() {
        Object.values(this.charts).forEach(chart => {
            chart.resize();
        });
    }
}

// Initialize and export
const analyticsCharts = new AnalyticsCharts();

// Handle window resize
window.addEventListener('resize', () => {
    analyticsCharts.resizeCharts();
});

// Export for global access
window.AnalyticsCharts = analyticsCharts;

export default analyticsCharts; 