import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const emptyForm = () => ({
    name: '',
    group_name: 'Portfolio',
    type: 'website',
    url: '',
    method: 'HEAD',
    expected_keyword: '',
    request_body_template: '{"status":"ok","api":true,"database":true}',
    timeout_seconds: 10,
});

document.addEventListener('alpine:init', () => {
    Alpine.data('statusPage', () => ({
        snapshot: null,
        loading: true,
        saving: false,
        error: '',
        modalOpen: false,
        expandedGroups: {},
        tooltip: {
            visible: false,
            title: '',
            lines: [],
            x: 0,
            y: 0,
        },
        tooltipHideTimer: null,
        form: emptyForm(),
        poller: null,

        init() {
            this.fetchSnapshot();
            this.poller = window.setInterval(() => this.fetchSnapshot(false), 5 * 60 * 1000);
        },

        async fetchSnapshot(showLoading = true) {
            if (showLoading) this.loading = true;
            try {
                const { data } = await window.axios.get('/api/status/snapshot');
                this.snapshot = data;
                this.syncExpandedGroups(data.groups || []);
                this.error = '';
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load status data.';
            } finally {
                if (showLoading) this.loading = false;
            }
        },

        syncExpandedGroups(groups) {
            const next = {};

            groups.forEach((group) => {
                next[group.name] = this.expandedGroups[group.name] ?? false;
            });

            this.expandedGroups = next;
        },

        toggleGroup(name) {
            this.expandedGroups[name] = !this.isExpanded(name);
        },

        isExpanded(name) {
            return this.expandedGroups[name] ?? false;
        },

        sparklineStyle(bars) {
            const count = Array.isArray(bars) && bars.length > 0
                ? bars.length
                : (this.snapshot?.display_window_minutes || 60);

            return `grid-template-columns: repeat(${count}, minmax(0, 1fr));`;
        },

        openModal() {
            this.form = emptyForm();
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
            this.form = emptyForm();
        },

        async saveMonitor() {
            this.saving = true;
            try {
                await window.axios.post('/api/status/targets', this.form);
                this.closeModal();
                this.fetchSnapshot();
            } catch (error) {
                const messages = error.response?.data?.errors;
                this.error = messages
                    ? Object.values(messages).flat().join(' ')
                    : (error.response?.data?.message || 'Failed to save monitor.');
            } finally {
                this.saving = false;
            }
        },

        overallHeadline(status) {
            return {
                operational: "We're fully operational",
                degraded: 'Some systems are experiencing degraded performance',
                down: 'We are experiencing an active outage',
                unknown: 'We are still collecting status data',
            }[status] || 'We are still collecting status data';
        },

        bannerClass(status) {
            return {
                operational: 'status-banner-ok',
                degraded: 'status-banner-warn',
                down: 'status-banner-down',
                unknown: 'status-banner-unknown',
            }[status] || 'status-banner-unknown';
        },

        statusLabel(status) {
            return {
                operational: 'Operational',
                degraded: 'Degraded performance',
                down: 'Major outage',
                unknown: 'No data',
            }[status] || 'No data';
        },

        sparkClass(status) {
            return {
                operational: 'bg-emerald-500',
                degraded: 'bg-amber-400',
                down: 'bg-rose-400',
                unknown: 'bg-slate-200',
            }[status] || 'bg-slate-200';
        },

        statusSymbol(status) {
            return {
                operational: 'check',
                degraded: 'warn',
                down: 'warn',
                unknown: 'dot',
            }[status] || 'dot';
        },

        formatUptime(value) {
            if (value === null || value === undefined) return '--';
            return `${Number(value).toFixed(2)}% uptime`;
        },

        componentLabel(count) {
            return `${count} component${count === 1 ? '' : 's'}`;
        },

        showUrlTooltip(event, group) {
            this.showTooltip(event, group.name, group.tooltip_urls || []);
        },

        showSparkTooltip(event, label, bar) {
            const lines = [this.statusLabel(bar.status)];
            if (bar.label) lines.push(bar.label);
            if (bar.http_status_code) lines.push(`HTTP ${bar.http_status_code}`);
            if (bar.response_time_ms) lines.push(`${bar.response_time_ms} ms`);
            if (bar.error_message) lines.push(bar.error_message);

            this.showTooltip(event, label, lines);
        },

        showTooltip(event, title, lines) {
            if (this.tooltipHideTimer) {
                window.clearTimeout(this.tooltipHideTimer);
                this.tooltipHideTimer = null;
            }

            this.tooltip.title = title;
            this.tooltip.lines = lines.filter(Boolean);
            this.tooltip.visible = true;
            this.moveTooltip(event);
        },

        moveTooltip(event) {
            window.requestAnimationFrame(() => {
                this.positionTooltip(event.clientX, event.clientY);
            });
        },

        positionTooltip(clientX, clientY) {
            const offset = 14;
            const viewportPadding = 12;
            const tooltipElement = document.querySelector('.status-tooltip');
            const tooltipRect = tooltipElement?.getBoundingClientRect();
            const tooltipWidth = tooltipRect?.width ?? 260;
            const tooltipHeight = tooltipRect?.height ?? 72;
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            let x = clientX + offset;
            let y = clientY + offset;

            if (x + tooltipWidth + viewportPadding > viewportWidth) {
                x = Math.max(viewportPadding, viewportWidth - tooltipWidth - viewportPadding);
            }

            if (y + tooltipHeight + viewportPadding > viewportHeight) {
                y = clientY - tooltipHeight - offset;
            }

            if (y < viewportPadding) {
                y = viewportPadding;
            }

            this.tooltip.x = x;
            this.tooltip.y = y;
        },

        hideTooltip() {
            if (this.tooltipHideTimer) {
                window.clearTimeout(this.tooltipHideTimer);
            }

            this.tooltipHideTimer = window.setTimeout(() => {
                this.tooltip.visible = false;
                this.tooltipHideTimer = null;
            }, 70);
        },
    }));
});

Alpine.start();
