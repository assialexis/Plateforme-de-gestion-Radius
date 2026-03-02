<?php
$nasId = $_GET['nas_id'] ?? null;
if (!$nasId) {
    header('Location: index.php?page=nas');
    exit;
}
$pageTitle = __('nas_map.network_map');
$currentPage = 'nas';
?>

<div x-data="nasMapPage()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="index.php?page=nas" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <?= __('nas_map.back_to_nas') ?>
            </a>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span x-text="nas ? '<?= __('nas_map.network_map') ?> - ' + nas.shortname : '<?= __('nas_map.network_map') ?>'"></span>
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1"><?= __('nas_map.pppoe_visualization') ?></p>
            </div>
            <div class="flex items-center gap-4">
                <!-- Legend -->
                <div class="flex items-center gap-4 bg-white dark:bg-[#161b22] rounded-lg px-4 py-2 shadow-sm border border-gray-200/60 dark:border-[#30363d]">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400"><?= __('nas_map.online') ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-red-500 rounded-full"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400"><?= __('nas_map.offline') ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-yellow-500 rounded-full"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400"><?= __('nas_map.no_position') ?></span>
                    </div>
                </div>
                <!-- Refresh Button -->
                <button @click="refreshData()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                        :disabled="loading">
                    <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <?= __('common.refresh') ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading && !nas" class="flex justify-center py-12">
        <svg class="animate-spin h-10 w-10 text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Stats Cards -->
    <div x-show="nas" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Total Clients -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= __('nas_map.total_clients') ?></p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="clients.length"></p>
                </div>
            </div>
        </div>
        <!-- Online -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= __('nas_map.online') ?></p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="onlineCount"></p>
                </div>
            </div>
        </div>
        <!-- Offline -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= __('nas_map.offline') ?></p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="offlineCount"></p>
                </div>
            </div>
        </div>
        <!-- With Location -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= __('nas_map.with_position') ?></p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="withLocationCount"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div x-show="nas" class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div id="nas-network-map" class="w-full h-[600px]"></div>
    </div>

    <!-- Clients List -->
    <div x-show="nas" class="mt-6 bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d]">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d]">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('nas_map.pppoe_clients') ?></h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('common.status') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('common.client') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('common.username') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('common.profile') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="client in clients" :key="client.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span x-show="client.is_online" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                    <?= __('nas_map.online') ?>
                                </span>
                                <span x-show="!client.is_online" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                    <?= __('nas_map.offline') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="client.customer_name || '-'"></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400" x-text="client.customer_phone || ''"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="text-sm bg-gray-100 dark:bg-[#21262d] px-2 py-1 rounded" x-text="client.username"></code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-white" x-text="client.profile_name || '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span x-show="client.latitude && client.longitude" class="inline-flex items-center gap-1 text-sm text-green-600 dark:text-green-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <?= __('nas_map.position_yes') ?>
                                </span>
                                <span x-show="!client.latitude || !client.longitude" class="inline-flex items-center gap-1 text-sm text-yellow-600 dark:text-yellow-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <?= __('nas_map.position_undefined') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <a :href="'index.php?page=pppoe-user&id=' + client.id"
                                       class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                                       title="<?= __('common.edit') ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <button @click="focusOnClient(client)"
                                            x-show="client.latitude && client.longitude"
                                            class="p-2 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors"
                                            title="<?= __('nas_map.view_on_map') ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="clients.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <?= __('nas_map.no_pppoe_client') ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* Animation pour les lignes de connexion */
    @keyframes pulse-line {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .connection-line-online {
        animation: pulse-line 2s ease-in-out infinite;
    }

    /* Custom markers */
    .nas-marker {
        background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4);
    }
    .client-marker-online {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border: 2px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.4);
    }
    .client-marker-offline {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border: 2px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }

    /* Leaflet popup styling */
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
    }
    .dark .leaflet-popup-content-wrapper {
        background: #1f2937;
        color: #f3f4f6;
    }
    .dark .leaflet-popup-tip {
        background: #1f2937;
    }
</style>

