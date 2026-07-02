<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Status Page') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important;}</style>
</head>
<body class="bg-[#fafafa] text-slate-900 antialiased">
    <div class="status-shell min-h-screen" x-data="statusPage()">
        <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            <template x-if="error">
                <section class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" x-text="error"></section>
            </template>

            <template x-if="loading">
                <section class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500">Loading status board...</section>
            </template>

            <template x-if="!loading && (snapshot?.groups || []).length === 0">
                <section class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-8 text-center text-sm text-slate-600">No monitors yet. Add the first target to start monitoring.</section>
            </template>

            <div class="space-y-5" x-cloak>
                <section class="status-banner" :class="bannerClass(snapshot?.overall_status)">
                    <div class="flex items-center gap-3">
                        <span class="status-icon h-5 w-5" :class="snapshot?.overall_status === 'operational' ? 'status-icon-ok' : snapshot?.overall_status === 'degraded' ? 'status-icon-warn-solid' : snapshot?.overall_status === 'down' ? 'status-icon-down' : 'status-icon-unknown'">
                            <svg x-show="statusSymbol(snapshot?.overall_status) === 'check'" viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3.5 8.5 6.5 11.5 12.5 4.5" />
                            </svg>
                            <svg x-show="statusSymbol(snapshot?.overall_status) === 'warn'" viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 2.5 14 13H2L8 2.5Z" />
                                <path d="M8 6.2v3.1" />
                                <path d="M8 11.4h.01" />
                            </svg>
                            <span x-show="statusSymbol(snapshot?.overall_status) === 'dot'" class="h-2 w-2 rounded-full bg-current"></span>
                        </span>
                        <div>
                            <p class="text-[15px] font-semibold" x-text="overallHeadline(snapshot?.overall_status)"></p>
                            <p class="mt-1 text-sm opacity-80" x-text="snapshot?.overall_message || ''"></p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="flex items-center gap-3">
                            <h2 class="text-[15px] font-semibold text-slate-950">System status</h2>
                        </div>
                        <button type="button" @click="openModal" class="w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:text-slate-900 sm:w-auto">Add target</button>
                    </div>

                    <div class="divide-y divide-slate-200">
                        <template x-for="group in (snapshot?.groups || [])" :key="group.name">
                            <div class="status-group px-4 py-4 sm:px-5" :class="isExpanded(group.name) ? 'status-group-expanded' : 'status-group-collapsed'">
                                <button
                                    type="button"
                                    @click="toggleGroup(group.name)"
                                    :aria-expanded="isExpanded(group.name)"
                                    class="flex w-full flex-col gap-3 text-left sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                                >
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="status-icon" :class="group.status === 'operational' ? 'status-icon-ok' : group.status === 'degraded' ? 'status-icon-warn-solid' : group.status === 'down' ? 'status-icon-down' : 'status-icon-unknown'">
                                            <svg x-show="statusSymbol(group.status) === 'check'" viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3.5 8.5 6.5 11.5 12.5 4.5" />
                                            </svg>
                                            <svg x-show="statusSymbol(group.status) === 'warn'" viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M8 2.5 14 13H2L8 2.5Z" />
                                                <path d="M8 6.2v3.1" />
                                                <path d="M8 11.4h.01" />
                                            </svg>
                                            <span x-show="statusSymbol(group.status) === 'dot'" class="h-2 w-2 rounded-full bg-current"></span>
                                        </span>

                                        <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                            <h3 class="truncate text-[15px] font-medium text-slate-950" x-text="group.name"></h3>
                                            <span class="status-info"
                                                @mouseenter="showUrlTooltip($event, group)"
                                                @mousemove="moveTooltip($event)"
                                                @mouseleave="hideTooltip()">i</span>
                                            <span class="text-sm text-slate-500" x-text="componentLabel(group.component_count)"></span>
                                            <svg viewBox="0 0 16 16" class="h-4 w-4 text-slate-400 transition duration-150" :class="isExpanded(group.name) ? 'rotate-180 text-slate-600' : 'text-slate-400'" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M4 6.5 8 10.5 12 6.5" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="w-full text-sm font-medium text-slate-400 sm:w-auto sm:shrink-0 sm:text-right" x-text="formatUptime(group.uptime_percent)"></div>
                                </button>

                                <div class="mt-3 overflow-hidden">
                                    <div class="status-sparkline" :style="sparklineStyle(group.spark_bars)">
                                        <template x-for="(bar, index) in group.spark_bars" :key="index">
                                            <div class="status-sparkline-bar"
                                                :class="sparkClass(bar.status)"
                                                @mouseenter="showSparkTooltip($event, group.name, bar)"
                                                @mousemove="moveTooltip($event)"
                                                @mouseleave="hideTooltip()"></div>
                                        </template>
                                    </div>
                                </div>

                                <div x-show="isExpanded(group.name)" class="status-group-details mt-4 border-t border-slate-200 pt-4">
                                    <div class="space-y-4 pl-3 sm:pl-4">
                                        <template x-for="monitor in group.monitors" :key="monitor.id">
                                            <div class="status-component">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                                    <div class="flex min-w-0 items-center gap-3">
                                                        <span class="status-icon" :class="monitor.last_status === 'operational' ? 'status-icon-ok' : monitor.last_status === 'degraded' ? 'status-icon-warn-solid' : monitor.last_status === 'down' ? 'status-icon-down' : 'status-icon-unknown'">
                                                            <svg x-show="statusSymbol(monitor.last_status) === 'check'" viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M3.5 8.5 6.5 11.5 12.5 4.5" />
                                                            </svg>
                                                            <svg x-show="statusSymbol(monitor.last_status) === 'warn'" viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M8 2.5 14 13H2L8 2.5Z" />
                                                                <path d="M8 6.2v3.1" />
                                                                <path d="M8 11.4h.01" />
                                                            </svg>
                                                            <span x-show="statusSymbol(monitor.last_status) === 'dot'" class="h-2 w-2 rounded-full bg-current"></span>
                                                        </span>
                                                        <div class="min-w-0">
                                                            <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                                                <p class="truncate text-[14px] font-medium text-slate-950" x-text="monitor.name"></p>
                                                                <span class="status-info"
                                                                    @mouseenter="showTooltip($event, monitor.name, [monitor.url])"
                                                                    @mousemove="moveTooltip($event)"
                                                                    @mouseleave="hideTooltip()">i</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="w-full text-sm font-medium text-slate-400 sm:w-auto sm:shrink-0 sm:text-right" x-text="formatUptime(monitor.last_uptime_percent)"></div>
                                                </div>

                                                <div class="mt-2 overflow-hidden">
                                                    <div class="status-sparkline" :style="sparklineStyle(monitor.spark_bars)">
                                                        <template x-for="(bar, index) in monitor.spark_bars" :key="index">
                                                            <div class="status-sparkline-bar"
                                                                :class="sparkClass(bar.status)"
                                                                @mouseenter="showSparkTooltip($event, monitor.name, bar)"
                                                                @mousemove="moveTooltip($event)"
                                                                @mouseleave="hideTooltip()"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </div>
        </main>

        <div x-show="tooltip.visible" x-cloak x-transition:enter="status-tooltip-enter" x-transition:enter-start="status-tooltip-enter-start" x-transition:enter-end="status-tooltip-enter-end" x-transition:leave="status-tooltip-leave" x-transition:leave-start="status-tooltip-leave-start" x-transition:leave-end="status-tooltip-leave-end" class="status-tooltip" :style="`left:${tooltip.x}px; top:${tooltip.y}px;`">
            <p class="status-tooltip-title" x-text="tooltip.title"></p>
            <template x-for="(line, index) in tooltip.lines" :key="`${tooltip.title}-${index}`">
                <p class="status-tooltip-line" x-text="line"></p>
            </template>
        </div>

        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/35 px-4 py-8" @click.self="closeModal">
            <div class="w-full max-w-2xl rounded-lg border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-3 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">New monitor</p>
                        <h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-950">Add new target</h2>
                    </div>
                    <button type="button" @click="closeModal" class="rounded-full border border-slate-200 px-3 py-1 text-sm font-medium text-slate-600 transition hover:border-slate-300">Close</button>
                </div>

                <form class="space-y-3" @submit.prevent="saveMonitor">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700">Name</span>
                            <input x-model="form.name" type="text" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-950">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700">Group</span>
                            <input x-model="form.group_name" type="text" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-950">
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-slate-700">URL</span>
                        <input x-model="form.url" type="url" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-950" placeholder="https://portfolio.example.com/health">
                    </label>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700">Type</span>
                            <select x-model="form.type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-950">
                                <option value="website">Website</option>
                                <option value="api">API</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700">Check method</span>
                            <select x-model="form.method" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-950">
                                <option value="HEAD">HEAD only</option>
                                <option value="GET">GET health check</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-slate-700">Timeout</span>
                            <input x-model="form.timeout_seconds" type="number" min="1" max="20" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-950">
                        </label>
                    </div>

                    <label class="block" x-show="form.method === 'GET'">
                        <span class="mb-1 block text-sm font-medium text-slate-700">Expected keyword</span>
                        <input x-model="form.expected_keyword" type="text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-950" placeholder="ok">
                    </label>

                    <label class="block" x-show="form.type === 'api' && form.method === 'GET'">
                        <span class="mb-1 block text-sm font-medium text-slate-700">Example health response</span>
                        <textarea x-model="form.request_body_template" rows="4" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-950" placeholder='{"status":"ok","api":true,"database":true}'></textarea>
                        <p class="mt-1 text-xs text-slate-500">Optional. The checker will recognize the JSON fields `status`, `api`, and `database` when the endpoint returns them.</p>
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" @click="closeModal" class="rounded-full border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-slate-400">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-full bg-slate-950 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save monitor'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
