<!-- vis-network -->
<script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

<div x-data="topologyPage()" x-init="init()" class="min-h-screen">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <a href="index.php?page=zones"
                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-7 h-7 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <?= __('topology.topological_view')?>
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    x-text="zone ? zone.name : '<?= __('topology.all_zones')?>'"></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- Sélecteur de zone -->
            <select x-model="selectedZoneId" @change="loadTopology()"
                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-xl bg-white dark:bg-[#21262d] text-gray-900 dark:text-white shadow-sm">
                <option value="">
                    <?= __('topology.all_zones')?>
                </option>
                <template x-for="z in zones" :key="z.id">
                    <option :value="z.id" x-text="z.name"></option>
                </template>
            </select>
            <!-- Auto-refresh toggle -->
            <button @click="toggleAutoRefresh()"
                :class="autoRefresh ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-[#21262d] text-gray-600 dark:text-gray-300'"
                class="p-2 rounded-xl transition-all duration-300 shadow-sm"
                :title="autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF'">
                <svg class="w-5 h-5" :class="autoRefresh && 'animate-spin-slow'" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" x-transition class="flex flex-col items-center justify-center py-24">
        <div class="relative">
            <div class="w-16 h-16 border-4 border-purple-200 dark:border-purple-900 rounded-full"></div>
            <div
                class="absolute top-0 left-0 w-16 h-16 border-4 border-purple-500 border-t-transparent rounded-full animate-spin">
            </div>
        </div>
        <p class="mt-4 text-gray-500 dark:text-gray-400">
            <?= __('topology.loading')?>
        </p>
    </div>

    <!-- Main Content -->
    <div x-show="!loading" x-transition class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/20">
                <div class="relative z-10">
                    <div class="text-3xl font-bold" x-text="stats.totalNas">0</div>
                    <div class="text-blue-100 text-sm mt-1">
                        <?= __('topology.routers')?>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full"></div>
                <svg class="absolute right-3 bottom-3 w-8 h-8 text-white/30" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                </svg>
            </div>
            <div
                class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-green-500/20">
                <div class="relative z-10">
                    <div class="text-3xl font-bold" x-text="stats.onlineNas">0</div>
                    <div class="text-green-100 text-sm mt-1">
                        <?= __('topology.online')?>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full"></div>
                <svg class="absolute right-3 bottom-3 w-8 h-8 text-white/30" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07" />
                </svg>
            </div>
            <div
                class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-5 text-white shadow-lg shadow-purple-500/20">
                <div class="relative z-10">
                    <div class="text-3xl font-bold" x-text="stats.totalClients">0</div>
                    <div class="text-purple-100 text-sm mt-1">
                        <?= __('topology.clients')?>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full"></div>
                <svg class="absolute right-3 bottom-3 w-8 h-8 text-white/30" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div
                class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg shadow-amber-500/20">
                <div class="relative z-10">
                    <div class="text-3xl font-bold" x-text="formatBytes(stats.totalTraffic)">0 B</div>
                    <div class="text-amber-100 text-sm mt-1">
                        <?= __('topology.traffic')?>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full"></div>
                <svg class="absolute right-3 bottom-3 w-8 h-8 text-white/30" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
        </div>

        <!-- Topology Visualization (vis-network) -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-3xl border border-gray-200/60 dark:border-[#30363d] shadow-xl overflow-hidden relative">
            <div id="topology-network"
                class="w-full h-[600px] bg-gray-50/50 dark:bg-transparent cursor-grab active:cursor-grabbing"></div>

            <!-- Context Menu (Optional, hidden by default) -->
            <div id="network-context-menu"
                class="hidden absolute z-50 bg-white dark:bg-[#21262d] rounded-xl shadow-xl border border-gray-200 dark:border-[#30363d] overflow-hidden min-w-[150px]"
                style="top:0; left:0;">
                <ul class="py-1">
                    <li>
                        <button
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] transition-colors"
                            @click="toggleSelectedNodeExpansion()">
                            <span
                                x-text="selectedNodeExpanded ? '<?= __('topology.hide_clients')?>' : '<?= __('topology.show_clients')?>'"></span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Controls overlay -->
            <div class="absolute bottom-4 right-4 flex flex-col gap-2">
                <button @click="fitNetwork()"
                    class="bg-white dark:bg-[#21262d] p-2 rounded-xl shadow-lg border border-gray-200 dark:border-[#30363d] text-gray-600 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 transition-colors"
                    title="Fit to screen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Legend -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200/60 dark:border-[#30363d] p-5 flex flex-col items-center justify-center text-center">
            <div class="text-sm text-gray-500 mb-3">
                <?= __('topology.interactive_legend') ?: 'Hint: You can drag nodes and zoom to explore the network.'?>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-6 selection-none">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-blue-600"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Server</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-purple-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Router (Online)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-gray-400"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Router (Offline)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full bg-emerald-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Client</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fade-in-up {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fade-in {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes flow-down {
        0% {
            transform: translateY(-100%);
        }

        100% {
            transform: translateY(400%);
        }
    }

    @keyframes spin-slow {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .animate-fade-in-up {
        animation: fade-in-up 0.5s ease-out forwards;
        opacity: 0;
    }

    .animate-fade-in {
        animation: fade-in 0.3s ease-out forwards;
    }

    .animate-flow-down {
        animation: flow-down 1.5s linear infinite;
    }

    .animate-spin-slow {
        animation: spin-slow 3s linear infinite;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #4b5563;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #6b7280;
    }
</style>

<script>
    function topologyPage() {
        return {
            zones: [],
            zone: null,
            selectedZoneId: new URLSearchParams(window.location.search).get('zone') || '',
            nasList: [],
            loading: true,
            autoRefresh: false,
            refreshInterval: null,
            stats: {
                totalNas: 0,
                onlineNas: 0,
                totalClients: 0,
                totalTraffic: 0
            },
            network: null,
            nodes: null,
            edges: null,
            selectedNodeId: null,
            selectedNodeExpanded: false,

            async init() {
                this.loadZones();
                await this.loadTopology();
                window.addEventListener('theme-changed', () => {
                    if (this.network) this.drawNetwork();
                });
                document.addEventListener('click', (e) => {
                    const cm = document.getElementById('network-context-menu');
                    if (cm && !cm.contains(e.target)) {
                        cm.classList.add('hidden');
                    }
                });
            },

            async loadZones() {
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data || [];
                    if (this.selectedZoneId) {
                        this.zone = this.zones.find(z => z.id == this.selectedZoneId);
                    }
                } catch (error) {
                    console.error('Error loading zones:', error);
                }
            },

            async loadTopology() {
                this.loading = true;
                try {
                    let nasUrl = '/nas';
                    if (this.selectedZoneId) {
                        nasUrl += '?zone=' + this.selectedZoneId;
                        this.zone = this.zones.find(z => z.id == this.selectedZoneId);
                    } else {
                        this.zone = null;
                    }
                    const nasResponse = await API.get(nasUrl);
                    const existingExpanded = {};
                    this.nasList.forEach(n => { existingExpanded[n.id] = n.expanded; });
                    this.nasList = (nasResponse.data || []).map(nas => ({
                        ...nas,
                        expanded: existingExpanded[nas.id] || false,
                        clients: [],
                        activeClients: 0,
                        isOnline: false
                    }));
                    const sessionsResponse = await API.get('/sessions/active');
                    const sessions = sessionsResponse.data || [];
                    let totalClients = 0;
                    let totalTraffic = 0;
                    this.nasList.forEach(nas => {
                        const nasSessions = sessions.filter(s => s.router_id === nas.router_id);
                        nas.activeClients = nasSessions.length;
                        nas.isOnline = nasSessions.length > 0;
                        nas.clients = nasSessions;
                        totalClients += nasSessions.length;
                        nasSessions.forEach(s => {
                            totalTraffic += (parseInt(s.input_octets) || 0) + (parseInt(s.output_octets) || 0);
                        });
                    });
                    this.stats = {
                        totalNas: this.nasList.length,
                        onlineNas: this.nasList.filter(n => n.isOnline).length,
                        totalClients: totalClients,
                        totalTraffic: totalTraffic
                    };
                    setTimeout(() => {
                        this.drawNetwork();
                    }, 100);
                } catch (error) {
                    showToast(error.message || __('api.error_loading'), 'error');
                } finally {
                    this.loading = false;
                }
            },

            drawNetwork() {
                const container = document.getElementById('topology-network');
                if (!container) return;
                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#e5e7eb' : '#374151'; 
                const primaryBlue = '#3866F2'; 
                const lightBlueEdge = '#A5C1F5'; 
                const bgNode = isDark ? '#21262d' : '#ffffff';
                const serverColor = '#2563EB'; 
                const routerOnlineColor = '#8B5CF6'; 
                const routerOfflineColor = '#9CA3AF'; 
                const clientColor = '#10B981'; 

                const nodeDefaults = {
                    shape: 'dot',
                    font: { color: textColor, face: 'Inter, sans-serif', size: 14, bold: true, vadjust: -5 },
                    borderWidth: 2,
                    shadow: { enabled: true, color: 'rgba(0,0,0,0.1)', size: 10, x: 0, y: 4 }
                };
                const nodesArray = [];
                const edgesArray = [];

                nodesArray.push({
                    ...nodeDefaults, id: 'server', label: 'RADIUS Server', level: 0,
                    color: { background: bgNode, border: serverColor, highlight: { background: serverColor, border: serverColor } },
                    size: 30, shape: 'circularImage',
                    image: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="%232563EB" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>',
                    font: { size: 16, color: serverColor }
                });

                this.nasList.forEach(nas => {
                    const nasColor = nas.isOnline ? routerOnlineColor : routerOfflineColor;
                    const clientsStr = nas.activeClients > 0 ? `\n(${nas.activeClients} clients)` : '';
                    nodesArray.push({
                        ...nodeDefaults, id: `nas-${nas.id}`, label: (nas.shortname || nas.router_id) + clientsStr, level: 1,
                        color: { background: bgNode, border: nasColor, highlight: { background: nasColor, border: nasColor } },
                        size: 25, shape: 'circularImage',
                        image: `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="${encodeURIComponent(nasColor)}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>`
                    });

                    edgesArray.push({
                        from: 'server', to: `nas-${nas.id}`,
                        color: { color: lightBlueEdge, highlight: primaryBlue },
                        smooth: { type: 'cubicBezier', forceDirection: 'horizontal', roundness: 0.5 }, width: 2
                    });

                    if (nas.expanded && nas.clients && nas.clients.length > 0) {
                        nas.clients.forEach((client, idx) => {
                            const clientId = `client-${client.username}-${idx}`;
                            nodesArray.push({
                                ...nodeDefaults, id: clientId, label: client.username + '\n' + (client.client_ip || ''), level: 2,
                                color: { background: bgNode, border: clientColor, highlight: { background: clientColor, border: clientColor } },
                                size: 15, shape: 'circularImage',
                                image: `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="${encodeURIComponent(clientColor)}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>`
                            });
                            edgesArray.push({
                                from: `nas-${nas.id}`, to: clientId,
                                color: { color: isDark ? '#4b5563' : '#d1d5db', highlight: clientColor },
                                smooth: { type: 'cubicBezier', forceDirection: 'horizontal', roundness: 0.5 }, width: 1.5
                            });
                        });
                    }
                });

                this.nodes = new vis.DataSet(nodesArray);
                this.edges = new vis.DataSet(edgesArray);
                const data = { nodes: this.nodes, edges: this.edges };
                const options = {
                    layout: { hierarchical: { direction: 'LR', sortMethod: 'directed', levelSeparation: 300, nodeSpacing: 100, treeSpacing: 200, blockShifting: true, edgeMinimization: true, parentCentralization: true } },
                    physics: false,
                    interaction: { hover: true, navigationButtons: false, keyboard: false, zoomView: true, dragView: true }
                };

                if (this.network) {
                    this.network.setData(data);
                    this.network.setOptions(options);
                } else {
                    this.network = new vis.Network(container, data, options);
                    this.network.on("click", (params) => {
                        document.getElementById('network-context-menu').classList.add('hidden');
                    });
                    this.network.on("doubleClick", (params) => {
                        if (params.nodes.length > 0) {
                            const nodeId = params.nodes[0];
                            if (nodeId.toString().startsWith('nas-')) {
                                const actualNasId = nodeId.substring(4);
                                const nas = this.nasList.find(n => n.id == actualNasId);
                                if (nas) {
                                    this.toggleNas(nas);
                                    this.drawNetwork(); 
                                }
                            }
                        }
                    });
                    this.network.on("oncontext", (params) => {
                        params.event.preventDefault();
                        const nodeId = this.network.getNodeAt({ x: params.pointer.DOM.x, y: params.pointer.DOM.y });
                        if (nodeId && nodeId.toString().startsWith('nas-')) {
                            const actualNasId = nodeId.substring(4);
                            const nas = this.nasList.find(n => n.id == actualNasId);
                            if (nas) {
                                this.selectedNodeId = actualNasId;
                                this.selectedNodeExpanded = nas.expanded;
                                const cm = document.getElementById('network-context-menu');
                                if (cm) {
                                    cm.style.left = params.pointer.DOM.x + 'px';
                                    cm.style.top = params.pointer.DOM.y + 'px';
                                    cm.classList.remove('hidden');
                                }
                            }
                        } else {
                            const cm = document.getElementById('network-context-menu');
                            if (cm) cm.classList.add('hidden');
                        }
                    });
                    setTimeout(() => {
                        if (this.network) this.network.fit({ animation: { duration: 500 } });
                    }, 200);
                }
            },

            toggleSelectedNodeExpansion() {
                if (this.selectedNodeId) {
                    const nas = this.nasList.find(n => n.id == this.selectedNodeId);
                    if (nas) {
                        this.toggleNas(nas);
                        this.drawNetwork();
                    }
                }
                const cm = document.getElementById('network-context-menu');
                if (cm) cm.classList.add('hidden');
            },

            toggleNas(nas) {
                nas.expanded = !nas.expanded;
            },

            fitNetwork() {
                if (this.network) {
                    this.network.fit({ animation: { duration: 500 } });
                }
            },

            toggleAutoRefresh() {
                this.autoRefresh = !this.autoRefresh;
                if (this.autoRefresh) {
                    this.refreshInterval = setInterval(() => this.loadTopology(), 10000);
                    showToast(__('topology.auto_refresh_on'), 'success');
                } else {
                    clearInterval(this.refreshInterval);
                    this.refreshInterval = null;
                    showToast(__('topology.auto_refresh_off'), 'info');
                }
            },

            formatBytes(bytes) {
                if (!bytes || bytes === 0) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let i = 0;
                while (bytes >= 1024 && i < units.length - 1) {
                    bytes /= 1024;
                    i++;
                }
                return bytes.toFixed(i > 0 ? 1 : 0) + ' ' + units[i];
            },

            formatDuration(seconds) {
                if (!seconds) return '0s';
                const hours = Math.floor(seconds / 3600);
                const mins = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;

                if (hours > 0) return `${hours}h ${mins}m`;
                if (mins > 0) return `${mins}m ${secs}s`;
                return `${secs}s`;
            }
        }
    }

</script>
