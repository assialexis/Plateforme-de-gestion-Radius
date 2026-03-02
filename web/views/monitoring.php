<?php
$pageTitle = __('monitoring.title');
$currentPage = 'monitoring';
?>

<div x-data="monitoringPage()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <?= __('monitoring.title')?>
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    <?= __('monitoring.subtitle')?>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span class="w-2 h-2 rounded-full"
                        :class="autoRefresh ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"></span>
                    <span x-text="autoRefresh ? __('monitoring.auto_refresh') : __('monitoring.pause')"></span>
                </span>
                <button @click="autoRefresh = !autoRefresh"
                    class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="autoRefresh ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-[#21262d] dark:text-gray-300'">
                    <span x-text="autoRefresh ? __('monitoring.pause') : __('monitoring.resume')"></span>
                </button>
                <button @click="refreshAll()"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <?= __('monitoring.refresh')?>
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <!-- Sessions actives -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm dark:shadow-none p-6 border border-gray-200/60 dark:border-[#30363d] relative overflow-hidden group hover:border-primary-500/40 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-emerald-400/20 to-green-500/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-700">
            </div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <?= __('monitoring.active_sessions')?>
                    </p>
                    <div class="flex items-baseline gap-2 mt-2">
                        <p class="text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight"
                            x-text="stats.active_sessions || 0"></p>
                    </div>
                </div>
                <div
                    class="p-3.5 rounded-2xl bg-gradient-to-br from-emerald-400 to-green-600 text-white shadow-lg shadow-green-500/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
            <div
                class="relative mt-5 flex items-center justify-between text-sm bg-gray-50 dark:bg-[#21262d]/50 py-2.5 px-3.5 rounded-xl border border-gray-100 dark:border-[#30363d]/50">
                <span class="font-bold text-gray-600 dark:text-gray-300 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]"></span>
                    <span x-text="stats.pppoe_sessions || 0"></span> <span
                        class="text-xs font-medium text-gray-400">PPPoE</span>
                </span>
                <span class="font-bold text-gray-600 dark:text-gray-300 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></span>
                    <span x-text="stats.hotspot_sessions || 0"></span> <span
                        class="text-xs font-medium text-gray-400">Hotspot</span>
                </span>
            </div>
        </div>

        <!-- Download Total (24h) -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm dark:shadow-none p-6 border border-gray-200/60 dark:border-[#30363d] relative overflow-hidden group hover:border-green-500/40 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-green-400/20 to-emerald-500/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-700">
            </div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <?= __('monitoring.download_24h')?>
                    </p>
                    <p class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-500 dark:from-green-400 dark:to-emerald-300 mt-2 tracking-tight"
                        x-text="formatBytes(stats.download_24h || 0)"></p>
                </div>
                <div
                    class="p-3.5 rounded-2xl bg-gradient-to-br from-green-400 to-emerald-600 text-white shadow-lg shadow-green-500/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </div>
            </div>
            <div class="relative mt-6">
                <div class="flex justify-between text-xs text-gray-500 font-bold mb-2">
                    <span class="uppercase tracking-wider">Ratio Total</span>
                    <span x-text="Math.round((stats.download_24h / (stats.total_24h || 1)) * 100) + '%'"></span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-1.5 rounded-full transition-all duration-1000 origin-left"
                        :style="'width: ' + Math.min(100, (stats.download_24h / (stats.total_24h || 1)) * 100) + '%'">
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Total (24h) -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm dark:shadow-none p-6 border border-gray-200/60 dark:border-[#30363d] relative overflow-hidden group hover:border-blue-500/40 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-blue-400/20 to-indigo-500/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-700">
            </div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <?= __('monitoring.upload_24h')?>
                    </p>
                    <p class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-500 dark:from-blue-400 dark:to-indigo-300 mt-2 tracking-tight"
                        x-text="formatBytes(stats.upload_24h || 0)"></p>
                </div>
                <div
                    class="p-3.5 rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 text-white shadow-lg shadow-blue-500/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
            </div>
            <div class="relative mt-6">
                <div class="flex justify-between text-xs text-gray-500 font-bold mb-2">
                    <span class="uppercase tracking-wider">Ratio Total</span>
                    <span x-text="Math.round((stats.upload_24h / (stats.total_24h || 1)) * 100) + '%'"></span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-sky-400 to-blue-500 h-1.5 rounded-full transition-all duration-1000 origin-left"
                        :style="'width: ' + Math.min(100, (stats.upload_24h / (stats.total_24h || 1)) * 100) + '%'">
                    </div>
                </div>
            </div>
        </div>

        <!-- Total (24h) -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm dark:shadow-none p-6 border border-gray-200/60 dark:border-[#30363d] relative overflow-hidden group hover:border-purple-500/40 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-32 h-32 bg-gradient-to-br from-purple-400/20 to-fuchsia-500/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-700">
            </div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <?= __('monitoring.total_24h')?>
                    </p>
                    <p class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-fuchsia-500 dark:from-purple-400 dark:to-fuchsia-400 mt-2 tracking-tight"
                        x-text="formatBytes(stats.total_24h || 0)"></p>
                </div>
                <div
                    class="p-3.5 rounded-2xl bg-gradient-to-br from-purple-500 to-fuchsia-600 text-white shadow-lg shadow-purple-500/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div
                class="relative mt-5 flex items-center justify-between text-sm bg-gray-50 dark:bg-[#21262d]/50 py-2.5 px-3.5 rounded-xl border border-gray-100 dark:border-[#30363d]/50">
                <span class="font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                    <span
                        class="p-1 rounded bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400"><svg
                            class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg></span>
                    <span x-text="stats.unique_users_24h || 0"></span> <span
                        class="text-xs text-gray-400 font-medium">Users</span>
                </span>
                <span class="font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                    <span x-text="stats.sessions_24h || 0"></span> <span
                        class="text-xs text-gray-400 font-medium">Sess.</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Top Consumers -->
        <div
            class="lg:col-span-2 bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d]">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                    </svg>
                    <?= __('monitoring.top_consumers')?>
                </h3>
                <select x-model="topUsersLimit" @change="loadTopUsers()"
                    class="text-sm border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    <option value="10">
                        <?= __('monitoring.top_10')?>
                    </option>
                    <option value="20">
                        <?= __('monitoring.top_20')?>
                    </option>
                    <option value="50">
                        <?= __('monitoring.top_50')?>
                    </option>
                </select>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr
                                class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="pb-3">#</th>
                                <th class="pb-3">
                                    <?= __('monitoring.user')?>
                                </th>
                                <th class="pb-3">
                                    <?= __('monitoring.type')?>
                                </th>
                                <th class="pb-3 text-right">
                                    <?= __('monitoring.download')?>
                                </th>
                                <th class="pb-3 text-right">
                                    <?= __('monitoring.upload')?>
                                </th>
                                <th class="pb-3 text-right">
                                    <?= __('monitoring.total')?>
                                </th>
                                <th class="pb-3">
                                    <?= __('monitoring.chart')?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                            <template x-for="(user, index) in topUsers" :key="user.username + '_' + user.type">
                                <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                    <td class="py-3 text-sm text-gray-500 dark:text-gray-400" x-text="index + 1"></td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-medium"
                                                :class="index < 3 ? 'bg-gradient-to-br from-orange-400 to-red-500' : 'bg-gray-400'">
                                                <span
                                                    x-text="String(user.username || '?').charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white"
                                                    x-text="user.username"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400"
                                                    x-text="user.customer_name || '-'"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 text-xs rounded-full"
                                            :class="user.type === 'pppoe' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                                            x-text="user.type === 'pppoe' ? 'PPPoE' : 'Hotspot'"></span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <span class="text-sm font-medium text-green-600 dark:text-green-400"
                                            x-text="formatBytes(user.download)"></span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400"
                                            x-text="formatBytes(user.upload)"></span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white"
                                            x-text="formatBytes(user.total)"></span>
                                    </td>
                                    <td class="py-3 w-32">
                                        <div class="flex gap-0.5 h-4">
                                            <div class="bg-green-500 rounded-l"
                                                :style="'width: ' + (user.download / user.total * 100) + '%'"></div>
                                            <div class="bg-blue-500 rounded-r"
                                                :style="'width: ' + (user.upload / user.total * 100) + '%'"></div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="topUsers.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <?= __('monitoring.no_data')?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Sessions -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] flex flex-col h-full max-h-[800px] lg:max-h-none">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <?= __('monitoring.live_sessions')?>
                </h3>
            </div>
            <div class="p-4 flex-1 overflow-y-auto min-h-0 lg:h-0">
                <div class="space-y-3">
                    <template x-for="session in liveSessions.slice(0, 50)"
                        :key="session.session_id + '_' + session.type">
                        <div class="p-3 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white"
                                    x-text="session.username"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                    :class="session.type === 'pppoe' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                                    x-text="session.type === 'pppoe' ? 'PPPoE' : 'Hotspot'"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        <?= __('monitoring.download')?>:
                                    </span>
                                    <span class="text-green-600 dark:text-green-400 font-medium ml-1"
                                        x-text="formatBytes(session.download)"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        <?= __('monitoring.upload')?>:
                                    </span>
                                    <span class="text-blue-600 dark:text-blue-400 font-medium ml-1"
                                        x-text="formatBytes(session.upload)"></span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        <?= __('monitoring.duration')?>:
                                    </span>
                                    <span class="text-gray-700 dark:text-gray-300 ml-1"
                                        x-text="formatDuration(session.duration_seconds)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="liveSessions.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <?= __('monitoring.no_active_session')?>
                    </div>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 dark:border-[#30363d] text-center">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <span x-text="liveSessions.length"></span>
                    <?= __('monitoring.active_sessions')?>
                </span>
            </div>
        </div>
    </div>

    <!-- Consumption History Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Hourly Chart -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d]">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?= __('monitoring.hourly_chart')?>
                </h3>
            </div>
            <div class="p-6">
                <!-- Chart Area -->
                <div class="relative h-64 pt-4">
                    <!-- Grid background -->
                    <div
                        class="absolute inset-0 flex flex-col justify-between pointer-events-none border-b border-gray-100 dark:border-gray-800">
                        <div class="w-full h-px border-t border-dashed border-gray-200 dark:border-gray-700/50"></div>
                        <div class="w-full h-px border-t border-dashed border-gray-200 dark:border-gray-700/50"></div>
                        <div class="w-full h-px border-t border-dashed border-gray-200 dark:border-gray-700/50"></div>
                        <div class="w-full h-px border-t border-dashed border-gray-200 dark:border-gray-700/50"></div>
                        <div class="w-full h-px"></div>
                    </div>

                    <div class="relative h-full flex items-end gap-1.5 z-10">
                        <template x-for="(hour, index) in hourlyStats" :key="index">
                            <div
                                class="flex-1 flex flex-col items-center justify-end h-full group relative cursor-pointer">
                                <!-- Tooltip -->
                                <div
                                    class="absolute bottom-full mb-2 opacity-0 group-hover:opacity-100 transition-opacity z-20 w-32 left-1/2 -translate-x-1/2 pointer-events-none scale-95 group-hover:scale-100 duration-200">
                                    <div
                                        class="bg-gray-900 dark:bg-[#21262d] text-white text-[10px] rounded-lg p-2.5 shadow-xl border border-gray-700">
                                        <div class="text-center font-bold mb-1.5 pb-1 border-b border-gray-700 text-gray-300"
                                            x-text="hour.hour + ':00'"></div>
                                        <div class="flex justify-between items-center text-green-400">
                                            <span><svg class="w-3.5 h-3.5 inline mr-0.5 pb-0.5" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg> Down</span>
                                            <span class="font-bold font-mono"
                                                x-text="formatBytesShort(hour.download)"></span>
                                        </div>
                                        <div class="flex justify-between items-center text-sky-400 mt-1">
                                            <span><svg class="w-3.5 h-3.5 inline mr-0.5 pb-0.5" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg> Up</span>
                                            <span class="font-bold font-mono"
                                                x-text="formatBytesShort(hour.upload)"></span>
                                        </div>
                                    </div>
                                    <div
                                        class="w-2 h-2 bg-gray-900 dark:bg-[#21262d] rotate-45 mx-auto -mt-1 border-r border-b border-gray-700">
                                    </div>
                                </div>

                                <div class="w-full max-w-[20px] flex flex-col gap-0.5 relative group-hover:-translate-y-1 transition-transform duration-300"
                                    :style="'height: ' + (hour.total > 0 ? Math.max(4, (hour.total / maxHourlyTotal) * 100) : 1) + '%'">
                                    <div class="w-full bg-gradient-to-t from-emerald-500 to-green-400 rounded-t-lg shadow-sm w-full transition-all group-hover:brightness-110"
                                        :style="'height: ' + (hour.download / (hour.total || 1) * 100) + '%'"></div>
                                    <div class="w-full bg-gradient-to-t from-blue-600 to-sky-400 rounded-b-lg shadow-sm w-full transition-all group-hover:brightness-110"
                                        :style="'height: ' + (hour.upload / (hour.total || 1) * 100) + '%'"></div>
                                </div>
                                <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 mt-2"
                                    x-text="hour.hour + 'h'"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <!-- Legend -->
                <div class="flex items-center justify-center gap-8 mt-6">
                    <div class="flex items-center gap-2">
                        <span
                            class="w-3 h-3 bg-gradient-to-tr from-emerald-500 to-green-400 rounded-full shadow-sm"></span>
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            <?= __('monitoring.download')?>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-gradient-to-tr from-blue-600 to-sky-400 rounded-full shadow-sm"></span>
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            <?= __('monitoring.upload')?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Chart (7 days) -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d]">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?= __('monitoring.daily_chart')?>
                </h3>
            </div>
            <div class="p-6">
                <!-- Chart Area -->
                <div class="relative h-64 pt-4 pb-2">
                    <!-- Grid background -->
                    <div
                        class="absolute inset-0 flex flex-col justify-between pointer-events-none border-b border-gray-100 dark:border-gray-800">
                        <div class="w-full h-px border-t border-dashed border-gray-200 dark:border-gray-700/50"></div>
                        <div class="w-full h-px border-t border-dashed border-gray-200 dark:border-gray-700/50"></div>
                        <div class="w-full h-px border-t border-dashed border-gray-200 dark:border-gray-700/50"></div>
                        <div class="w-full h-px border-t border-dashed border-gray-200 dark:border-gray-700/50"></div>
                        <div class="w-full h-px"></div>
                    </div>

                    <div class="relative h-full flex items-end gap-1 z-10 px-2">
                        <template x-for="(day, index) in dailyStats" :key="index">
                            <div
                                class="flex-1 flex flex-col items-center justify-end h-full group relative cursor-pointer">
                                <!-- Tooltip -->
                                <div
                                    class="absolute bottom-full mb-2 opacity-0 group-hover:opacity-100 transition-opacity z-20 w-32 left-1/2 -translate-x-1/2 pointer-events-none scale-95 group-hover:scale-100 duration-200">
                                    <div
                                        class="bg-gray-900 dark:bg-[#21262d] text-white text-[10px] rounded-lg p-2.5 shadow-xl border border-gray-700">
                                        <div class="text-center font-bold mb-1.5 pb-1 border-b border-gray-700 text-gray-300"
                                            x-text="day.label"></div>
                                        <div class="flex justify-between items-center text-green-400">
                                            <span><svg class="w-3.5 h-3.5 inline mr-0.5 pb-0.5" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg> Down</span>
                                            <span class="font-bold font-mono"
                                                x-text="formatBytesShort(day.download)"></span>
                                        </div>
                                        <div class="flex justify-between items-center text-sky-400 mt-1">
                                            <span><svg class="w-3.5 h-3.5 inline mr-0.5 pb-0.5" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg> Up</span>
                                            <span class="font-bold font-mono"
                                                x-text="formatBytesShort(day.upload)"></span>
                                        </div>
                                    </div>
                                    <div
                                        class="w-2 h-2 bg-gray-900 dark:bg-[#21262d] rotate-45 mx-auto -mt-1 border-r border-b border-gray-700">
                                    </div>
                                </div>

                                <div class="w-full max-w-[32px] flex flex-col gap-0.5 relative group-hover:-translate-y-1 transition-transform duration-300"
                                    :style="'height: ' + (day.total > 0 ? Math.max(4, (day.total / maxDailyTotal) * 100) : 1) + '%'">
                                    <div class="w-full bg-gradient-to-t from-emerald-500 to-green-400 rounded-t-lg shadow-sm transition-all group-hover:brightness-110"
                                        :style="'height: ' + (day.download / (day.total || 1) * 100) + '%'"></div>
                                    <div class="w-full bg-gradient-to-t from-blue-600 to-sky-400 rounded-b-lg shadow-sm transition-all group-hover:brightness-110"
                                        :style="'height: ' + (day.upload / (day.total || 1) * 100) + '%'"></div>
                                </div>
                                <div class="mt-3 flex flex-col items-center">
                                    <span
                                        class="text-[10px] font-bold text-gray-400 group-hover:text-blue-500 transition-colors whitespace-nowrap"
                                        x-text="day.label"></span>
                                    <span class="text-[9px] font-bold text-gray-500 dark:text-gray-400"
                                        x-text="formatBytesShort(day.total)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FUP Alerts -->
    <div
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <?= __('monitoring.fup_alerts')?>
            </h3>
            <span
                class="px-3 py-1 text-sm rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                x-text="fupAlerts.length + ' ' + __('monitoring.users')"></span>
        </div>
        <div class="p-6">
            <div x-show="fupAlerts.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="alert in fupAlerts" :key="alert.id">
                    <div class="p-4 rounded-lg border-2"
                        :class="alert.triggered ? 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20'">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 dark:text-white" x-text="alert.username"></span>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                :class="alert.triggered ? 'bg-red-200 text-red-700 dark:bg-red-800 dark:text-red-300' : 'bg-amber-200 text-amber-700 dark:bg-amber-800 dark:text-amber-300'"
                                x-text="alert.triggered ? __('monitoring.fup_active') : __('monitoring.near_limit')"></span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">
                                    <?= __('monitoring.consumption')?>:
                                </span>
                                <span class="font-medium"
                                    x-text="formatBytes(alert.fup_data_used || 0) + ' / ' + formatBytes((alert.fup_quota || 0) * 1073741824)"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-[#21262d] rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                    :class="(alert.usage_percent || 0) >= 100 ? 'bg-red-500' : (alert.usage_percent || 0) >= 80 ? 'bg-amber-500' : 'bg-green-500'"
                                    :style="'width: ' + Math.min(100, (alert.usage_percent || 0)) + '%'"></div>
                            </div>
                            <div class="text-right text-sm font-medium"
                                :class="(alert.usage_percent || 0) >= 100 ? 'text-red-600' : 'text-amber-600'"
                                x-text="parseFloat(alert.usage_percent || 0).toFixed(1) + '%'"></div>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="fupAlerts.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>
                    <?= __('monitoring.no_fup_alert')?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function monitoringPage() {
        return {
            loading: false,
            autoRefresh: true,
            refreshInterval: null,
            lastUpdated: null,

            stats: {},
            topUsers: [],
            topUsersLimit: 10,
            liveSessions: [],
            hourlyStats: [],
            dailyStats: [],
            fupAlerts: [],

            maxHourlyTotal: 1,
            maxDailyTotal: 1,

            async init() {
                await this.refreshAll();

                // Auto-refresh every 30 seconds
                this.refreshInterval = setInterval(() => {
                    if (this.autoRefresh) {
                        this.refreshAll();
                    }
                }, 30000);
            },

            async refreshAll() {
                this.loading = true;
                try {
                    await Promise.all([
                        this.loadStats(),
                        this.loadTopUsers(),
                        this.loadLiveSessions(),
                        this.loadHourlyStats(),
                        this.loadDailyStats(),
                        this.loadFupAlerts()
                    ]);
                } catch (error) {
                    console.error('Error refreshing data:', error);
                } finally {
                    this.loading = false;
                    this.lastUpdated = new Date();
                }
            },

            async loadStats() {
                try {
                    const response = await API.get('/monitoring/stats');
                    this.stats = response.data || {};
                } catch (error) {
                    console.error('Error loading stats:', error);
                }
            },

            async loadTopUsers() {
                try {
                    const response = await API.get('/monitoring/top-users?limit=' + this.topUsersLimit);
                    this.topUsers = response.data || [];
                } catch (error) {
                    console.error('Error loading top users:', error);
                }
            },

            async loadLiveSessions() {
                try {
                    const response = await API.get('/monitoring/live-sessions');
                    this.liveSessions = response.data || [];
                } catch (error) {
                    console.error('Error loading live sessions:', error);
                }
            },

            async loadHourlyStats() {
                try {
                    const response = await API.get('/monitoring/hourly-stats');
                    this.hourlyStats = response.data || [];
                    this.maxHourlyTotal = Math.max(...this.hourlyStats.map(h => h.total || 0), 1);
                } catch (error) {
                    console.error('Error loading hourly stats:', error);
                }
            },

            async loadDailyStats() {
                try {
                    const response = await API.get('/monitoring/daily-stats');
                    this.dailyStats = response.data || [];
                    this.maxDailyTotal = Math.max(...this.dailyStats.map(d => d.total || 0), 1);
                } catch (error) {
                    console.error('Error loading daily stats:', error);
                }
            },

            async loadFupAlerts() {
                try {
                    const response = await API.get('/monitoring/fup-alerts');
                    this.fupAlerts = response.data || [];
                } catch (error) {
                    console.error('Error loading FUP alerts:', error);
                }
            },

            formatBytes(bytes) {
                if (!bytes || bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },

            formatBytesShort(bytes) {
                if (!bytes || bytes === 0) return '0';
                const k = 1024;
                const sizes = ['B', 'K', 'M', 'G', 'T'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + sizes[i];
            },

            formatDuration(seconds) {
                if (!seconds) return '0s';
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                if (hours > 0) {
                    return hours + 'h ' + minutes + 'm';
                }
                return minutes + 'm';
            }
        };
    }
</script>