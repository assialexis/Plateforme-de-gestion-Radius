<?php $pageTitle = __('page.dashboard'); $currentPage = 'dashboard'; ?>

<div x-data="dashboardData()" x-init="init()">

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
        <!-- Sessions actives -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-emerald-50 dark:bg-emerald-900/20">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.active_sessions') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="monitoring.active_sessions ?? 0">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="(monitoring.pppoe_sessions ?? 0) + ' PPPoE'"></span>
                <span class="text-gray-300 dark:text-gray-500">/</span>
                <span x-text="(monitoring.hotspot_sessions ?? 0) + ' Hotspot'"></span>
            </div>
        </div>

        <!-- Utilisateurs PPPoE -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-blue-50 dark:bg-blue-900/20">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.pppoe_users') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="pppoe.active_users ?? 0">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="'/' + (pppoe.total_users ?? 0) + ' total'"></span>
                <template x-if="pppoe.suspended_users > 0">
                    <span class="text-amber-500" x-text="pppoe.suspended_users + ' ' + __('dashboard.susp')"></span>
                </template>
            </div>
        </div>

        <!-- Vouchers actifs -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-violet-50 dark:bg-violet-900/20">
                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.active_vouchers') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="stats.vouchers_by_status?.active ?? 0">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="(stats.vouchers_by_status?.unused ?? 0) + ' ' + __('dashboard.avail')"></span>
                <span class="text-gray-300 dark:text-gray-500">/</span>
                <span class="text-red-400" x-text="(stats.vouchers_by_status?.expired ?? 0) + ' ' + __('dashboard.exp')"></span>
            </div>
        </div>

        <!-- Trafic 24h -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-orange-50 dark:bg-orange-900/20">
                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.traffic_24h') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="formatBytes(monitoring.total_24h ?? 0)">0 B</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="'↓ ' + formatBytes(monitoring.download_24h ?? 0)"></span>
                <span class="text-gray-300 dark:text-gray-500">/</span>
                <span x-text="'↑ ' + formatBytes(monitoring.upload_24h ?? 0)"></span>
            </div>
        </div>

        <!-- Revenu aujourd'hui -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-emerald-50 dark:bg-emerald-900/20">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.revenue_today') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="formatMoney(billing.daily_revenue ?? 0)">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="__('dashboard.month') + ' ' + formatMoney(billing.monthly_revenue ?? 0)"></span>
            </div>
        </div>

        <!-- NAS actifs -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-slate-50 dark:bg-slate-800/50">
                    <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.nas_configured') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="stats.nas_count ?? 0">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="stats.connections_today + ' ' + __('dashboard.auth_today')"></span>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-6">
        <!-- Trafic réseau 7 jours -->
        <div class="lg:col-span-2 bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.network_traffic') ?></h3>
                <span class="text-2xs text-gray-400"><?= __('dashboard.last_7_days') ?></span>
            </div>
            <div class="relative" style="height: 240px;">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <!-- Doughnut vouchers -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.vouchers') ?></h3>
                <span class="text-2xs text-gray-400" x-text="stats.total_vouchers + ' total'"></span>
            </div>
            <div class="relative flex justify-center" style="height: 170px;">
                <canvas id="voucherChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-[#30363d]"></span>
                    <span class="text-2xs text-gray-500 dark:text-gray-400"><?= __('dashboard.unused') ?></span>
                    <span class="text-2xs font-medium text-gray-900 dark:text-white ml-auto" x-text="stats.vouchers_by_status?.unused ?? 0"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-2xs text-gray-500 dark:text-gray-400"><?= __('dashboard.active') ?></span>
                    <span class="text-2xs font-medium text-gray-900 dark:text-white ml-auto" x-text="stats.vouchers_by_status?.active ?? 0"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span class="text-2xs text-gray-500 dark:text-gray-400"><?= __('dashboard.expired') ?></span>
                    <span class="text-2xs font-medium text-gray-900 dark:text-white ml-auto" x-text="stats.vouchers_by_status?.expired ?? 0"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-2xs text-gray-500 dark:text-gray-400"><?= __('dashboard.disabled') ?></span>
                    <span class="text-2xs font-medium text-gray-900 dark:text-white ml-auto" x-text="stats.vouchers_by_status?.disabled ?? 0"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-6">
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.connections') ?></h3>
                <span class="text-2xs text-gray-400"><?= __('dashboard.last_7_days') ?></span>
            </div>
            <div class="relative" style="height: 210px;">
                <canvas id="connectionsChart"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.hourly_traffic') ?></h3>
                <span class="text-2xs text-gray-400"><?= __('dashboard.last_24h') ?></span>
            </div>
            <div class="relative" style="height: 210px;">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-6">
        <!-- Top consommateurs -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.top_consumers') ?></h3>
                <a href="index.php?page=monitoring" class="text-2xs text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"><?= __('dashboard.view_all') ?></a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b border-gray-100 dark:border-[#30363d]">
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.user') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.type') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider text-right"><?= __('dashboard.total_label') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="user in topConsumers" :key="user.username">
                            <tr class="border-b border-gray-50 dark:border-[#30363d]/50">
                                <td class="py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md flex items-center justify-center text-2xs font-semibold"
                                             :class="user.type === 'pppoe' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400'"
                                             x-text="(user.customer_name || user.username).charAt(0).toUpperCase()"></div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-900 dark:text-white" x-text="user.customer_name || user.username"></p>
                                            <p class="text-2xs text-gray-400" x-text="user.username" x-show="user.customer_name"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5">
                                    <span class="text-2xs font-medium px-1.5 py-0.5 rounded"
                                          :class="user.type === 'pppoe' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400'"
                                          x-text="user.type === 'pppoe' ? 'PPPoE' : 'Hotspot'"></span>
                                </td>
                                <td class="py-2.5 text-right">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white" x-text="formatBytes(user.total)"></p>
                                    <div class="flex items-center justify-end gap-1.5 text-2xs text-gray-400 mt-0.5">
                                        <span x-text="'↓' + formatBytes(user.download)"></span>
                                        <span x-text="'↑' + formatBytes(user.upload)"></span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="topConsumers.length === 0">
                            <td colspan="3" class="py-8 text-center text-xs text-gray-400"><?= __('dashboard.no_data') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sessions actives -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.active_sessions') ?></h3>
                <a href="index.php?page=sessions" class="text-2xs text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"><?= __('dashboard.view_all') ?></a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b border-gray-100 dark:border-[#30363d]">
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.user') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.ip') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.duration') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider text-right"><?= __('dashboard.data') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="session in activeSessions.slice(0, 5)" :key="session.id">
                            <tr class="border-b border-gray-50 dark:border-[#30363d]/50">
                                <td class="py-2.5">
                                    <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="session.voucher_code || session.username"></span>
                                </td>
                                <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="session.client_ip || '-'"></td>
                                <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(session.session_time)"></td>
                                <td class="py-2.5 text-right text-xs text-gray-500 dark:text-gray-400" x-text="formatBytes((session.input_octets || 0) + (session.output_octets || 0))"></td>
                            </tr>
                        </template>
                        <tr x-show="activeSessions.length === 0">
                            <td colspan="4" class="py-8 text-center text-xs text-gray-400"><?= __('dashboard.no_active_session') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Dernières connexions -->
    <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.recent_connections') ?></h3>
            <a href="index.php?page=logs" class="text-2xs text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"><?= __('dashboard.view_all') ?></a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left border-b border-gray-100 dark:border-[#30363d]">
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.date') ?></th>
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.user') ?></th>
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.status') ?></th>
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.nas') ?></th>
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.mac') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="log in recentConnections" :key="log.id">
                        <tr class="border-b border-gray-50 dark:border-[#30363d]/50">
                            <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400" x-text="new Date(log.created_at).toLocaleString('fr-FR')"></td>
                            <td class="py-2.5">
                                <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="log.username"></span>
                            </td>
                            <td class="py-2.5">
                                <span class="text-2xs font-medium px-1.5 py-0.5 rounded"
                                      :class="log.action === 'accept' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'"
                                      x-text="log.action === 'accept' ? __('dashboard.accepted') : __('dashboard.rejected')"></span>
                            </td>
                            <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400" x-text="log.nas_name || log.nas_ip"></td>
                            <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="log.client_mac || '-'"></td>
                        </tr>
                    </template>
                    <tr x-show="recentConnections.length === 0">
                        <td colspan="5" class="py-8 text-center text-xs text-gray-400"><?= __('dashboard.no_recent_connection') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function dashboardData() {
    return {
        stats: {},
        pppoe: {},
        billing: {},
        monitoring: {},
        topConsumers: [],
        activeSessions: [],
        recentConnections: [],
        hourlyStats: [],
        trafficChart: null,
        voucherChart: null,
        connectionsChart: null,
        hourlyChart: null,

        async init() {
            await Promise.all([
                this.loadFullStats(),
                this.loadTopConsumers(),
                this.loadActiveSessions(),
                this.loadRecentConnections(),
            ]);
            await this.loadCharts();
            setInterval(() => {
                this.loadFullStats();
                this.loadActiveSessions();
                this.loadTopConsumers();
            }, 30000);
        },

        async loadFullStats() {
            try {
                const response = await API.get('/dashboard/full');
                const d = response.data;
                this.stats = d.main || {};
                this.pppoe = d.pppoe || {};
                this.billing = d.billing || {};
                this.monitoring = d.monitoring || {};
            } catch (error) { console.error('Error loading stats:', error); }
        },

        async loadTopConsumers() {
            try {
                const response = await API.get('/monitoring/top-users?limit=5');
                this.topConsumers = response.data || [];
            } catch (error) { console.error('Error loading top consumers:', error); }
        },

        async loadActiveSessions() {
            try {
                const response = await API.get('/sessions/active');
                this.activeSessions = response.data || [];
            } catch (error) { console.error('Error loading sessions:', error); }
        },

        async loadRecentConnections() {
            try {
                const response = await API.get('/dashboard/recent?limit=10');
                this.recentConnections = response.data || [];
            } catch (error) { console.error('Error loading connections:', error); }
        },

        async loadCharts() {
            try {
                const [connectionsData, dataUsage, hourlyData] = await Promise.all([
                    API.get('/dashboard/connections?days=7'),
                    API.get('/dashboard/data?days=7'),
                    API.get('/monitoring/hourly-stats'),
                ]);
                this.renderTrafficChart(dataUsage.data || []);
                this.renderVoucherChart();
                this.renderConnectionsChart(connectionsData.data || []);
                this.renderHourlyChart(hourlyData.data || []);
            } catch (error) { console.error('Error loading charts:', error); }
        },

        chartDefaults() {
            const isDark = document.documentElement.classList.contains('dark');
            return {
                gridColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)',
                tickColor: isDark ? '#8b949e' : '#9ca3af',
                tooltipBg: isDark ? '#161b22' : '#fff',
                tooltipTitle: isDark ? '#f0f6fc' : '#111827',
                tooltipBody: isDark ? '#8b949e' : '#6b7280',
                tooltipBorder: isDark ? '#30363d' : '#f3f4f6',
                legendColor: isDark ? '#8b949e' : '#9ca3af',
            };
        },

        renderTrafficChart(data) {
            const ctx = document.getElementById('trafficChart');
            if (!ctx) return;
            if (this.trafficChart) this.trafficChart.destroy();
            const c = this.chartDefaults();

            this.trafficChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => new Date(d.date).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' })),
                    datasets: [
                        { label: 'Download', data: data.map(d => (d.download || 0) / (1024*1024)), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)', fill: true, tension: 0.4, borderWidth: 1.5, pointRadius: 2, pointBackgroundColor: '#6366f1' },
                        { label: 'Upload', data: data.map(d => (d.upload || 0) / (1024*1024)), borderColor: '#a78bfa', backgroundColor: 'rgba(167,139,250,0.06)', fill: true, tension: 0.4, borderWidth: 1.5, pointRadius: 2, pointBackgroundColor: '#a78bfa' }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                        tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, padding: 10, cornerRadius: 6, callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' MB' } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.tickColor, font: { size: 11 } } },
                        y: { beginAtZero: true, grid: { color: c.gridColor }, ticks: { color: c.tickColor, font: { size: 11 }, callback: v => v + ' MB' } }
                    }
                }
            });
        },

        renderVoucherChart() {
            const ctx = document.getElementById('voucherChart');
            if (!ctx) return;
            if (this.voucherChart) this.voucherChart.destroy();
            const c = this.chartDefaults();
            const vs = this.stats.vouchers_by_status || {};

            this.voucherChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [__('dashboard.chart_unused'), __('dashboard.chart_active'), __('dashboard.chart_expired'), __('dashboard.chart_disabled')],
                    datasets: [{ data: [vs.unused || 0, vs.active || 0, vs.expired || 0, vs.disabled || 0], backgroundColor: ['#d1d5db', '#10b981', '#ef4444', '#f59e0b'], borderWidth: 0, hoverOffset: 3 }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false }, tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, cornerRadius: 6 } } }
            });
        },

        renderConnectionsChart(data) {
            const ctx = document.getElementById('connectionsChart');
            if (!ctx) return;
            if (this.connectionsChart) this.connectionsChart.destroy();
            const c = this.chartDefaults();

            this.connectionsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => new Date(d.date).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' })),
                    datasets: [
                        { label: __('dashboard.chart_accepted'), data: data.map(d => d.accepted || 0), backgroundColor: '#10b981', borderRadius: 3, barPercentage: 0.65 },
                        { label: __('dashboard.chart_rejected'), data: data.map(d => d.rejected || 0), backgroundColor: '#ef4444', borderRadius: 3, barPercentage: 0.65 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                        tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, cornerRadius: 6 }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.tickColor, font: { size: 11 } } },
                        y: { beginAtZero: true, grid: { color: c.gridColor }, ticks: { color: c.tickColor, font: { size: 11 } } }
                    }
                }
            });
        },

        renderHourlyChart(data) {
            const ctx = document.getElementById('hourlyChart');
            if (!ctx) return;
            if (this.hourlyChart) this.hourlyChart.destroy();
            const c = this.chartDefaults();

            this.hourlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.hour_num + 'h'),
                    datasets: [
                        { label: 'Download', data: data.map(d => (d.download || 0) / (1024*1024)), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 2, barPercentage: 0.8 },
                        { label: 'Upload', data: data.map(d => (d.upload || 0) / (1024*1024)), backgroundColor: 'rgba(167,139,250,0.6)', borderRadius: 2, barPercentage: 0.8 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                        tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, cornerRadius: 6, callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' MB' } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.tickColor, font: { size: 10 }, maxRotation: 0 } },
                        y: { beginAtZero: true, grid: { color: c.gridColor }, ticks: { color: c.tickColor, font: { size: 11 }, callback: v => v + ' MB' } }
                    }
                }
            });
        },

        formatBytes(bytes) { return formatBytes(bytes); },
        formatTime(seconds) { return formatTime(seconds); },
        formatMoney(amount) { return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(amount || 0); }
    }
}
</script>
