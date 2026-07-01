import './bootstrap';
import Alpine from 'alpinejs';
import axios from 'axios';
import Echo from 'laravel-echo';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.axios = axios;
window.Chart = Chart;

document.addEventListener('alpine:init', () => {
    Alpine.data('app', () => ({
        token: localStorage.getItem('sentinel_token'),
        user: null,
        email: '',
        password: '',
        error: '',
        view: 'dashboard',
        viewParams: {},
        loadingDashboard: false,
        loadingServers: false,
        loadingServerDetail: false,
        loadingServices: false,
        loadingAlerts: false,
        loadingSettings: false,
        dashboard: null,
        servers: [],
        currentServer: null,
        services: [],
        serverMetrics: [],
        serverList: { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 15 } },
        alertList: { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 15 } },
        alerts: [],
        serviceList: { data: [], meta: { current_page: 1, last_page: 1, total: 0 } },
        settings: {},
        alertCount: 0,
        serverForm: { name: '', host: '', port: 22, username: '', auth_type: 'password', auth_key: '', connection_type: '', notes: '', os: '' },
        editingServer: null,
        showServerModal: false,
        saving: false,
        testResult: null,
        testingConnection: false,
        alertFilters: { status: '', severity: '', server_id: '' },
        chartInstance: null,
        chartRange: '1h',
        chartType: 'cpu',
        echo: null,
        echoConnected: false,
        pollingInterval: null,
        serverPage: 1,
        serverSearch: '',
        serverStatusFilter: '',
        alertPage: 1,
        websiteList: { data: [], meta: { current_page: 1, last_page: 1, total: 0 } },
        loadingWebsites: false,
        currentWebsite: null,
        websiteChecks: [],
        serverWebsites: [],
        websitesDown: 0,
        websiteForm: { name: '', url: '', server_id: '', check_interval_seconds: 60, expected_status_code: 200, expected_keyword: '', timeout_seconds: 10, enabled: true },
        editingWebsite: null,
        showWebsiteModal: false,
        savingWebsite: false,
        websiteStatusFilter: '',
        websiteServerFilter: '',
        websitePage: 1,
        websiteRespChart: null,

        ax(path, method = 'get', data = null) {
            const headers = { Authorization: `Bearer ${this.token}` };
            if (method === 'get') return axios.get(path, { headers, params: data });
            if (method === 'delete') return axios.delete(path, { headers });
            if (method === 'put') return axios.put(path, data, { headers });
            return axios.post(path, data, { headers });
        },

        init() {
            if (this.token) {
                this.fetchUser();
                this.showView('dashboard');
            }
            this.pollingInterval = setInterval(() => {
                if (!this.token) return;
                if (this.view === 'dashboard') this.fetchDashboard(false);
                if (this.view === 'alerts') this.fetchAlerts(false);
                if (this.view === 'websites') this.fetchWebsites();
                if (this.view === 'server-detail' && this.currentServer) {
                    this.fetchServices(this.currentServer.id);
                    this.fetchServerWebsites(this.currentServer.id);
                }
            }, 30000);
        },

        async login() {
            try {
                const res = await axios.post('/api/auth/login', { email: this.email, password: this.password });
                this.token = res.data.token;
                this.user = res.data.user;
                localStorage.setItem('sentinel_token', this.token);
                this.error = '';
                this.fetchUser();
                this.showView('dashboard');
                Alpine.nextTick(() => this.connectEcho());
            } catch (e) {
                this.error = e.response?.data?.message || 'Login failed';
            }
        },

        logout() {
            this.ax('/api/auth/logout', 'post').finally(() => {
                this.token = null;
                this.user = null;
                localStorage.removeItem('sentinel_token');
                if (this.echo) { try { this.echo.disconnect(); } catch (_e) {} }
                if (this.pollingInterval) { clearInterval(this.pollingInterval); this.pollingInterval = null; }
            });
        },

        async fetchUser() {
            try {
                const res = await this.ax('/api/auth/me');
                this.user = res.data;
            } catch (e) {
                this.logout();
            }
        },

        showView(name, params = {}) {
            this.view = name;
            this.viewParams = params;
            this.destroyChart();
            if (name === 'dashboard') this.fetchDashboard();
            else if (name === 'servers') this.fetchServers();
            else if (name === 'server-detail') {
                if (params.id) {
                    this.fetchServerDetail(params.id);
                    this.fetchServices(params.id);
                    this.fetchServerWebsites(params.id);
                    Alpine.nextTick(() => this.fetchMetrics(params.id));
                }
            } else if (name === 'alerts') this.fetchAlerts();
            else if (name === 'websites') { this.fetchWebsites(); this.currentWebsite = null; }
            else if (name === 'settings') this.fetchSettings();
        },

        async fetchDashboard(showLoading = true) {
            if (showLoading) this.loadingDashboard = true;
            try {
                const res = await this.ax('/api/dashboard/overview');
                this.dashboard = res.data;
                const serverList = res.data?.servers || [];
                this.alertCount = res.data?.total_alerts_open || 0;
                this.websitesDown = res.data?.websites_down || 0;
                const metricPromises = serverList.map(s =>
                    this.ax(`/api/servers/${s.id}/metrics/latest`).then(r => r.data?.data || []).catch(() => [])
                );
                const allMetrics = await Promise.all(metricPromises);
                this.servers = serverList.map((s, i) => {
                    const metrics = allMetrics[i] || [];
                    const cpu = metrics.find(m => m?.type === 'cpu');
                    const ram = metrics.find(m => m?.type === 'ram');
                    const disk = metrics.find(m => m?.type === 'disk');
                    return { ...s, cpu_percent: cpu?.value ?? 0, ram_percent: ram?.value ?? 0, disk_percent: disk?.value ?? 0 };
                });
            } catch (e) {
                console.error('Dashboard fetch failed', e);
            } finally {
                if (showLoading) this.loadingDashboard = false;
            }
        },

        async fetchServers() {
            this.loadingServers = true;
            try {
                const params = { page: this.serverPage, per_page: 15 };
                if (this.serverSearch) params.search = this.serverSearch;
                if (this.serverStatusFilter) params.status = this.serverStatusFilter;
                const res = await this.ax('/api/servers', 'get', params);
                this.serverList = res.data;
            } catch (e) {
                console.error('Servers fetch failed', e);
            } finally {
                this.loadingServers = false;
            }
        },

        async fetchServerDetail(id) {
            this.loadingServerDetail = true;
            try {
                const res = await this.ax(`/api/servers/${id}`);
                this.currentServer = res.data?.data || res.data;
            } catch (e) {
                console.error('Server detail fetch failed', e);
            } finally {
                this.loadingServerDetail = false;
            }
        },

        async fetchServices(serverId) {
            this.loadingServices = true;
            try {
                const res = await this.ax(`/api/servers/${serverId}/services`);
                this.serviceList = res.data;
                this.services = res.data?.data || [];
            } catch (e) {
                console.error('Services fetch failed', e);
            } finally {
                this.loadingServices = false;
            }
        },

        async fetchAlerts(showLoading = true) {
            if (showLoading) this.loadingAlerts = true;
            try {
                const params = { page: this.alertPage, per_page: 15 };
                if (this.alertFilters.status) params.status = this.alertFilters.status;
                if (this.alertFilters.severity) params.severity = this.alertFilters.severity;
                if (this.alertFilters.server_id) params.server_id = this.alertFilters.server_id;
                const res = await this.ax('/api/alerts', 'get', params);
                this.alertList = res.data;
                this.alerts = res.data?.data || [];
            } catch (e) {
                console.error('Alerts fetch failed', e);
            } finally {
                if (showLoading) this.loadingAlerts = false;
            }
        },

        async fetchSettings() {
            this.loadingSettings = true;
            try {
                const res = await this.ax('/api/settings');
                this.settings = (res.data && res.data.data) || {};
            } catch (e) {
                console.error('Settings fetch failed', e);
            } finally {
                this.loadingSettings = false;
            }
        },

        openServerModal(server = null) {
            if (server) {
                this.editingServer = server;
                this.serverForm = {
                    name: server.name || '',
                    host: server.host || '',
                    port: server.port || 22,
                    username: server.username || '',
                    auth_type: server.auth_type || 'password',
                    auth_key: '',
                    connection_type: server.connection_type || '',
                    notes: server.notes || '',
                    os: server.os || '',
                };
            } else {
                this.editingServer = null;
                this.serverForm = { name: '', host: '', port: 22, username: '', auth_type: 'password', auth_key: '', connection_type: '', notes: '', os: '' };
            }
            this.showServerModal = true;
            this.testResult = null;
        },

        async saveServer() {
            this.saving = true;
            try {
                const payload = { ...this.serverForm };
                if (!payload.auth_key && this.editingServer) delete payload.auth_key;
                if (this.editingServer) {
                    await this.ax(`/api/servers/${this.editingServer.id}`, 'put', payload);
                } else {
                    await this.ax('/api/servers', 'post', payload);
                }
                this.showServerModal = false;
                this.fetchServers();
            } catch (e) {
                const msg = e.response?.data?.message || e.response?.data?.errors || 'Failed to save server';
                alert(typeof msg === 'string' ? msg : Object.values(msg).flat().join('\n'));
            } finally {
                this.saving = false;
            }
        },

        async deleteServer(id) {
            if (!confirm('Delete this server?')) return;
            try {
                await this.ax(`/api/servers/${id}`, 'delete');
                if (this.view === 'servers') this.fetchServers();
                if (this.currentServer?.id === id) { this.view = 'servers'; this.fetchServers(); }
            } catch (e) {
                console.error('Delete failed', e);
            }
        },

        async testConnection(server) {
            this.testingConnection = true;
            this.testResult = null;
            try {
                const res = await this.ax(`/api/servers/${server.id}/test-connection`, 'post');
                this.testResult = { success: true, message: res.data?.message || 'Connection successful' };
            } catch (e) {
                const msg = e.response?.data?.message || 'Connection failed';
                this.testResult = { success: false, message: msg };
            } finally {
                this.testingConnection = false;
            }
        },

        async restartService(serverId, serviceId) {
            try {
                await this.ax(`/api/servers/${serverId}/services/${serviceId}/restart`, 'post');
                this.fetchServices(serverId);
            } catch (e) {
                console.error('Restart failed', e);
            }
        },

        async startService(serverId, serviceId) {
            try {
                await this.ax(`/api/servers/${serverId}/services/${serviceId}/start`, 'post');
                this.fetchServices(serverId);
            } catch (e) {
                console.error('Start failed', e);
            }
        },

        async stopService(serverId, serviceId) {
            try {
                await this.ax(`/api/servers/${serverId}/services/${serviceId}/stop`, 'post');
                this.fetchServices(serverId);
            } catch (e) {
                console.error('Stop failed', e);
            }
        },

        async acknowledgeAlert(id) {
            try {
                await this.ax(`/api/alerts/${id}/acknowledge`, 'post');
                this.fetchAlerts();
            } catch (e) {
                console.error('Acknowledge failed', e);
            }
        },

        async resolveAlert(id) {
            try {
                await this.ax(`/api/alerts/${id}/resolve`, 'post');
                this.fetchAlerts();
            } catch (e) {
                console.error('Resolve failed', e);
            }
        },

        async updateSettings() {
            try {
                await this.ax('/api/settings', 'put', { settings: this.settings });
                alert('Settings updated');
            } catch (e) {
                const msg = e.response?.data?.message || 'Failed to update settings';
                alert(msg);
            }
        },

        async fetchMetrics(serverId) {
            try {
                const res = await this.ax(`/api/servers/${serverId}/metrics/history`, 'get', { type: this.chartType, interval: this.chartRange });
                this.serverMetrics = res.data?.data || [];
                Alpine.nextTick(() => this.renderChart());
            } catch (e) {
                console.error('Metrics fetch failed', e);
            }
        },

        renderChart() {
            this.destroyChart();
            const canvas = document.getElementById('metricsChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            const metrics = this.serverMetrics || [];
            if (metrics.length === 0) return;
            const labels = metrics.map(m => {
                if (!m.recorded_at) return '';
                const d = new Date(m.recorded_at);
                return d.toLocaleTimeString();
            });
            const values = metrics.map(m => m.value ?? 0);
            try {
                this.chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: (this.chartType || 'cpu').toUpperCase(),
                            data: values,
                            borderColor: '#22d3ee',
                            backgroundColor: 'rgba(34, 211, 238, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 2,
                            pointHoverRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 300 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1f2937',
                                titleColor: '#f3f4f6',
                                bodyColor: '#d1d5db',
                                borderColor: '#374151',
                                borderWidth: 1,
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#9ca3af', maxTicksLimit: 10 },
                                grid: { color: 'rgba(75, 85, 99, 0.2)' },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#9ca3af' },
                                grid: { color: 'rgba(75, 85, 99, 0.2)' },
                            }
                        }
                    }
                });
            } catch (e) {
                console.error('Chart render failed', e);
            }
        },

        destroyChart() {
            if (this.chartInstance) {
                try { this.chartInstance.destroy(); } catch (_e) {}
                this.chartInstance = null;
            }
            if (this.websiteRespChart) {
                try { this.websiteRespChart.destroy(); } catch (_e) {}
                this.websiteRespChart = null;
            }
        },

        changeChartType(type) {
            this.chartType = type;
            if (this.currentServer) this.fetchMetrics(this.currentServer.id);
        },

        changeChartRange(range) {
            this.chartRange = range;
            if (this.currentServer) this.fetchMetrics(this.currentServer.id);
        },

        connectEcho() {
            try {
                if (this.echo) {
                    try { this.echo.disconnect(); } catch (_e) {}
                }
                const key = import.meta.env?.VITE_REVERB_APP_KEY;
                if (!key) return;
                this.echo = new Echo({
                    broadcaster: 'reverb',
                    key: key,
                    wsHost: import.meta.env?.VITE_REVERB_HOST || 'localhost',
                    wsPort: Number(import.meta.env?.VITE_REVERB_PORT) || 8080,
                    wssPort: Number(import.meta.env?.VITE_REVERB_PORT) || 443,
                    forceTLS: (import.meta.env?.VITE_REVERB_SCHEME || 'https') === 'https',
                    enabledTransports: ['ws', 'wss'],
                    authorizer: (channel, options) => ({
                        authorize: (socketId, callback) => {
                            axios.post('/api/broadcasting/auth', {
                                socket_id: socketId,
                                channel_name: channel.name,
                            }, {
                                headers: { Authorization: `Bearer ${this.token}` }
                            }).then(response => {
                                callback(false, response.data);
                            }).catch(error => {
                                callback(true, error);
                            });
                        }
                    })
                });
                this.echoConnected = true;
                if (this.echo.connector?.pusher?.connection) {
                    this.echo.connector.pusher.connection.bind('connected', () => { this.echoConnected = true; });
                    this.echo.connector.pusher.connection.bind('disconnected', () => { this.echoConnected = false; });
                }
            } catch (e) {
                console.warn('Echo connection failed', e);
                this.echoConnected = false;
            }
        },

        async fetchWebsites() {
            this.loadingWebsites = true;
            try {
                const params = { page: this.websitePage };
                if (this.websiteStatusFilter) params.status = this.websiteStatusFilter;
                const serverId = this.websiteServerFilter;
                const path = serverId ? `/api/servers/${serverId}/websites` : '/api/servers/1/websites';
                if (serverId) {
                    const res = await this.ax(path, 'get', params);
                    this.websiteList = res.data;
                } else {
                    const servers = this.serverList?.data || this.servers || [];
                    let allData = [];
                    for (const s of servers) {
                        try {
                            const r = await this.ax(`/api/servers/${s.id}/websites`, 'get', params);
                            allData = allData.concat(r.data?.data || []);
                        } catch (_e) {}
                    }
                    this.websiteList = { data: allData, meta: { current_page: 1, last_page: 1, total: allData.length } };
                }
            } catch (e) {
                console.error('Websites fetch failed', e);
            } finally {
                this.loadingWebsites = false;
            }
        },

        async showWebsiteDetail(id) {
            try {
                const res = await this.ax(`/api/websites/${id}`);
                this.currentWebsite = res.data?.data || res.data;
                await this.fetchWebsiteChecks(id);
                this.$nextTick(() => this.renderWebsiteChart());
            } catch (e) {
                console.error('Website detail fetch failed', e);
            }
        },

        async fetchWebsiteChecks(websiteId) {
            try {
                const res = await this.ax(`/api/websites/${websiteId}/history`, 'get', { per_page: 30 });
                this.websiteChecks = res.data?.data || [];
            } catch (e) {
                console.error('Website checks fetch failed', e);
            }
        },

        renderWebsiteChart() {
            if (this.websiteRespChart) { try { this.websiteRespChart.destroy(); } catch (_e) {} }
            const canvas = document.getElementById('websiteRespChart' + this.currentWebsite?.id);
            if (!canvas || !this.currentWebsite) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            const checks = [...(this.websiteChecks || [])].reverse();
            if (checks.length === 0) return;
            const labels = checks.map(c => {
                if (!c.checked_at) return '';
                return new Date(c.checked_at).toLocaleTimeString();
            });
            const values = checks.map(c => c.response_time_ms ?? 0);
            try {
                this.websiteRespChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Response Time (ms)',
                            data: values,
                            borderColor: '#22d3ee',
                            backgroundColor: 'rgba(34, 211, 238, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 300 },
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { color: '#9ca3af', maxTicksLimit: 8 }, grid: { color: 'rgba(75, 85, 99, 0.2)' } },
                            y: { beginAtZero: true, ticks: { color: '#9ca3af' }, grid: { color: 'rgba(75, 85, 99, 0.2)' } },
                        }
                    }
                });
            } catch (e) {
                console.error('Website chart render failed', e);
            }
        },

        openWebsiteModal(website = null) {
            if (website) {
                this.editingWebsite = website;
                this.websiteForm = {
                    name: website.name || '',
                    url: website.url || '',
                    server_id: website.server_id || '',
                    check_interval_seconds: website.check_interval_seconds || 60,
                    expected_status_code: website.expected_status_code || 200,
                    expected_keyword: website.expected_keyword || '',
                    timeout_seconds: website.timeout_seconds || 10,
                    enabled: website.enabled !== false,
                };
            } else {
                this.editingWebsite = null;
                this.websiteForm = { name: '', url: '', server_id: '', check_interval_seconds: 60, expected_status_code: 200, expected_keyword: '', timeout_seconds: 10, enabled: true };
            }
            this.showWebsiteModal = true;
        },

        async saveWebsite() {
            this.savingWebsite = true;
            try {
                const payload = { ...this.websiteForm, enabled: this.websiteForm.enabled ? 1 : 0 };
                const serverId = this.editingWebsite ? this.editingWebsite.server_id : payload.server_id;
                if (this.editingWebsite) {
                    await this.ax(`/api/websites/${this.editingWebsite.id}`, 'put', payload);
                } else {
                    await this.ax(`/api/servers/${serverId}/websites`, 'post', payload);
                }
                this.showWebsiteModal = false;
                this.fetchWebsites();
            } catch (e) {
                const msg = e.response?.data?.message || e.response?.data?.errors || 'Failed to save website';
                alert(typeof msg === 'string' ? msg : Object.values(msg).flat().join('\n'));
            } finally {
                this.savingWebsite = false;
            }
        },

        async deleteWebsite(id) {
            if (!confirm('Delete this website monitor?')) return;
            try {
                await this.ax(`/api/websites/${id}`, 'delete');
                if (this.currentWebsite?.id === id) this.currentWebsite = null;
                this.fetchWebsites();
            } catch (e) {
                console.error('Delete website failed', e);
            }
        },

        async checkWebsite(id) {
            try {
                await this.ax(`/api/websites/${id}/check`, 'post');
                if (this.currentWebsite?.id === id) this.showWebsiteDetail(id);
                this.fetchWebsites();
            } catch (e) {
                console.error('Website check failed', e);
            }
        },

        async fetchServerWebsites(serverId) {
            try {
                const res = await this.ax(`/api/servers/${serverId}/websites`);
                this.serverWebsites = res.data?.data || [];
            } catch (e) {
                this.serverWebsites = [];
            }
        },

        websiteAvgUptime() {
            const websites = this.dashboard?.websites || [];
            if (websites.length === 0) return '--';
            const sum = websites.reduce((acc, w) => acc + (parseFloat(w.last_uptime_percent) || 0), 0);
            return (sum / websites.length).toFixed(1);
        },

        formatDate(date) {
            if (!date) return '';
            try { return new Date(date).toLocaleString(); } catch (_e) { return ''; }
        },

        serverPageChanged(page) {
            this.serverPage = page;
            this.fetchServers();
        },

        alertPageChanged(page) {
            this.alertPage = page;
            this.fetchAlerts();
        },

        applyAlertFilters() {
            this.alertPage = 1;
            this.fetchAlerts();
        },

        resetAlertFilters() {
            this.alertFilters = { status: '', severity: '', server_id: '' };
            this.alertPage = 1;
            this.fetchAlerts();
        },

        searchServers() {
            this.serverPage = 1;
            this.fetchServers();
        },

        get paginationRange() {
            return (meta) => {
                if (!meta) return [];
                const current = meta.current_page || 1;
                const last = meta.last_page || 1;
                const pages = [];
                const start = Math.max(1, current - 2);
                const end = Math.min(last, current + 2);
                for (let i = start; i <= end; i++) pages.push(i);
                return pages;
            };
        },
    }));
});

Alpine.start();
