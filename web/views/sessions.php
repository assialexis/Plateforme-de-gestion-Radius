<?php $pageTitle = __('page.sessions');
$currentPage = 'sessions'; ?>

<div x-data="sessionsPage()" x-init="init()">
    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-[#30363d] mb-6">
        <nav class="flex gap-4">
            <button @click="tab = 'active'"
                :class="tab === 'active' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                <?= __('session.active_sessions')?>
                <span
                    class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                    x-text="activeSessions.length"></span>
            </button>
            <button @click="tab = 'history'"
                :class="tab === 'history' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                <?= __('session.history')?>
            </button>
        </nav>
    </div>

    <!-- Sessions actives -->
    <div x-show="tab === 'active'">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <?= __('session.auto_refresh')?>
                </p>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="autoRefresh" @change="toggleAutoRefresh()"
                        class="w-4 h-4 text-primary-600 rounded">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                        <?= __('session.auto')?>
                    </span>
                </label>
            </div>
            <div class="flex items-center gap-2">
                <button @click="loadActiveSessions()"
                    class="inline-flex items-center px-3 py-1.5 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 border border-primary-300 dark:border-primary-700 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20">
                    <svg class="w-4 h-4 mr-1" :class="{'animate-spin': loading}" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <?= __('session.refresh')?>
                </button>
                <button x-show="activeSessions.length > 0" @click="showDisconnectAllModal = true"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <?= __('session.disconnect_all')?>
                </button>
            </div>
        </div>

        <div
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.voucher')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.client_ip')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.mac')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.zone')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.router')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.duration')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.expiration')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.download')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.upload')?>
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.action')?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                        <template x-for="session in activeSessions" :key="session.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono font-semibold text-gray-900 dark:text-white"
                                        x-text="session.voucher_code"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"
                                    x-text="session.client_ip || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600 dark:text-gray-400"
                                    x-text="session.client_mac || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span x-show="session.zone_name"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                                        x-text="session.zone_name"></span>
                                    <span x-show="!session.zone_name" class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900 dark:text-white"
                                            x-text="session.nas_name || session.nas_ip"></p>
                                        <p x-show="session.router_id" class="text-xs text-gray-500 dark:text-gray-400"
                                            x-text="session.router_id"></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"
                                    x-text="formatTime(session.session_time)"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template x-if="session.valid_until">
                                        <div class="text-sm">
                                            <p :class="isExpired(session.valid_until) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                                x-text="getRemainingTime(session.valid_until)"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400"
                                                x-text="new Date(session.valid_until).toLocaleString('fr-FR')"></p>
                                        </div>
                                    </template>
                                    <template x-if="!session.valid_until">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            <?= __('session.unlimited')?>
                                        </span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 dark:text-blue-400"
                                    x-text="formatBytes(session.output_octets)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-600 dark:text-purple-400"
                                    x-text="formatBytes(session.input_octets)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button @click="disconnectSession(session)"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400 text-sm font-medium">
                                        <?= __('session.disconnect')?>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="activeSessions.length === 0" class="p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    <?= __('session.empty_active')?>
                </h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    <?= __('session.empty_active_desc')?><br>
                    <span class="text-sm">
                        <?= __('session.empty_active_hint')?>
                    </span>
                </p>
            </div>
        </div>
    </div>

    <!-- Historique -->
    <div x-show="tab === 'history'"
        x-init="$watch('tab', val => { if(val === 'history' && history.length === 0) loadHistory() })">
        <!-- Filtres améliorés -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-4 mb-4">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        <?= __('common.search')?>
                    </label>
                    <input type="text" x-model="filters.username" @input.debounce.300ms="loadHistory()"
                        placeholder="<?= __('session.search_code')?>"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        <?= __('session.zone')?>
                    </label>
                    <select x-model="filters.zone_id" @change="loadHistory()"
                        class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        <option value="">
                            <?= __('session.all_zones')?>
                        </option>
                        <template x-for="zone in zones" :key="zone.id">
                            <option :value="zone.id" x-text="zone.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        <?= __('session.status')?>
                    </label>
                    <select x-model="filters.status" @change="loadHistory()"
                        class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        <option value="">
                            <?= __('session.all_statuses')?>
                        </option>
                        <option value="active">
                            <?= __('session.in_progress')?>
                        </option>
                        <option value="terminated">
                            <?= __('session.completed')?>
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        <?= __('session.from')?>
                    </label>
                    <input type="date" x-model="filters.date_from" @change="loadHistory()"
                        class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        <?= __('session.to')?>
                    </label>
                    <input type="date" x-model="filters.date_to" @change="loadHistory()"
                        class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                </div>
                <button @click="resetFilters()"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <?= __('session.reset')?>
                </button>
                
                <div class="relative ml-auto" x-data="{ openExport: false }">
                    <button @click="openExport = !openExport" @click.away="openExport = false" 
                        class="inline-flex items-center px-4 py-2 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] font-medium transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <?= __('common.export') ?? 'Exporter' ?>
                        <svg class="w-4 h-4 ml-2" :class="{'rotate-180': openExport}" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 0.2s;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    <div x-show="openExport" x-transition.opacity x-cloak
                        class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#161b22] rounded-xl shadow-lg border border-gray-200 dark:border-[#30363d] py-1 z-50">
                        <button @click="exportHistory('csv'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export CSV
                        </button>
                        <button @click="exportHistory('excel'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export Excel
                        </button>
                        <button @click="exportHistory('json'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                            <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            Export JSON
                        </button>
                        <button @click="exportHistory('pdf'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                            <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <?= __('session.total_sessions')?>
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="historyTotal"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <?= __('session.total_data')?>
                </p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                    x-text="formatBytes(historyStats.totalData)"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <?= __('session.total_time')?>
                </p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400"
                    x-text="formatTime(historyStats.totalTime)"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <?= __('session.avg_duration')?>
                </p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400"
                    x-text="formatTime(historyStats.avgTime)"></p>
            </div>
        </div>

        <div
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.connection')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.voucher_profile')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.client')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.zone_router')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.duration')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.traffic')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('session.status')?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                        <template x-for="session in history" :key="session.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30">
                                <!-- Connexion -->
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900 dark:text-white"
                                            x-text="formatDateTime(session.start_time)"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <span x-show="session.stop_time">Fin: <span
                                                    x-text="formatDateTime(session.stop_time)"></span></span>
                                            <span x-show="!session.stop_time"
                                                class="text-green-600 dark:text-green-400">
                                                <?= __('session.in_progress_status')?>
                                            </span>
                                        </p>
                                    </div>
                                </td>
                                <!-- Voucher / Profil -->
                                <td class="px-4 py-3">
                                    <div>
                                        <span class="font-mono font-semibold text-gray-900 dark:text-white"
                                            x-text="session.voucher_code"></span>
                                        <p x-show="session.profile_name"
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                            x-text="session.profile_name"></p>
                                    </div>
                                </td>
                                <!-- Client -->
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <p class="text-gray-900 dark:text-white" x-text="session.client_ip || '-'"></p>
                                        <p class="text-xs font-mono text-gray-500 dark:text-gray-400"
                                            x-text="session.client_mac || ''"></p>
                                    </div>
                                </td>
                                <!-- Zone / Routeur -->
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <span x-show="session.zone_name"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                                            x-text="session.zone_name"></span>
                                        <p class="text-gray-600 dark:text-gray-400 mt-1"
                                            x-text="session.nas_name || session.nas_ip"></p>
                                    </div>
                                </td>
                                <!-- Durée -->
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900 dark:text-white"
                                            x-text="formatTime(session.session_time)"></p>
                                    </div>
                                </td>
                                <!-- Trafic -->
                                <td class="px-4 py-3">
                                    <div class="text-sm space-y-1">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-3 h-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span class="text-blue-600 dark:text-blue-400"
                                                x-text="formatBytes(session.output_octets)"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-3 h-3 text-purple-500" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span class="text-purple-600 dark:text-purple-400"
                                                x-text="formatBytes(session.input_octets)"></span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Total: <span
                                                x-text="formatBytes((session.input_octets || 0) + (session.output_octets || 0))"></span>
                                        </p>
                                    </div>
                                </td>
                                <!-- Statut -->
                                <td class="px-4 py-3">
                                    <template x-if="!session.stop_time">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            <span class="w-2 h-2 mr-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                            <?= __('session.connected')?>
                                        </span>
                                    </template>
                                    <template x-if="session.stop_time">
                                        <div>
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                                :class="getTerminateCauseClass(session.terminate_cause)"
                                                x-text="getTerminateCauseLabel(session.terminate_cause)">
                                            </span>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Message si vide -->
            <div x-show="history.length === 0 && !loadingHistory" class="p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    <?= __('session.empty_history')?>
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                    <?= __('session.empty_history_hint')?>
                </p>
            </div>

            <!-- Pagination améliorée -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <?= __('session.showing_results')?> <span x-text="((currentPage - 1) * 50) + 1"></span> - <span
                        x-text="Math.min(currentPage * 50, historyTotal)"></span> / <span x-text="historyTotal"></span>
                </p>
                <div class="flex gap-2">
                    <button @click="currentPage = 1; loadHistory()" :disabled="currentPage <= 1"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50"
                        title="Première page">
                        «
                    </button>
                    <button @click="currentPage--; loadHistory()" :disabled="currentPage <= 1"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50">
                        <?= __('common.previous')?>
                    </button>
                    <span class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400">
                        Page <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                    </span>
                    <button @click="currentPage++; loadHistory()" :disabled="currentPage >= totalPages"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50">
                        <?= __('common.next')?>
                    </button>
                    <button @click="currentPage = totalPages; loadHistory()" :disabled="currentPage >= totalPages"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50"
                        title="Dernière page">
                        »
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation déconnexion globale -->
    <div x-show="showDisconnectAllModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDisconnectAllModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="text-center">
                    <div
                        class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        <?= __('session.disconnect_all_title')?>
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        <?= __('session.disconnect_all_desc')?> <strong x-text="activeSessions.length"></strong>
                    </p>
                    <div class="flex gap-3 justify-center">
                        <button @click="showDisconnectAllModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button @click="disconnectAll()" :disabled="disconnectingAll"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">
                            <span x-show="!disconnectingAll">
                                <?= __('common.confirm')?>
                            </span>
                            <span x-show="disconnectingAll">
                                <?= __('session.disconnecting')?>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function sessionsPage() {
        return {
            tab: 'active',
            activeSessions: [],
            history: [],
            zones: [],
            filters: { username: '', date_from: '', date_to: '', zone_id: '', status: '' },
            currentPage: 1,
            totalPages: 1,
            historyTotal: 0,
            historyStats: { totalData: 0, totalTime: 0, avgTime: 0 },
            refreshInterval: null,
            autoRefresh: true,
            loading: false,
            loadingHistory: false,
            showDisconnectAllModal: false,
            disconnectingAll: false,

            async init() {
                await this.loadZones();
                await this.loadActiveSessions();
                if (this.autoRefresh) {
                    this.startAutoRefresh();
                }
            },

            async loadZones() {
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data || [];
                } catch (error) {
                    console.error('Erreur chargement zones', error);
                }
            },

            toggleAutoRefresh() {
                if (this.autoRefresh) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            },

            startAutoRefresh() {
                this.stopAutoRefresh();
                this.refreshInterval = setInterval(() => {
                    if (this.tab === 'active') this.loadActiveSessions();
                }, 10000);
            },

            stopAutoRefresh() {
                if (this.refreshInterval) {
                    clearInterval(this.refreshInterval);
                    this.refreshInterval = null;
                }
            },

            async loadActiveSessions() {
                this.loading = true;
                try {
                    const response = await API.get('/sessions/active');
                    this.activeSessions = response.data;
                } catch (error) {
                    showToast(__('session.msg_load_error'), 'error');
                } finally {
                    this.loading = false;
                }
            },

            async loadHistory() {
                this.loadingHistory = true;
                try {
                    let url = `/sessions?page=${this.currentPage}`;
                    if (this.filters.username) url += `&username=${encodeURIComponent(this.filters.username)}`;
                    if (this.filters.date_from) url += `&date_from=${this.filters.date_from}`;
                    if (this.filters.date_to) url += `&date_to=${this.filters.date_to}`;
                    if (this.filters.zone_id) url += `&zone_id=${this.filters.zone_id}`;
                    if (this.filters.status) url += `&status=${this.filters.status}`;

                    const response = await API.get(url);
                    this.history = response.data.data;
                    this.totalPages = response.data.total_pages;
                    this.historyTotal = response.data.total;

                    // Calculer les statistiques
                    this.calculateHistoryStats();
                } catch (error) {
                    showToast(__('session.msg_history_error'), 'error');
                } finally {
                    this.loadingHistory = false;
                }
            },

            calculateHistoryStats() {
                let totalData = 0;
                let totalTime = 0;
                this.history.forEach(s => {
                    totalData += (s.input_octets || 0) + (s.output_octets || 0);
                    totalTime += s.session_time || 0;
                });
                this.historyStats = {
                    totalData,
                    totalTime,
                    avgTime: this.history.length > 0 ? Math.round(totalTime / this.history.length) : 0
                };
            },

            resetFilters() {
                this.filters = { username: '', date_from: '', date_to: '', zone_id: '', status: '' };
                this.currentPage = 1;
                this.loadHistory();
            },

            async exportHistory(format) {
                try {
                    showToast('Préparation de l\'export', 'info');
                    
                    // Fetch all data matching current filters, ignoring pagination
                    let url = `/sessions?per_page=1000000`;
                    if (this.filters.username) url += `&username=${encodeURIComponent(this.filters.username)}`;
                    if (this.filters.date_from) url += `&date_from=${this.filters.date_from}`;
                    if (this.filters.date_to) url += `&date_to=${this.filters.date_to}`;
                    if (this.filters.zone_id) url += `&zone_id=${this.filters.zone_id}`;
                    if (this.filters.status) url += `&status=${this.filters.status}`;

                    const response = await API.get(url);
                    const data = response.data.data;
                    
                    if (!data || data.length === 0) {
                        showToast(__('session.empty_history'), 'warning');
                        return;
                    }

                    // Format the data into simpler rows
                    const headers = [
                        'Date Connexion', 'Date Fin', 'Voucher', 'Profil',
                        'IP Client', 'MAC Client', 'Zone', 'Routeur',
                        'Durée', 'Upload (Mo)', 'Download (Mo)', 'Statut'
                    ];

                    const rows = data.map(s => {
                        const totalOctets = (s.input_octets || 0) + (s.output_octets || 0);
                        return {
                            start_time: s.start_time || '',
                            stop_time: s.stop_time || '',
                            username: s.username || '',
                            profile: s.profile_name || '',
                            client_ip: s.client_ip || '',
                            client_mac: s.client_mac || '',
                            zone: s.zone_name || '',
                            router: s.nas_name || s.nas_ip || '',
                            duration: this.formatTime(s.session_time || 0),
                            upload: ((s.input_octets || 0) / 1048576).toFixed(2),
                            download: ((s.output_octets || 0) / 1048576).toFixed(2),
                            status: s.stop_time ? this.getTerminateCauseLabel(s.terminate_cause) : __('session.connected')
                        };
                    });

                    // JSON Export
                    if (format === 'json') {
                        const blob = new Blob([JSON.stringify(rows, null, 2)], {type: 'application/json'});
                        this.downloadFile(blob, 'history_sessions.json');
                        return;
                    }

                    // CSV Export
                    if (format === 'csv') {
                        const csvContent = [
                            headers.join(','),
                            ...rows.map(row => 
                                Object.values(row).map(v => 
                                    `"${String(v).replace(/"/g, '""')}"`
                                ).join(',')
                            )
                        ].join('\n');
                        const blob = new Blob(['\uFEFF' + csvContent], {type: 'text/csv;charset=utf-8;'});
                        this.downloadFile(blob, 'history_sessions.csv');
                        return;
                    }

                    // Requires external libraries for Excel and PDF
                    if (format === 'excel') {
                        await this.loadXLSX();
                        const ws = XLSX.utils.json_to_sheet(rows);
                        // Rename headers
                        XLSX.utils.sheet_add_aoa(ws, [headers], { origin: "A1" });
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Historique");
                        XLSX.writeFile(wb, 'history_sessions.xlsx');
                        return;
                    }

                    if (format === 'pdf') {
                        await this.loadJSPDF();
                        const doc = new window.jspdf.jsPDF('landscape');
                        doc.text('Historique des Sessions', 14, 15);
                        
                        const pdfRows = rows.map(r => Object.values(r));
                        doc.autoTable({
                            head: [headers],
                            body: pdfRows,
                            startY: 20,
                            styles: { fontSize: 8 },
                            headStyles: { fillColor: [41, 128, 185] }
                        });
                        doc.save('history_sessions.pdf');
                        return;
                    }

                } catch (error) {
                    console.error('Erreur export:', error);
                    showToast('Erreur lors de l\'export', 'error');
                }
            },
            
            downloadFile(blob, filename) {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },
            
            async loadXLSX() {
                if (typeof XLSX !== 'undefined') return;
                return new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            },
            
            async loadJSPDF() {
                if (window.jspdf && window.jspdf.jsPDF && typeof window.jspdf.jsPDF.prototype.autoTable === 'function') return;
                
                if (!window.jspdf) {
                    await new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }
                
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            },

            async disconnectSession(session) {
                if (!confirmAction(__('session.msg_confirm_disconnect').replace(':code', session.voucher_code))) return;

                try {
                    const result = await API.delete(`/sessions/${session.id}`);
                    showToast(result.message || __('session.msg_disconnected'));
                    this.loadActiveSessions();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async disconnectAll() {
                this.disconnectingAll = true;
                try {
                    await API.post('/sessions/disconnect-all');
                    showToast(__('session.msg_all_disconnected'));
                    this.activeSessions = [];
                    this.showDisconnectAllModal = false;
                } catch (error) {
                    showToast(error.message, 'error');
                } finally {
                    this.disconnectingAll = false;
                }
            },

            formatBytes(bytes) { return formatBytes(bytes); },
            formatTime(seconds) { return formatTime(seconds); },

            formatDateTime(dateStr) {
                if (!dateStr) return '-';
                const date = new Date(dateStr);
                return date.toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            isExpired(validUntil) {
                if (!validUntil) return false;
                return new Date(validUntil) < new Date();
            },

            getRemainingTime(validUntil) {
                if (!validUntil) return '-';
                const now = new Date();
                const expires = new Date(validUntil);
                const diffMs = expires - now;
                if (diffMs <= 0) return __('session.expired');
                const diffSeconds = Math.floor(diffMs / 1000);
                return formatTime(diffSeconds);
            },

            getTerminateCauseLabel(cause) {
                const labels = {
                    'User-Request': __('session.terminate_user_request'),
                    'Session-Timeout': __('session.terminate_session_timeout'),
                    'Idle-Timeout': __('session.terminate_idle_timeout'),
                    'Admin-Reset': __('session.terminate_admin_reset'),
                    'NAS-Reboot': __('session.terminate_nas_reboot'),
                    'NAS-Request': __('session.terminate_nas_request'),
                    'NAS-Sync': __('session.terminate_nas_sync'),
                    'Lost-Carrier': __('session.terminate_lost_carrier'),
                    'Port-Error': __('session.terminate_port_error')
                };
                return labels[cause] || cause || __('session.terminate_default');
            },

            getTerminateCauseClass(cause) {
                const classes = {
                    'User-Request': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                    'Session-Timeout': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                    'Idle-Timeout': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'Admin-Reset': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'NAS-Reboot': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                    'NAS-Request': 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300',
                    'NAS-Sync': 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300',
                    'Lost-Carrier': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'Port-Error': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                };
                return classes[cause] || 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300';
            }
        }
    }
</script>