<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>
<body class="bg-gray-950 text-gray-100 font-sans antialiased">
    <div id="app" x-data="app()">
        <template x-if="!token">
            <div class="min-h-screen flex items-center justify-center">
                <div class="w-full max-w-md p-8">
                    <h1 class="text-2xl font-bold text-center mb-8 text-cyan-400">Sentinel Monitor</h1>
                    <form @submit.prevent="login" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                            <input type="email" x-model="email" class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">Password</label>
                            <input type="password" x-model="password" class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none text-gray-100">
                        </div>
                        <p x-text="error" class="text-red-400 text-sm" x-show="error"></p>
                        <button type="submit" class="w-full py-2 px-4 bg-cyan-600 hover:bg-cyan-500 rounded-lg font-medium transition-colors">Sign In</button>
                    </form>
                </div>
            </div>
        </template>

        <template x-if="token">
            <div class="min-h-screen flex">
                <aside class="w-64 bg-gray-900 border-r border-gray-800 p-4 flex flex-col shrink-0">
                    <h2 class="text-lg font-bold text-cyan-400 mb-6">Sentinel</h2>
                    <nav class="space-y-1 flex-1">
                        <a href="#" @click.prevent="showView('dashboard')" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" :class="view === 'dashboard' ? 'bg-gray-800 text-cyan-400' : 'text-gray-400 hover:bg-gray-800'">
                            <span>Dashboard</span>
                        </a>
                        <a href="#" @click.prevent="showView('servers')" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" :class="view === 'servers' ? 'bg-gray-800 text-cyan-400' : 'text-gray-400 hover:bg-gray-800'">
                            <span>Servers</span>
                        </a>
                        <a href="#" @click.prevent="showView('websites')" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" :class="view === 'websites' ? 'bg-gray-800 text-cyan-400' : 'text-gray-400 hover:bg-gray-800'">
                            <span>Websites</span>
                            <span x-show="websitesDown > 0" x-text="websitesDown" class="ml-auto bg-red-600 text-white text-xs px-2 py-0.5 rounded-full"></span>
                        </a>
                        <a href="#" @click.prevent="showView('alerts')" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" :class="view === 'alerts' ? 'bg-gray-800 text-cyan-400' : 'text-gray-400 hover:bg-gray-800'">
                            <span>Alerts</span>
                            <span x-show="alertCount > 0" x-text="alertCount" class="ml-auto bg-red-600 text-white text-xs px-2 py-0.5 rounded-full"></span>
                        </a>
                        <a href="#" @click.prevent="showView('settings')" x-show="user?.role === 'superadmin' || user?.role === 'admin'" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors" :class="view === 'settings' ? 'bg-gray-800 text-cyan-400' : 'text-gray-400 hover:bg-gray-800'">
                            <span>Settings</span>
                        </a>
                    </nav>
                    <div class="pt-4 border-t border-gray-800">
                        <p class="text-sm text-gray-400" x-text="user?.name || ''"></p>
                        <p class="text-xs text-gray-600" x-text="user?.role || ''"></p>
                        <button @click="logout" class="mt-2 text-sm text-red-400 hover:text-red-300">Logout</button>
                    </div>
                </aside>

                <main class="flex-1 p-6 overflow-auto">
                    <div x-show="view === 'dashboard'" x-cloak>
                        <div x-show="loadingDashboard" class="text-gray-400 py-4">Loading dashboard...</div>
                        <template x-if="!loadingDashboard && dashboard">
                            <div>
                                <h1 class="text-2xl font-bold mb-6">Dashboard</h1>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Servers</p>
                                        <p class="text-2xl font-bold mt-1" x-text="dashboard?.total_servers || 0"></p>
                                    </div>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Online</p>
                                        <p class="text-2xl font-bold mt-1 text-green-400" x-text="dashboard?.online_servers || 0"></p>
                                    </div>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Offline</p>
                                        <p class="text-2xl font-bold mt-1 text-red-400" x-text="dashboard?.offline_servers || 0"></p>
                                    </div>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Health Score</p>
                                        <p class="text-2xl font-bold mt-1 text-cyan-400" x-text="(dashboard?.average_health_score || 0) + '%'"></p>
                                    </div>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Open Alerts</p>
                                        <p class="text-2xl font-bold mt-1 text-yellow-400" x-text="dashboard?.total_alerts_open || 0"></p>
                                    </div>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Critical</p>
                                        <p class="text-2xl font-bold mt-1 text-red-400" x-text="dashboard?.total_alerts_critical || 0"></p>
                                    </div>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Warning</p>
                                        <p class="text-2xl font-bold mt-1 text-yellow-500" x-text="dashboard?.total_alerts_warning || 0"></p>
                                    </div>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Websites</p>
                                        <p class="text-2xl font-bold mt-1 text-cyan-400"><span x-text="dashboard?.websites_up || 0" class="text-green-400"></span>/<span x-text="dashboard?.total_websites || 0"></span></p>
                                    </div>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Web Uptime</p>
                                        <p class="text-2xl font-bold mt-1 text-green-400" x-text="websiteAvgUptime() + '%'"></p>
                                    </div>
                                </div>

                                <h2 class="text-lg font-semibold mb-4 text-gray-300">Server Status</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <template x-for="server in servers" :key="server.id">
                                        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 cursor-pointer hover:border-gray-700 transition-colors" @click="showView('server-detail', {id: server.id})">
                                            <div class="flex items-center justify-between mb-3">
                                                <h3 class="font-semibold truncate" x-text="server.name || ''"></h3>
                                                <span class="text-xs px-2 py-1 rounded-full shrink-0" :class="server.status === 'online' ? 'bg-green-900 text-green-400' : server.status === 'offline' ? 'bg-red-900 text-red-400' : 'bg-gray-800 text-gray-400'" x-text="server.status || 'unknown'"></span>
                                            </div>
                                            <div class="mb-2">
                                                <div class="flex justify-between text-xs text-gray-400 mb-1">
                                                    <span>Health</span>
                                                    <span x-text="(server.health_score || 0) + '%'"></span>
                                                </div>
                                                <div class="h-2 bg-gray-800 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all duration-500" :style="'width: ' + (server.health_score || 0) + '%'" :class="(server.health_score || 0) > 80 ? 'bg-cyan-500' : (server.health_score || 0) > 50 ? 'bg-yellow-500' : 'bg-red-500'"></div>
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <div>
                                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                                        <span>CPU</span>
                                                        <span x-text="(server.cpu_percent || 0).toFixed(1) + '%'"></span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full transition-all duration-500" :style="'width: ' + (server.cpu_percent || 0) + '%'" :class="(server.cpu_percent || 0) > 90 ? 'bg-red-500' : (server.cpu_percent || 0) > 70 ? 'bg-yellow-500' : 'bg-cyan-500'"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                                        <span>RAM</span>
                                                        <span x-text="(server.ram_percent || 0).toFixed(1) + '%'"></span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full transition-all duration-500" :style="'width: ' + (server.ram_percent || 0) + '%'" :class="(server.ram_percent || 0) > 90 ? 'bg-red-500' : (server.ram_percent || 0) > 70 ? 'bg-yellow-500' : 'bg-cyan-500'"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                                        <span>Disk</span>
                                                        <span x-text="(server.disk_percent || 0).toFixed(1) + '%'"></span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full transition-all duration-500" :style="'width: ' + (server.disk_percent || 0) + '%'" :class="(server.disk_percent || 0) > 85 ? 'bg-red-500' : (server.disk_percent || 0) > 70 ? 'bg-yellow-500' : 'bg-cyan-500'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <p x-show="servers.length === 0" class="text-gray-500 text-center py-8">No servers configured yet. Add one in Servers.</p>

                                <template x-if="(dashboard?.websites || []).length > 0">
                                    <div class="mt-8">
                                        <h2 class="text-lg font-semibold mb-4 text-gray-300">Website Status</h2>
                                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                            <template x-for="w in (dashboard?.websites || [])" :key="w.id">
                                                <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 cursor-pointer hover:border-gray-700 transition-colors" @click="showView('websites'); setTimeout(() => showWebsiteDetail(w.id), 100)">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <h3 class="font-semibold text-sm truncate" x-text="w.name || ''"></h3>
                                                        <span class="text-xs px-2 py-0.5 rounded-full shrink-0" :class="w.last_status === 'up' ? 'bg-green-900 text-green-400' : w.last_status === 'down' ? 'bg-red-900 text-red-400' : 'bg-yellow-900 text-yellow-400'" x-text="w.last_status || 'unknown'"></span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mb-2 truncate" x-text="w.url || ''"></p>
                                                    <div class="flex items-center gap-3 text-xs text-gray-400 mb-2">
                                                        <span x-text="'HTTP ' + (w.last_http_code || '--')"></span>
                                                        <span x-text="(w.last_response_ms || '--') + 'ms'"></span>
                                                    </div>
                                                    <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full" :style="'width: ' + (w.last_uptime_percent || 0) + '%'" :class="(w.last_uptime_percent || 0) > 99 ? 'bg-green-500' : (w.last_uptime_percent || 0) > 95 ? 'bg-yellow-500' : 'bg-red-500'"></div>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mt-1" x-text="(w.last_uptime_percent || 0) + '% uptime'"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div x-show="view === 'servers'" x-cloak>
                        <div class="flex items-center justify-between mb-6">
                            <h1 class="text-2xl font-bold">Servers</h1>
                            <button @click="openServerModal()" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 rounded-lg text-sm font-medium transition-colors">+ Add Server</button>
                        </div>

                        <div class="flex gap-3 mb-4">
                            <input type="text" x-model="serverSearch" @keyup.enter="searchServers" placeholder="Search servers..." class="px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:border-cyan-500 outline-none text-gray-100 w-64">
                            <select x-model="serverStatusFilter" @change="searchServers" class="px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:border-cyan-500 outline-none text-gray-100">
                                <option value="">All Status</option>
                                <option value="online">Online</option>
                                <option value="offline">Offline</option>
                                <option value="unknown">Unknown</option>
                            </select>
                        </div>

                        <div x-show="loadingServers" class="text-gray-400 py-4">Loading servers...</div>

                        <template x-if="!loadingServers">
                            <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-800 text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="text-left px-4 py-3">Name</th>
                                            <th class="text-left px-4 py-3">Host</th>
                                            <th class="text-left px-4 py-3">Port</th>
                                            <th class="text-left px-4 py-3">Status</th>
                                            <th class="text-left px-4 py-3">OS</th>
                                            <th class="text-left px-4 py-3">Health</th>
                                            <th class="text-left px-4 py-3">Services</th>
                                            <th class="text-left px-4 py-3">Last Checked</th>
                                            <th class="text-right px-4 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="server in (serverList?.data || [])" :key="server.id">
                                            <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                                                <td class="px-4 py-3">
                                                    <a href="#" @click.prevent="showView('server-detail', {id: server.id})" class="text-cyan-400 hover:text-cyan-300 font-medium" x-text="server.name || ''"></a>
                                                </td>
                                                <td class="px-4 py-3 text-gray-400" x-text="server.host || ''"></td>
                                                <td class="px-4 py-3 text-gray-400" x-text="server.port || ''"></td>
                                                <td class="px-4 py-3">
                                                    <span class="text-xs px-2 py-0.5 rounded-full" :class="server.status === 'online' ? 'bg-green-900 text-green-400' : server.status === 'offline' ? 'bg-red-900 text-red-400' : 'bg-gray-800 text-gray-400'" x-text="server.status || 'unknown'"></span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-400" x-text="server.os || '-'"></td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <div class="h-1.5 w-16 bg-gray-800 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full" :style="'width: ' + (server.health_score || 0) + '%'" :class="(server.health_score || 0) > 80 ? 'bg-cyan-500' : (server.health_score || 0) > 50 ? 'bg-yellow-500' : 'bg-red-500'"></div>
                                                        </div>
                                                        <span class="text-xs" x-text="(server.health_score || 0) + '%'"></span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-gray-400" x-text="server.services_count || 0"></td>
                                                <td class="px-4 py-3 text-gray-500 text-xs" x-text="formatDate(server.last_checked_at) || '-'"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <button @click="openServerModal(server)" class="px-2 py-1 text-xs bg-gray-800 hover:bg-gray-700 rounded transition-colors">Edit</button>
                                                        <button @click="testConnection(server); $el.nextElementSibling.classList.toggle('hidden')" class="px-2 py-1 text-xs bg-gray-800 hover:bg-gray-700 rounded transition-colors">Test</button>
                                                        <button @click="deleteServer(server.id)" class="px-2 py-1 text-xs bg-red-900/50 hover:bg-red-800 text-red-400 rounded transition-colors">Del</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                <p x-show="(serverList?.data || []).length === 0" class="text-gray-500 text-center py-8">No servers found.</p>
                            </div>
                        </template>

                        <div x-show="(serverList?.meta?.last_page || 1) > 1" class="flex items-center justify-center gap-2 mt-4">
                            <button @click="serverPageChanged((serverList?.meta?.current_page || 1) - 1)" :disabled="(serverList?.meta?.current_page || 1) <= 1" class="px-3 py-1 text-sm bg-gray-800 rounded disabled:opacity-50 hover:bg-gray-700 transition-colors">Prev</button>
                            <template x-for="p in (paginationRange(serverList?.meta) || [])" :key="p">
                                <button @click="serverPageChanged(p)" class="px-3 py-1 text-sm rounded transition-colors" :class="p === (serverList?.meta?.current_page || 1) ? 'bg-cyan-600 text-white' : 'bg-gray-800 hover:bg-gray-700'">
                                    <span x-text="p"></span>
                                </button>
                            </template>
                            <button @click="serverPageChanged((serverList?.meta?.current_page || 1) + 1)" :disabled="(serverList?.meta?.current_page || 1) >= (serverList?.meta?.last_page || 1)" class="px-3 py-1 text-sm bg-gray-800 rounded disabled:opacity-50 hover:bg-gray-700 transition-colors">Next</button>
                        </div>

                        <div x-show="testResult" class="mt-4 px-4 py-3 rounded-lg text-sm" :class="testResult?.success ? 'bg-green-900/50 text-green-400 border border-green-800' : 'bg-red-900/50 text-red-400 border border-red-800'" x-text="testResult?.message || ''"></div>
                        <div x-show="testingConnection" class="mt-4 text-gray-400 text-sm">Testing connection...</div>

                        <div x-show="showServerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showServerModal = false">
                            <div class="bg-gray-900 border border-gray-800 rounded-lg w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                                <div class="p-6">
                                    <h2 class="text-lg font-semibold mb-4" x-text="editingServer ? 'Edit Server' : 'Add Server'"></h2>
                                    <form @submit.prevent="saveServer" class="space-y-3">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Name</label>
                                                <input type="text" x-model="serverForm.name" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Host</label>
                                                <input type="text" x-model="serverForm.host" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Port</label>
                                                <input type="number" x-model="serverForm.port" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Username</label>
                                                <input type="text" x-model="serverForm.username" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Auth Type</label>
                                                <select x-model="serverForm.auth_type" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                                    <option value="password">Password</option>
                                                    <option value="key">Key</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">OS (optional)</label>
                                                <input type="text" x-model="serverForm.os" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">Auth Key / Password</label>
                                            <input type="password" x-model="serverForm.auth_key" :required="!editingServer" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">Connection Type (optional)</label>
                                            <input type="text" x-model="serverForm.connection_type" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">Notes (optional)</label>
                                            <textarea x-model="serverForm.notes" rows="2" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100"></textarea>
                                        </div>
                                        <div class="flex justify-end gap-2 pt-2">
                                            <button type="button" @click="showServerModal = false" class="px-4 py-2 text-sm bg-gray-800 hover:bg-gray-700 rounded transition-colors">Cancel</button>
                                            <button type="submit" :disabled="saving" class="px-4 py-2 text-sm bg-cyan-600 hover:bg-cyan-500 rounded font-medium transition-colors disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save'"></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="view === 'server-detail'" x-cloak>
                        <button @click="showView('servers')" class="text-sm text-gray-400 hover:text-gray-300 mb-4">&larr; Back to Servers</button>
                        <div x-show="loadingServerDetail" class="text-gray-400 py-4">Loading server details...</div>
                        <template x-if="!loadingServerDetail && currentServer">
                            <div>
                                <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 mb-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h1 class="text-2xl font-bold" x-text="currentServer?.name || ''"></h1>
                                            <p class="text-gray-400 text-sm mt-1">
                                                <span x-text="currentServer?.host || ''"></span>:<span x-text="currentServer?.port || ''"></span>
                                                <span class="mx-2 text-gray-600">|</span>
                                                <span x-text="currentServer?.os || 'Unknown OS'"></span>
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs px-3 py-1 rounded-full" :class="currentServer?.status === 'online' ? 'bg-green-900 text-green-400' : currentServer?.status === 'offline' ? 'bg-red-900 text-red-400' : 'bg-gray-800 text-gray-400'" x-text="currentServer?.status || 'unknown'"></span>
                                            <span class="text-sm" :class="(currentServer?.health_score || 0) > 80 ? 'text-cyan-400' : (currentServer?.health_score || 0) > 50 ? 'text-yellow-400' : 'text-red-400'">
                                                Health: <span x-text="(currentServer?.health_score || 0) + '%'"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="h-2 bg-gray-800 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all" :style="'width: ' + (currentServer?.health_score || 0) + '%'" :class="(currentServer?.health_score || 0) > 80 ? 'bg-cyan-500' : (currentServer?.health_score || 0) > 50 ? 'bg-yellow-500' : 'bg-red-500'"></div>
                                    </div>
                                    <div class="flex gap-4 mt-4 text-xs text-gray-500">
                                        <span>Last checked: <span x-text="formatDate(currentServer?.last_checked_at) || 'Never'"></span></span>
                                        <span>Username: <span x-text="currentServer?.username || ''"></span></span>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <h2 class="text-lg font-semibold mb-4 text-gray-300">Services</h2>
                                    <div x-show="loadingServices" class="text-gray-400 py-2 text-sm">Loading services...</div>
                                    <template x-if="!loadingServices">
                                        <div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                                <template x-for="service in (services || [])" :key="service.id">
                                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <h3 class="font-medium text-sm" x-text="service.name || ''"></h3>
                                                            <span class="text-xs px-2 py-0.5 rounded-full" :class="service.status === 'running' || service.status === 'active' ? 'bg-green-900 text-green-400' : service.status === 'stopped' || service.status === 'inactive' ? 'bg-red-900 text-red-400' : 'bg-gray-800 text-gray-400'" x-text="service.status || 'unknown'"></span>
                                                        </div>
                                                        <p class="text-xs text-gray-500 mb-1" x-text="service.type || ''"></p>
                                                        <p x-show="service.current_output" class="text-xs text-gray-600 truncate" x-text="service.current_output || ''"></p>
                                                        <div class="flex gap-1 mt-3">
                                                            <button @click="startService(currentServer?.id, service.id)" class="px-2 py-1 text-xs bg-green-900/50 hover:bg-green-800 text-green-400 rounded transition-colors">Start</button>
                                                            <button @click="stopService(currentServer?.id, service.id)" class="px-2 py-1 text-xs bg-red-900/50 hover:bg-red-800 text-red-400 rounded transition-colors">Stop</button>
                                                            <button @click="restartService(currentServer?.id, service.id)" class="px-2 py-1 text-xs bg-yellow-900/50 hover:bg-yellow-800 text-yellow-400 rounded transition-colors">Restart</button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <p x-show="(services || []).length === 0" class="text-gray-500 text-sm py-4">No services found for this server.</p>
                                        </div>
                                    </template>
                                </div>

                                <div class="mb-6" x-show="(serverWebsites || []).length > 0">
                                    <h2 class="text-lg font-semibold mb-4 text-gray-300">Websites</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                        <template x-for="w in (serverWebsites || [])" :key="w.id">
                                            <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 cursor-pointer hover:border-gray-700 transition-colors" @click="showView('websites'); setTimeout(() => showWebsiteDetail(w.id), 100)">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h3 class="font-medium text-sm truncate" x-text="w.name || ''"></h3>
                                                    <span class="text-xs px-2 py-0.5 rounded-full shrink-0" :class="w.last_status === 'up' ? 'bg-green-900 text-green-400' : w.last_status === 'down' ? 'bg-red-900 text-red-400' : 'bg-yellow-900 text-yellow-400'" x-text="w.last_status || 'unknown'"></span>
                                                </div>
                                                <p class="text-xs text-gray-500 truncate mb-1" x-text="w.url || ''"></p>
                                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                                    <span x-text="'HTTP ' + (w.last_http_code || '--')"></span>
                                                    <span x-text="(w.last_response_ms || '--') + 'ms'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div>
                                    <h2 class="text-lg font-semibold mb-4 text-gray-300">Metrics</h2>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="flex gap-1">
                                                <button @click="changeChartType('cpu')" class="px-3 py-1 text-xs rounded transition-colors" :class="chartType === 'cpu' ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">CPU</button>
                                                <button @click="changeChartType('ram')" class="px-3 py-1 text-xs rounded transition-colors" :class="chartType === 'ram' ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">RAM</button>
                                                <button @click="changeChartType('disk')" class="px-3 py-1 text-xs rounded transition-colors" :class="chartType === 'disk' ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">Disk</button>
                                            </div>
                                            <div class="flex gap-1">
                                                <button @click="changeChartRange('1h')" class="px-3 py-1 text-xs rounded transition-colors" :class="chartRange === '1h' ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">1H</button>
                                                <button @click="changeChartRange('1d')" class="px-3 py-1 text-xs rounded transition-colors" :class="chartRange === '1d' ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">24H</button>
                                                <button @click="changeChartRange('7d')" class="px-3 py-1 text-xs rounded transition-colors" :class="chartRange === '7d' ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'">7D</button>
                                            </div>
                                        </div>
                                        <div class="relative h-64">
                                            <canvas id="metricsChart"></canvas>
                                            <p x-show="(serverMetrics || []).length === 0" class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm">No metric data available</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="view === 'alerts'" x-cloak>
                        <h1 class="text-2xl font-bold mb-6">Alerts</h1>

                        <div class="flex flex-wrap gap-3 mb-4 items-end">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Status</label>
                                <select x-model="alertFilters.status" class="px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:border-cyan-500 outline-none text-gray-100">
                                    <option value="">All</option>
                                    <option value="open">Open</option>
                                    <option value="acknowledged">Acknowledged</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Severity</label>
                                <select x-model="alertFilters.severity" class="px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:border-cyan-500 outline-none text-gray-100">
                                    <option value="">All</option>
                                    <option value="critical">Critical</option>
                                    <option value="warning">Warning</option>
                                    <option value="info">Info</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Server ID</label>
                                <input type="number" x-model="alertFilters.server_id" placeholder="Server ID" class="w-24 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:border-cyan-500 outline-none text-gray-100">
                            </div>
                            <button @click="applyAlertFilters" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 rounded-lg text-sm transition-colors">Apply</button>
                            <button @click="resetAlertFilters" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm transition-colors">Reset</button>
                        </div>

                        <div x-show="loadingAlerts" class="text-gray-400 py-4">Loading alerts...</div>

                        <template x-if="!loadingAlerts">
                            <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-800 text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="text-left px-4 py-3">Title</th>
                                            <th class="text-left px-4 py-3">Server</th>
                                            <th class="text-left px-4 py-3">Severity</th>
                                            <th class="text-left px-4 py-3">Status</th>
                                            <th class="text-left px-4 py-3">Triggered</th>
                                            <th class="text-right px-4 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="alert in (alerts || [])" :key="alert.id">
                                            <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                                                <td class="px-4 py-3">
                                                    <span class="font-medium" x-text="alert.title || ''"></span>
                                                    <p x-show="alert.message" class="text-xs text-gray-500 mt-0.5" x-text="alert.message || ''"></p>
                                                </td>
                                                <td class="px-4 py-3 text-gray-400" x-text="'#' + (alert.server_id || '')"></td>
                                                <td class="px-4 py-3">
                                                    <span class="text-xs px-2 py-0.5 rounded-full" :class="alert.severity === 'critical' ? 'bg-red-900 text-red-400' : alert.severity === 'warning' ? 'bg-yellow-900 text-yellow-400' : 'bg-blue-900 text-blue-400'" x-text="alert.severity || 'info'"></span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-xs" :class="alert.status === 'open' ? 'text-red-400' : alert.status === 'acknowledged' ? 'text-yellow-400' : 'text-green-400'" x-text="alert.status || ''"></span>
                                                </td>
                                                <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(alert.triggered_at || alert.created_at) || '-'"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <button x-show="alert.status === 'open'" @click="acknowledgeAlert(alert.id)" class="px-2 py-1 text-xs bg-yellow-900/50 hover:bg-yellow-800 text-yellow-400 rounded transition-colors">Acknowledge</button>
                                                        <button x-show="alert.status === 'open' || alert.status === 'acknowledged'" @click="resolveAlert(alert.id)" class="px-2 py-1 text-xs bg-green-900/50 hover:bg-green-800 text-green-400 rounded transition-colors">Resolve</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                <p x-show="(alerts || []).length === 0" class="text-gray-500 text-center py-8">No alerts found.</p>
                            </div>
                        </template>

                        <div x-show="(alertList?.meta?.last_page || 1) > 1" class="flex items-center justify-center gap-2 mt-4">
                            <button @click="alertPageChanged((alertList?.meta?.current_page || 1) - 1)" :disabled="(alertList?.meta?.current_page || 1) <= 1" class="px-3 py-1 text-sm bg-gray-800 rounded disabled:opacity-50 hover:bg-gray-700 transition-colors">Prev</button>
                            <template x-for="p in (paginationRange(alertList?.meta) || [])" :key="p">
                                <button @click="alertPageChanged(p)" class="px-3 py-1 text-sm rounded transition-colors" :class="p === (alertList?.meta?.current_page || 1) ? 'bg-cyan-600 text-white' : 'bg-gray-800 hover:bg-gray-700'">
                                    <span x-text="p"></span>
                                </button>
                            </template>
                            <button @click="alertPageChanged((alertList?.meta?.current_page || 1) + 1)" :disabled="(alertList?.meta?.current_page || 1) >= (alertList?.meta?.last_page || 1)" class="px-3 py-1 text-sm bg-gray-800 rounded disabled:opacity-50 hover:bg-gray-700 transition-colors">Next</button>
                        </div>
                    </div>

                    <div x-show="view === 'websites'" x-cloak>
                        <div class="flex items-center justify-between mb-6">
                            <h1 class="text-2xl font-bold">Websites</h1>
                            <button @click="openWebsiteModal()" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 rounded-lg text-sm font-medium transition-colors">+ Add Website</button>
                        </div>

                        <div class="flex gap-3 mb-4">
                            <select x-model="websiteStatusFilter" @change="fetchWebsites()" class="px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:border-cyan-500 outline-none text-gray-100">
                                <option value="">All Status</option>
                                <option value="up">Up</option>
                                <option value="down">Down</option>
                                <option value="degraded">Degraded</option>
                            </select>
                            <select x-model="websiteServerFilter" @change="fetchWebsites()" class="px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:border-cyan-500 outline-none text-gray-100">
                                <option value="">All Servers</option>
                                <template x-for="s in (serverList?.data || servers || [])" :key="s.id">
                                    <option :value="s.id" x-text="s.name || ''"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="loadingWebsites" class="text-gray-400 py-4">Loading websites...</div>

                        <template x-if="!loadingWebsites && !currentWebsite">
                            <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-800 text-gray-500 text-xs uppercase tracking-wide">
                                            <th class="text-left px-4 py-3">Name</th>
                                            <th class="text-left px-4 py-3">URL</th>
                                            <th class="text-left px-4 py-3">Server</th>
                                            <th class="text-left px-4 py-3">Status</th>
                                            <th class="text-left px-4 py-3">HTTP</th>
                                            <th class="text-left px-4 py-3">Response</th>
                                            <th class="text-left px-4 py-3">Uptime</th>
                                            <th class="text-left px-4 py-3">Last Check</th>
                                            <th class="text-right px-4 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="w in (websiteList?.data || [])" :key="w.id">
                                            <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                                                <td class="px-4 py-3">
                                                    <a href="#" @click.prevent="showWebsiteDetail(w.id)" class="text-cyan-400 hover:text-cyan-300 font-medium" x-text="w.name || ''"></a>
                                                </td>
                                                <td class="px-4 py-3 text-gray-400 text-xs truncate max-w-[200px]" x-text="w.url || ''"></td>
                                                <td class="px-4 py-3 text-gray-400 text-xs" x-text="w.server_name || '#' + w.server_id"></td>
                                                <td class="px-4 py-3">
                                                    <span class="text-xs px-2 py-0.5 rounded-full" :class="w.last_status === 'up' ? 'bg-green-900 text-green-400' : w.last_status === 'down' ? 'bg-red-900 text-red-400' : 'bg-yellow-900 text-yellow-400'" x-text="w.last_status || 'unknown'"></span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-400 text-xs" x-text="w.last_http_code || '--'"></td>
                                                <td class="px-4 py-3 text-gray-400 text-xs" x-text="(w.last_response_ms || '--') + 'ms'"></td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-1">
                                                        <div class="h-1.5 w-12 bg-gray-800 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full" :style="'width: ' + (w.last_uptime_percent || 0) + '%'" :class="(w.last_uptime_percent || 0) > 99 ? 'bg-green-500' : (w.last_uptime_percent || 0) > 95 ? 'bg-yellow-500' : 'bg-red-500'"></div>
                                                        </div>
                                                        <span class="text-xs text-gray-500" x-text="(w.last_uptime_percent || 0) + '%'"></span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-gray-500 text-xs" x-text="formatDate(w.last_checked_at) || '-'"></td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <button @click="checkWebsite(w.id)" class="px-2 py-1 text-xs bg-gray-800 hover:bg-gray-700 rounded transition-colors">Check</button>
                                                        <button @click="openWebsiteModal(w)" class="px-2 py-1 text-xs bg-gray-800 hover:bg-gray-700 rounded transition-colors">Edit</button>
                                                        <button @click="deleteWebsite(w.id)" class="px-2 py-1 text-xs bg-red-900/50 hover:bg-red-800 text-red-400 rounded transition-colors">Del</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                <p x-show="(websiteList?.data || []).length === 0" class="text-gray-500 text-center py-8">No websites found. Add one to start monitoring.</p>
                            </div>
                        </template>

                        <template x-if="currentWebsite">
                            <div>
                                <button @click="currentWebsite = null" class="text-sm text-gray-400 hover:text-gray-300 mb-4">&larr; Back to Websites</button>
                                <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 mb-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h1 class="text-xl font-bold" x-text="currentWebsite?.name || ''"></h1>
                                            <p class="text-gray-400 text-sm mt-1" x-text="currentWebsite?.url || ''"></p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs px-3 py-1 rounded-full" :class="currentWebsite?.last_status === 'up' ? 'bg-green-900 text-green-400' : currentWebsite?.last_status === 'down' ? 'bg-red-900 text-red-400' : 'bg-yellow-900 text-yellow-400'" x-text="currentWebsite?.last_status || 'unknown'"></span>
                                            <button @click="checkWebsite(currentWebsite?.id)" class="px-3 py-1.5 text-xs bg-cyan-600 hover:bg-cyan-500 rounded transition-colors">Check Now</button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                        <div class="bg-gray-800 rounded-lg p-3 text-center">
                                            <p class="text-xs text-gray-500 mb-1">HTTP Status</p>
                                            <p class="text-lg font-bold" :class="currentWebsite?.last_http_code === currentWebsite?.expected_status_code ? 'text-green-400' : 'text-red-400'" x-text="currentWebsite?.last_http_code || '--'"></p>
                                        </div>
                                        <div class="bg-gray-800 rounded-lg p-3 text-center">
                                            <p class="text-xs text-gray-500 mb-1">Response Time</p>
                                            <p class="text-lg font-bold text-cyan-400" x-text="(currentWebsite?.last_response_ms || '--') + 'ms'"></p>
                                        </div>
                                        <div class="bg-gray-800 rounded-lg p-3 text-center">
                                            <p class="text-xs text-gray-500 mb-1">Uptime (30d)</p>
                                            <p class="text-lg font-bold" :class="(currentWebsite?.last_uptime_percent || 0) > 99 ? 'text-green-400' : 'text-yellow-400'" x-text="(currentWebsite?.last_uptime_percent || 0) + '%'"></p>
                                        </div>
                                        <div class="bg-gray-800 rounded-lg p-3 text-center">
                                            <p class="text-xs text-gray-500 mb-1">Last Checked</p>
                                            <p class="text-sm text-gray-300" x-text="formatDate(currentWebsite?.last_checked_at) || 'Never'"></p>
                                        </div>
                                    </div>
                                    <div class="h-2 bg-gray-800 rounded-full overflow-hidden mb-6">
                                        <div class="h-full rounded-full" :style="'width: ' + (currentWebsite?.last_uptime_percent || 0) + '%'" :class="(currentWebsite?.last_uptime_percent || 0) > 99 ? 'bg-green-500' : (currentWebsite?.last_uptime_percent || 0) > 95 ? 'bg-yellow-500' : 'bg-red-500'"></div>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <h2 class="text-lg font-semibold mb-4 text-gray-300">Response Time</h2>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
                                        <div class="relative h-48">
                                            <canvas :id="'websiteRespChart' + currentWebsite?.id"></canvas>
                                            <p x-show="(websiteChecks || []).length === 0" class="absolute inset-0 flex items-center justify-center text-gray-500 text-sm">No check data yet</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h2 class="text-lg font-semibold mb-4 text-gray-300">Recent Checks</h2>
                                    <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-b border-gray-800 text-gray-500 text-xs uppercase tracking-wide">
                                                    <th class="text-left px-4 py-3">Time</th>
                                                    <th class="text-left px-4 py-3">HTTP</th>
                                                    <th class="text-left px-4 py-3">Response</th>
                                                    <th class="text-left px-4 py-3">Status</th>
                                                    <th class="text-left px-4 py-3">Error</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="c in (websiteChecks || [])" :key="c.id">
                                                    <tr class="border-b border-gray-800">
                                                        <td class="px-4 py-2 text-xs text-gray-500" x-text="formatDate(c.checked_at) || ''"></td>
                                                        <td class="px-4 py-2 text-xs" :class="c.is_up ? 'text-green-400' : 'text-red-400'" x-text="c.http_status_code || ''"></td>
                                                        <td class="px-4 py-2 text-xs text-gray-400" x-text="(c.response_time_ms || 0) + 'ms'"></td>
                                                        <td class="px-4 py-2"><span class="text-xs px-1.5 py-0.5 rounded-full" :class="c.is_up ? 'bg-green-900 text-green-400' : 'bg-red-900 text-red-400'" x-text="c.is_up ? 'up' : 'down'"></span></td>
                                                        <td class="px-4 py-2 text-xs text-gray-500 truncate max-w-[200px]" x-text="c.error_message || '-'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="showWebsiteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="showWebsiteModal = false">
                            <div class="bg-gray-900 border border-gray-800 rounded-lg w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                                <div class="p-6">
                                    <h2 class="text-lg font-semibold mb-4" x-text="editingWebsite ? 'Edit Website' : 'Add Website'"></h2>
                                    <form @submit.prevent="saveWebsite" class="space-y-3">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Name</label>
                                                <input type="text" x-model="websiteForm.name" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Server</label>
                                                <select x-model="websiteForm.server_id" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                                    <option value="">Select...</option>
                                                    <template x-for="s in (serverList?.data || servers || [])" :key="s.id">
                                                        <option :value="s.id" x-text="s.name || ''"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">URL</label>
                                            <input type="url" x-model="websiteForm.url" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                        </div>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Interval (s)</label>
                                                <input type="number" x-model="websiteForm.check_interval_seconds" min="30" max="3600" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Expected HTTP</label>
                                                <input type="number" x-model="websiteForm.expected_status_code" min="100" max="599" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1">Timeout (s)</label>
                                                <input type="number" x-model="websiteForm.timeout_seconds" min="1" max="60" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-400 mb-1">Expected Keyword (optional)</label>
                                            <input type="text" x-model="websiteForm.expected_keyword" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" x-model="websiteForm.enabled" class="rounded bg-gray-800 border-gray-700">
                                            <label class="text-xs text-gray-400">Enabled</label>
                                        </div>
                                        <div class="flex justify-end gap-2 pt-2">
                                            <button type="button" @click="showWebsiteModal = false" class="px-4 py-2 text-sm bg-gray-800 hover:bg-gray-700 rounded transition-colors">Cancel</button>
                                            <button type="submit" :disabled="savingWebsite" class="px-4 py-2 text-sm bg-cyan-600 hover:bg-cyan-500 rounded font-medium transition-colors disabled:opacity-50" x-text="savingWebsite ? 'Saving...' : 'Save'"></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="view === 'settings'" x-cloak>
                        <h1 class="text-2xl font-bold mb-6">Settings</h1>
                        <div x-show="loadingSettings" class="text-gray-400 py-4">Loading settings...</div>
                        <template x-if="!loadingSettings">
                            <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 max-w-2xl">
                                <p x-show="Object.keys(settings || {}).length === 0" class="text-gray-500 text-sm py-4">No settings configured.</p>
                                <template x-for="(value, key) in settings" :key="key">
                                    <div class="mb-4">
                                        <label class="block text-xs text-gray-400 mb-1 uppercase tracking-wide" x-text="key"></label>
                                        <input type="text" x-model="settings[key]" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded text-sm focus:border-cyan-500 outline-none text-gray-100">
                                    </div>
                                </template>
                                <button @click="updateSettings" class="mt-4 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 rounded-lg text-sm font-medium transition-colors">Save Settings</button>
                            </div>
                        </template>
                    </div>
                </main>
            </div>
        </template>
    </div>
</body>
</html>