<script>
function nasMapPage() {
    return {
        nasId: <?= json_encode($nasId) ?>,
        nas: null,
        clients: [],
        loading: true,
        map: null,
        markers: [],
        connectionLines: [],

        get onlineCount() {
            return this.clients.filter(c => c.is_online).length;
        },

        get offlineCount() {
            return this.clients.filter(c => !c.is_online).length;
        },

        get withLocationCount() {
            return this.clients.filter(c => c.latitude && c.longitude).length;
        },

        async init() {
            await this.loadData();
            this.$nextTick(() => {
                this.initMap();
            });
        },

        async loadData() {
            this.loading = true;
            try {
                // Charger les infos du NAS
                const nasResponse = await API.get(`/nas/${this.nasId}`);
                this.nas = nasResponse.data;

                // Charger les clients de ce NAS
                const clientsResponse = await API.get(`/nas/${this.nasId}/clients`);
                this.clients = clientsResponse.data || [];

            } catch (error) {
                console.error('Error loading data:', error);
                showToast('<?= __('api.error_loading') ?>', 'error');
            } finally {
                this.loading = false;
            }
        },

        async refreshData() {
            await this.loadData();
            this.updateMap();
        },

        initMap() {
            if (!this.nas || !this.nas.latitude || !this.nas.longitude) {
                console.warn('NAS has no location');
                return;
            }

            const nasLat = parseFloat(this.nas.latitude);
            const nasLng = parseFloat(this.nas.longitude);

            // Initialiser la carte
            this.map = L.map('nas-network-map').setView([nasLat, nasLng], 14);

            // Tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            this.updateMap();
        },

        updateMap() {
            if (!this.map) return;

            // Effacer les anciens marqueurs et lignes
            this.markers.forEach(m => this.map.removeLayer(m));
            this.connectionLines.forEach(l => this.map.removeLayer(l));
            this.markers = [];
            this.connectionLines = [];

            const nasLat = parseFloat(this.nas.latitude);
            const nasLng = parseFloat(this.nas.longitude);

            // Créer l'icône du NAS
            const nasIcon = L.divIcon({
                className: 'nas-marker',
                iconSize: [40, 40],
                iconAnchor: [20, 20],
                popupAnchor: [0, -20],
                html: `<div class="flex items-center justify-center w-full h-full">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M5 2h14a2 2 0 012 2v16a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2zm0 2v4h14V4H5zm0 6v4h14v-4H5zm0 6v4h14v-4H5zm2-8a1 1 0 110-2 1 1 0 010 2zm0 6a1 1 0 110-2 1 1 0 010 2zm0 6a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                </div>`
            });

            // Marqueur NAS
            const nasMarker = L.marker([nasLat, nasLng], { icon: nasIcon })
                .addTo(this.map)
                .bindPopup(`
                    <div class="p-2">
                        <h4 class="font-bold text-lg">${this.nas.shortname}</h4>
                        <p class="text-sm text-gray-600">${this.nas.nasname}</p>
                        <p class="text-sm">${this.nas.description || ''}</p>
                        <p class="text-xs text-gray-500 mt-2">${this.nas.address || ''}</p>
                    </div>
                `);
            this.markers.push(nasMarker);

            // Bounds pour le zoom
            const bounds = [[nasLat, nasLng]];

            // Ajouter les clients
            this.clients.forEach(client => {
                if (!client.latitude || !client.longitude) return;

                const clientLat = parseFloat(client.latitude);
                const clientLng = parseFloat(client.longitude);
                bounds.push([clientLat, clientLng]);

                // Icône client
                const clientIcon = L.divIcon({
                    className: client.is_online ? 'client-marker-online' : 'client-marker-offline',
                    iconSize: [24, 24],
                    iconAnchor: [12, 12],
                    popupAnchor: [0, -12],
                    html: `<div class="flex items-center justify-center w-full h-full">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>`
                });

                // Marqueur client
                const clientMarker = L.marker([clientLat, clientLng], { icon: clientIcon })
                    .addTo(this.map)
                    .bindPopup(`
                        <div class="p-2">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-3 h-3 rounded-full ${client.is_online ? 'bg-green-500' : 'bg-red-500'}"></span>
                                <span class="font-semibold">${client.is_online ? '<?= __('nas_map.online') ?>' : '<?= __('nas_map.offline') ?>'}</span>
                            </div>
                            <h4 class="font-bold">${client.customer_name || client.username}</h4>
                            <p class="text-sm text-gray-600">${client.username}</p>
                            ${client.profile_name ? `<p class="text-sm"><?= __('common.profile') ?>: ${client.profile_name}</p>` : ''}
                            ${client.customer_phone ? `<p class="text-sm"><?= __('common.phone') ?>: ${client.customer_phone}</p>` : ''}
                            <a href="index.php?page=pppoe-user&id=${client.id}" class="inline-block mt-2 text-primary-600 hover:underline text-sm"><?= __('nas_map.view_client') ?></a>
                        </div>
                    `);
                this.markers.push(clientMarker);

                // Ligne de connexion
                const lineColor = client.is_online ? '#22c55e' : '#ef4444';
                const lineWeight = client.is_online ? 3 : 2;
                const lineDash = client.is_online ? null : '10, 10';

                const connectionLine = L.polyline([[nasLat, nasLng], [clientLat, clientLng]], {
                    color: lineColor,
                    weight: lineWeight,
                    opacity: client.is_online ? 0.8 : 0.5,
                    dashArray: lineDash,
                    className: client.is_online ? 'connection-line-online' : ''
                }).addTo(this.map);

                this.connectionLines.push(connectionLine);
            });

            // Ajuster le zoom pour voir tous les points
            if (bounds.length > 1) {
                this.map.fitBounds(bounds, { padding: [50, 50] });
            }
        },

        focusOnClient(client) {
            if (!this.map || !client.latitude || !client.longitude) return;

            const lat = parseFloat(client.latitude);
            const lng = parseFloat(client.longitude);

            this.map.setView([lat, lng], 17);

            // Trouver et ouvrir le popup du marqueur
            this.markers.forEach(marker => {
                const pos = marker.getLatLng();
                if (Math.abs(pos.lat - lat) < 0.0001 && Math.abs(pos.lng - lng) < 0.0001) {
                    marker.openPopup();
                }
            });
        }
    };
}
</script>
