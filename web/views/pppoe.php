<?php $pageTitle = __('page.pppoe');
$currentPage = 'pppoe'; ?>

<div x-data="pppoePage()" x-init="init()">
    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <div class="flex items-center justify-between">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button @click="activeTab = 'users'"
                    :class="activeTab === 'users' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <?= __('pppoe.clients')?>
                </button>
            </li>
            <li class="mr-2">
                <button @click="activeTab = 'profiles'"
                    :class="activeTab === 'profiles' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <?= __('pppoe.profiles')?>
                </button>
            </li>
            <li class="mr-2">
                <button @click="activeTab = 'sessions'; loadSessions()"
                    :class="activeTab === 'sessions' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                    </svg>
                    <?= __('pppoe.sessions')?>
                    <span x-show="sessionStats.active_sessions > 0"
                        class="ml-2 px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 rounded-full"
                        x-text="sessionStats.active_sessions"></span>
                </button>
            </li>
        </ul>
        <a href="client-login.php?admin=<?= $currentUser->getId() ?>" target="_blank"
            class="inline-flex items-center gap-2 px-4 py-2 mb-1 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Portail Client
            <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
        <!-- Total -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all duration-500">
            </div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe.total_clients')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="stats.total_users || 0"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-[0_0_15px_rgba(59,130,246,0.3)] group-hover:shadow-[0_0_20px_rgba(59,130,246,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-green-500/10 dark:bg-green-500/5 rounded-full blur-2xl group-hover:bg-green-500/20 transition-all duration-500">
            </div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe.active')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="stats.active_users || 0"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-green-500 shadow-[0_0_15px_rgba(52,211,153,0.3)] group-hover:shadow-[0_0_20px_rgba(52,211,153,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-orange-500/10 dark:bg-orange-500/5 rounded-full blur-2xl group-hover:bg-orange-500/20 transition-all duration-500">
            </div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe.active_sessions')?>
                    </p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-3xl font-black text-gray-900 dark:text-white"
                            x-text="stats.active_sessions || 0"></p>
                        <span class="text-xs font-bold text-orange-500 animate-pulse">● Live</span>
                    </div>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-500 shadow-[0_0_15px_rgba(249,115,22,0.3)] group-hover:shadow-[0_0_20px_rgba(249,115,22,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Expired -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/10 dark:bg-red-500/5 rounded-full blur-2xl group-hover:bg-red-500/20 transition-all duration-500">
            </div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe.expired')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="stats.expired_users || 0"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 shadow-[0_0_15px_rgba(239,68,68,0.3)] group-hover:shadow-[0_0_20px_rgba(239,68,68,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Users -->
    <div x-show="activeTab === 'users'">
        <!-- Header avec actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Recherche -->
                <div class="relative">
                    <input type="text" x-model="search" @input.debounce.300ms="loadUsers()"
                        placeholder="<?= __('pppoe.search')?>"
                        class="pl-10 pr-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Filtre statut -->
                <select x-model="statusFilter" @change="loadUsers()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    <option value="">
                        <?= __('pppoe.all_statuses')?>
                    </option>
                    <option value="active">
                        <?= __('pppoe.active_status')?>
                    </option>
                    <option value="suspended">
                        <?= __('pppoe.suspended_status')?>
                    </option>
                    <option value="expired">
                        <?= __('pppoe.expired_status')?>
                    </option>
                    <option value="disabled">
                        <?= __('pppoe.disabled_status')?>
                    </option>
                </select>

                <!-- Filtre profil -->
                <select x-model="profileFilter" @change="loadUsers()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    <option value="">
                        <?= __('pppoe.all_profiles')?>
                    </option>
                    <template x-for="profile in profiles" :key="profile.id">
                        <option :value="profile.id" x-text="profile.name"></option>
                    </template>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <!-- Bouton créer -->
                <a href="index.php?page=pppoe-user"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?= __('pppoe.new_client')?>
                </a>

                <!-- Bouton générer en lot -->
                <button @click="showBatchModal = true"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <?= __('pppoe.generate')?>
                </button>
            </div>
        </div>

        <!-- Table des utilisateurs PPPoE -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.table_client')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.table_username')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.table_profile')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.table_status')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.table_expiration')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.table_sessions')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.table_data')?>
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.table_actions')?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                        <template x-if="loading">
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex justify-center">
                                        <svg class="animate-spin h-8 w-8 text-primary-600" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && users.length === 0">
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-500" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p>
                                        <?= __('pppoe.empty_clients')?>
                                    </p>
                                </td>
                            </tr>
                        </template>
                        <template x-for="(user, index) in users" :key="user.id">
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-[#30363d]/30 transition-colors group">
                                <!-- Client Info -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-sm"
                                            :class="(user.id || index) % 3 === 0 ? 'bg-gradient-to-br from-indigo-500 to-indigo-600' : (user.id || index) % 2 === 0 ? 'bg-gradient-to-br from-pink-500 to-rose-500' : 'bg-gradient-to-br from-teal-500 to-emerald-500'"
                                            x-text="(user.customer_name || user.username || 'U')[0].toUpperCase()">
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                                                x-text="user.customer_name || '-'"></p>
                                            <p class="text-xs font-medium text-gray-500"
                                                x-text="user.customer_phone || ''"></p>
                                        </div>
                                    </div>
                                </td>
                                <!-- Username -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span
                                            class="font-mono font-bold px-2 py-1 bg-gray-100 dark:bg-[#161b22] border border-gray-200 dark:border-[#30363d] rounded-md text-gray-800 dark:text-gray-200 text-xs"
                                            x-text="user.username"></span>
                                        <button @click="copyToClipboard(user.username)"
                                            class="ml-2 text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                            title="<?= __('pppoe.copy')?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <!-- Profil -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white"
                                            x-text="user.profile_name"></p>
                                        <p class="text-[11px] font-bold text-gray-500"
                                            x-text="formatSpeed(user.download_speed) + ' ↓ / ' + formatSpeed(user.upload_speed) + ' ↑'">
                                        </p>
                                    </div>
                                </td>
                                <!-- Statut -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border text-xs font-bold uppercase tracking-wide"
                                        :class="getStatusClass(user.status)"
                                        x-text="getStatusLabel(user.status)"></span>
                                </td>
                                <!-- Expiration -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white"
                                            x-text="formatDate(user.valid_until)"></p>
                                        <p class="text-xs font-bold"
                                            :class="isExpiringSoon(user.valid_until) ? 'text-red-500' : 'text-gray-400'"
                                            x-text="getDaysRemaining(user.valid_until)"></p>
                                    </div>
                                </td>
                                <!-- Sessions -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md border text-xs font-extrabold shadow-sm"
                                        :class="user.active_sessions > 0 ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/30 dark:border-green-800/50 dark:text-green-400' : 'bg-gray-50 border-gray-200 text-gray-500 dark:bg-[#21262d] dark:border-[#30363d] dark:text-gray-400'">
                                        <span x-show="user.active_sessions > 0"
                                            class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                                        <span x-text="user.active_sessions || 0"></span>
                                    </span>
                                </td>
                                <!-- Data Usage -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div x-show="user.profile_data_limit > 0">
                                        <div class="text-[11px] font-bold text-gray-500 dark:text-gray-400 flex justify-between"
                                            style="width: 80px;">
                                            <span x-text="formatBytes(user.data_used)"></span>
                                            <span x-text="formatBytes(user.profile_data_limit)"></span>
                                        </div>
                                        <div
                                            class="w-20 bg-gray-100 dark:bg-[#161b22] rounded-full h-1.5 mt-1 border border-gray-200/50 dark:border-[#30363d] overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="getDataUsagePercent(user) >= 90 ? 'bg-red-500' : getDataUsagePercent(user) >= 75 ? 'bg-amber-400' : 'bg-gradient-to-r from-green-400 to-green-500'"
                                                :style="'width: ' + Math.min(100, getDataUsagePercent(user)) + '%'">
                                            </div>
                                        </div>
                                    </div>
                                    <span x-show="!user.profile_data_limit || user.profile_data_limit == 0"
                                        class="text-xs font-bold text-gray-400 dark:text-gray-500 px-2 py-0.5 border border-gray-100 dark:border-gray-800 rounded-md bg-gray-50 dark:bg-[#161b22]/50">∞</span>
                                </td>
                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div
                                        class="inline-flex items-center p-1 bg-white dark:bg-[#21262d] rounded-lg border border-gray-200 dark:border-[#30363d] shadow-sm">
                                        <button @click="renewUser(user)"
                                            class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors"
                                            :title="__('pppoe.renew')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <div class="w-px h-4 bg-gray-200 dark:bg-[#30363d] mx-1"></div>
                                        <a :href="'index.php?page=pppoe-user&id=' + user.id"
                                            class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-md transition-colors"
                                            :title="__('pppoe.modify')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <template x-if="user.status === 'active' || user.status === 'suspended'">
                                            <div class="w-px h-4 bg-gray-200 dark:bg-[#30363d] mx-1"></div>
                                        </template>
                                        <button x-show="user.status === 'active'" @click="suspendUser(user)"
                                            class="p-1.5 text-gray-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-md transition-colors"
                                            :title="__('pppoe.suspend')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <button x-show="user.status === 'suspended'" @click="activateUser(user)"
                                            class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors"
                                            :title="__('pppoe.activate')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <div class="w-px h-4 bg-gray-200 dark:bg-[#30363d] mx-1"></div>
                                        <button @click="deleteUser(user)"
                                            class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
                                            :title="__('pppoe.delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                class="px-6 py-3 bg-gray-50 dark:bg-[#21262d]/50 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <span
                        x-text="__('pppoe.page_x_of_y').replace(':current', currentPage).replace(':total', totalPages)"></span>
                    <span x-text="__('pppoe.clients_count_label').replace(':count', totalItems)"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="prevPage()" :disabled="currentPage <= 1"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <?= __('pppoe.previous')?>
                    </button>
                    <button @click="nextPage()" :disabled="currentPage >= totalPages"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <?= __('pppoe.next')?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Profiles -->
    <div x-show="activeTab === 'profiles'">
        <div class="flex justify-end mb-4">
            <button @click="openCreateProfileModal()"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('pppoe.new_profile')?>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <template x-for="profile in profiles" :key="profile.id">
                <div
                    class="relative bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-100 dark:border-[#30363d] group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-gray-50/50 to-white dark:from-[#21262d]/50 dark:to-[#161b22] opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-0">
                    </div>
                    <div class="relative z-10 flex-1 flex flex-col">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white leading-tight truncate mr-2"
                                x-text="profile.name"></h3>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded border text-[10px] font-bold uppercase tracking-wide shrink-0"
                                :class="profile.is_active ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/30 dark:border-green-800/50 dark:text-green-400' : 'bg-gray-50 border-gray-200 text-gray-500 dark:bg-[#21262d] dark:border-[#30363d] dark:text-gray-400'"
                                x-text="profile.is_active ? __('pppoe.profile_active_label') : __('pppoe.profile_inactive_label')"></span>
                        </div>

                        <p class="text-[12px] text-gray-500 dark:text-gray-400 mb-4 line-clamp-1"
                            x-text="profile.description || __('pppoe.no_description')"></p>

                        <div class="grid grid-cols-2 gap-2 text-[11px] mb-4 flex-1">
                            <div
                                class="bg-gray-50 dark:bg-[#21262d] p-2.5 rounded-lg border border-gray-100 dark:border-[#30363d]">
                                <p class="text-gray-500 dark:text-gray-400 font-semibold mb-0.5"
                                    x-text="__('pppoe.speed')"></p>
                                <p class="font-bold text-gray-900 dark:text-white truncate"
                                    x-text="formatSpeed(profile.download_speed) + ' ↓ / ' + formatSpeed(profile.upload_speed) + ' ↑'">
                                </p>
                            </div>
                            <div
                                class="bg-gray-50 dark:bg-[#21262d] p-2.5 rounded-lg border border-gray-100 dark:border-[#30363d]">
                                <p class="text-gray-500 dark:text-gray-400 font-semibold mb-0.5"
                                    x-text="__('pppoe.price')"></p>
                                <p class="font-black text-green-600 dark:text-green-400 truncate"
                                    x-text="formatPrice(profile.price)"></p>
                            </div>
                            <div
                                class="bg-gray-50 dark:bg-[#21262d] p-2.5 rounded-lg border border-gray-100 dark:border-[#30363d]">
                                <p class="text-gray-500 dark:text-gray-400 font-semibold mb-0.5"
                                    x-text="__('pppoe.validity')"></p>
                                <p class="font-bold text-gray-900 dark:text-white"
                                    x-text="profile.validity_days + ' ' + __('pppoe.days')"></p>
                            </div>
                            <div
                                class="bg-gray-50 dark:bg-[#21262d] p-2.5 rounded-lg border border-gray-100 dark:border-[#30363d]">
                                <p class="text-gray-500 dark:text-gray-400 font-semibold mb-0.5"
                                    x-text="__('pppoe.clients_count')"></p>
                                <div class="font-bold text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span x-text="profile.users_count || 0"></span>
                                </div>
                            </div>
                        </div>

                        <!-- FUP indicator -->
                        <div x-show="profile.fup_enabled" class="mb-4">
                            <span
                                class="inline-flex w-full items-center justify-center px-2 py-2 rounded-lg border border-amber-200 text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-400">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                FUP POLICY: <span class="ml-1" x-text="formatBytes(profile.fup_quota)"></span>
                            </span>
                        </div>

                        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-[#30363d] grid grid-cols-2 gap-2">
                            <button @click="editProfile(profile)"
                                class="w-full px-2 py-2 text-primary-600 bg-primary-50 border border-primary-100 hover:bg-primary-100 dark:bg-primary-900/20 dark:border-primary-800/50 dark:hover:bg-primary-900/40 rounded-lg transition-colors font-bold text-[11px] uppercase tracking-wider"
                                x-text="__('pppoe.modify')">
                            </button>
                            <button @click="deleteProfile(profile)"
                                class="w-full px-2 py-2 text-red-600 bg-red-50 border border-red-100 hover:bg-red-100 dark:bg-red-900/20 dark:border-red-800/50 dark:hover:bg-red-900/40 rounded-lg transition-colors font-bold text-[11px] uppercase tracking-wider"
                                x-text="__('pppoe.delete')">
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Tab: Sessions -->
    <div x-show="activeTab === 'sessions'">
        <!-- Stats Sessions -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
            <!-- Active Sessions -->
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div
                    class="absolute -right-6 -top-6 w-24 h-24 bg-green-500/10 dark:bg-green-500/5 rounded-full blur-2xl group-hover:bg-green-500/20 transition-all duration-500">
                </div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            <?= __('pppoe.active_sessions')?>
                        </p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white"
                            x-text="sessionStats.active_sessions || 0"></p>
                    </div>
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-green-500 shadow-[0_0_15px_rgba(52,211,153,0.3)] group-hover:shadow-[0_0_20px_rgba(52,211,153,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Orphaned Sessions -->
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div
                    class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 dark:bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all duration-500">
                </div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            <?= __('pppoe.session_orphaned')?>
                        </p>
                        <p class="text-3xl font-black"
                            :class="sessionStats.orphaned_sessions > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-900 dark:text-white'"
                            x-text="sessionStats.orphaned_sessions || 0"></p>
                    </div>
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-[0_0_15px_rgba(251,191,36,0.3)] group-hover:shadow-[0_0_20px_rgba(251,191,36,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Sessions Today -->
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div
                    class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all duration-500">
                </div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            <?= __('pppoe.session_today')?>
                        </p>
                        <p class="text-3xl font-black text-gray-900 dark:text-white"
                            x-text="sessionStats.today_sessions || 0"></p>
                    </div>
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-500 shadow-[0_0_15px_rgba(59,130,246,0.3)] group-hover:shadow-[0_0_20px_rgba(59,130,246,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Traffic Today -->
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div
                    class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/10 dark:bg-purple-500/5 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all duration-500">
                </div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                            <?= __('pppoe.traffic_today')?>
                        </p>
                        <p class="text-3xl font-black text-purple-600 dark:text-purple-400"
                            x-text="sessionStats.today_traffic_total || '0 B'"></p>
                    </div>
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-fuchsia-500 to-purple-600 shadow-[0_0_15px_rgba(168,85,247,0.3)] group-hover:shadow-[0_0_20px_rgba(168,85,247,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions et Filtres -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Recherche -->
                <div class="relative">
                    <input type="text" x-model="sessionSearch" @input.debounce.300ms="loadSessions()"
                        placeholder="<?= __('pppoe.search')?>"
                        class="pl-10 pr-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Filtre statut -->
                <select x-model="sessionStatusFilter" @change="loadSessions()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    <option value="">
                        <?= __('pppoe.all_sessions')?>
                    </option>
                    <option value="active">
                        <?= __('pppoe.active_sessions_filter')?>
                    </option>
                    <option value="closed">
                        <?= __('pppoe.closed_sessions')?>
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <!-- Rafraîchir -->
                <button @click="loadSessions(); loadSessionStats()"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                    <svg class="w-5 h-5" :class="loadingSessions ? 'animate-spin' : ''" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>

                <!-- Nettoyer les sessions orphelines -->
                <button @click="cleanupOrphanedSessions()" x-show="sessionStats.orphaned_sessions > 0"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <?= __('pppoe.clean_orphaned')?>
                </button>

                <!-- Terminer toutes -->
                <button @click="terminateAllSessions()" x-show="sessionStats.active_sessions > 0"
                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <?= __('pppoe.terminate_all')?>
                </button>
            </div>
        </div>

        <!-- Table des sessions -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#21262d]">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.session_client')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.session_client_ip')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.session_start')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.session_duration')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.session_traffic')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.session_status')?>
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('pppoe.session_actions')?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                        <template x-if="loadingSessions">
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-8 h-8 mx-auto mb-2 animate-spin text-primary-600" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <?= __('pppoe.loading')?>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loadingSessions && sessions.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <?= __('pppoe.no_session_found')?>
                                </td>
                            </tr>
                        </template>
                        <template x-for="session in sessions" :key="session.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white"
                                            x-text="session.username"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400"
                                            x-text="session.customer_name || '-'"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm text-gray-900 dark:text-white"
                                        x-text="session.client_ip || '-'"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div x-text="formatDateTime(session.start_time)"></div>
                                    <div x-show="session.stop_time" class="text-xs text-red-500"
                                        x-text="__('pppoe.end_label') + ' ' + formatDateTime(session.stop_time)"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white"
                                        x-text="session.duration_formatted"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs space-y-1">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                            <span class="text-green-600 dark:text-green-400"
                                                x-text="session.data_in_formatted"></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                            <span class="text-blue-600 dark:text-blue-400"
                                                x-text="session.data_out_formatted"></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <template x-if="session.is_active">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                                            <?= __('pppoe.connected')?>
                                        </span>
                                    </template>
                                    <template x-if="!session.is_active">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-400">
                                            <span
                                                x-text="session.terminate_cause || __('pppoe.session_terminated')"></span>
                                        </span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <template x-if="session.is_active">
                                        <button @click="terminateSession(session)"
                                            class="inline-flex items-center px-2 py-1 text-xs text-red-600 hover:text-red-800 dark:text-red-400 border border-red-300 dark:border-red-700 rounded hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            <?= __('pppoe.terminate')?>
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                class="px-6 py-3 bg-gray-50 dark:bg-[#21262d]/50 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <span
                        x-text="__('pppoe.page_x_of_y').replace(':current', sessionPage).replace(':total', sessionTotalPages)"></span>
                    <span x-text="__('pppoe.sessions_count_label').replace(':count', sessionTotal)"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="prevSessionPage()" :disabled="sessionPage <= 1"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <?= __('pppoe.previous')?>
                    </button>
                    <button @click="nextSessionPage()" :disabled="sessionPage >= sessionTotalPages"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <?= __('pppoe.next')?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Créer/Modifier Profil -->
    <div x-show="showProfileModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showProfileModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"
                    x-text="editingProfile ? __('pppoe.profile_edit') : __('pppoe.profile_new')"></h3>

                <form @submit.prevent="saveProfile()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('pppoe.profile_name')?>
                        </label>
                        <input type="text" x-model="profileForm.name" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('pppoe.profile_description')?>
                        </label>
                        <input type="text" x-model="profileForm.description"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>

                    <!-- Politique de Bande Passante -->
                    <div
                        class="border border-primary-200 dark:border-primary-800 rounded-lg p-4 bg-primary-50 dark:bg-primary-900/20">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <?= __('pppoe.profile_bandwidth_policy')?>
                            </span>
                        </label>
                        <select x-model="profileForm.bandwidth_policy_id" @change="onBandwidthPolicyChange()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <option value="">
                                <?= __('pppoe.profile_custom_speeds')?>
                            </option>
                            <template x-for="policy in bandwidthPolicies" :key="policy.id">
                                <option :value="policy.id"
                                    x-text="policy.name + ' (' + formatSpeed(policy.download_rate) + '/' + formatSpeed(policy.upload_rate) + ')'">
                                </option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <?= __('pppoe.profile_bandwidth_hint')?>
                        </p>
                    </div>

                    <!-- Vitesses personnalisées (affichées si pas de politique sélectionnée) -->
                    <div x-show="!profileForm.bandwidth_policy_id" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('pppoe.profile_download')?>
                            </label>
                            <input type="number" x-model="profileForm.download_mbps" min="1" step="1"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('pppoe.profile_upload')?>
                            </label>
                            <input type="number" x-model="profileForm.upload_mbps" min="1" step="1"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Affichage des vitesses de la politique sélectionnée -->
                    <div x-show="profileForm.bandwidth_policy_id" class="space-y-3">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-100 dark:bg-[#21262d] rounded-lg p-3">
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Download</label>
                                <p class="text-lg font-semibold text-green-600 dark:text-green-400"
                                    x-text="formatSpeed(selectedPolicyDownload)"></p>
                            </div>
                            <div class="bg-gray-100 dark:bg-[#21262d] rounded-lg p-3">
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Upload</label>
                                <p class="text-lg font-semibold text-blue-600 dark:text-blue-400"
                                    x-text="formatSpeed(selectedPolicyUpload)"></p>
                            </div>
                        </div>
                        <!-- Affichage Burst si configuré -->
                        <div x-show="selectedPolicyBurstDownload > 0 || selectedPolicyBurstUpload > 0"
                            class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span class="text-xs font-medium text-purple-700 dark:text-purple-300">
                                    <?= __('pppoe.burst_label')?>
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Download:</span>
                                    <span class="font-medium text-purple-600 dark:text-purple-400"
                                        x-text="formatSpeed(selectedPolicyBurstDownload)"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Upload:</span>
                                    <span class="font-medium text-purple-600 dark:text-purple-400"
                                        x-text="formatSpeed(selectedPolicyBurstUpload)"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">
                                        <?= __('pppoe.burst_duration')?>
                                    </span>
                                    <span class="font-medium text-purple-600 dark:text-purple-400"
                                        x-text="selectedPolicyBurstTime + 's'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('pppoe.profile_validity_days')?>
                            </label>
                            <input type="number" x-model="profileForm.validity_days" min="1"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('pppoe.profile_price')?>
                            </label>
                            <input type="number" x-model="profileForm.price" min="0" step="100"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('pppoe.profile_simultaneous_use')?>
                        </label>
                        <input type="number" x-model="profileForm.simultaneous_use" min="1" max="10"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <?= __('pppoe.profile_simultaneous_use_hint')?>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('pppoe.profile_ip_pool')?>
                        </label>
                        <input type="text" x-model="profileForm.ip_pool_name" placeholder="ex: pppoe-pool"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('pppoe.profile_mikrotik')?>
                        </label>
                        <input type="text" x-model="profileForm.mikrotik_group" placeholder="ex: pppoe-profile-5M"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <?= __('pppoe.profile_mikrotik_hint')?>
                        </p>
                    </div>

                    <!-- Section FUP (Fair Usage Policy) -->
                    <div class="border-t border-gray-200 dark:border-[#30363d] pt-4 mt-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <?= __('pppoe.fup_title')?>
                            </h4>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="profileForm.fup_enabled" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 dark:peer-focus:ring-primary-600 rounded-full peer dark:bg-[#21262d] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-[#30363d] peer-checked:bg-primary-600">
                                </div>
                            </label>
                        </div>

                        <div x-show="profileForm.fup_enabled" x-transition
                            class="space-y-3 bg-amber-50 dark:bg-amber-900/10 p-3 rounded-lg">
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                <?= __('pppoe.fup_description')?>
                            </p>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('pppoe.fup_monthly_quota')?>
                                </label>
                                <div class="flex gap-2">
                                    <input type="number" x-model="profileForm.fup_quota_value" min="1" step="1"
                                        placeholder="50"
                                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                    <select x-model="profileForm.fup_quota_unit"
                                        class="w-20 px-2 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                        <option value="MB">
                                            <?= __('pppoe.unit_mb')?>
                                        </option>
                                        <option value="GB">
                                            <?= __('pppoe.unit_gb')?>
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('pppoe.fup_download')?>
                                    </label>
                                    <input type="number" x-model="profileForm.fup_download_mbps" min="0.1" step="0.1"
                                        placeholder="1"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('pppoe.fup_upload')?>
                                    </label>
                                    <input type="number" x-model="profileForm.fup_upload_mbps" min="0.1" step="0.1"
                                        placeholder="0.5"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('pppoe.fup_reset_day')?>
                                    </label>
                                    <select x-model="profileForm.fup_reset_day"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                        <template x-for="day in 28" :key="day">
                                            <option :value="day" x-text="day"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('pppoe.fup_reset_type')?>
                                    </label>
                                    <select x-model="profileForm.fup_reset_type"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                        <option value="monthly">
                                            <?= __('pppoe.fup_reset_calendar')?>
                                        </option>
                                        <option value="billing_cycle">
                                            <?= __('pppoe.fup_reset_billing')?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" x-model="profileForm.is_active" id="profile_active"
                            class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                        <label for="profile_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            <?= __('pppoe.profile_active')?>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showProfileModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <span x-text="editingProfile ? __('pppoe.modify') : __('common.create')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Génération en lot -->
    <div x-show="showBatchModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showBatchModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <?= __('pppoe.batch_generate')?>
                </h3>

                <form @submit.prevent="generateBatch()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('pppoe.batch_profile')?>
                        </label>
                        <select x-model="batchForm.profile_id" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <option value="">
                                <?= __('pppoe.batch_select_profile')?>
                            </option>
                            <template x-for="profile in profiles" :key="profile.id">
                                <option :value="profile.id" x-text="profile.name"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('pppoe.batch_count')?>
                            </label>
                            <input type="number" x-model="batchForm.count" min="1" max="100"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('pppoe.batch_prefix')?>
                            </label>
                            <input type="text" x-model="batchForm.prefix" maxlength="10"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showBatchModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <?= __('pppoe.generate')?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Renouvellement -->
    <div x-show="showRenewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showRenewModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full">
                <div class="px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <?= __('pppoe.renew_title') ?>
                        </h3>
                        <button @click="showRenewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Résumé client -->
                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-4 mb-4" x-show="renewingUser">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400"><?= __('pppoe.renew_client') ?></span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="renewingUser?.customer_name || renewingUser?.username"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400"><?= __('pppoe.renew_profile') ?></span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="getRenewProfile()?.name || '-'"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400"><?= __('pppoe.renew_duration') ?></span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="(getRenewProfile()?.validity_days || 30) + ' <?= __('pppoe.days') ?>'"></span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-[#30363d] pt-2 mt-2 flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300 font-medium"><?= __('pppoe.renew_price') ?></span>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400" x-text="formatPrice(getRenewProfile()?.price || 0)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire paiement -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe.renew_payment_method') ?> *</label>
                            <select x-model="renewForm.payment_method"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="cash"><?= __('pppoe_user.pay_cash') ?></option>
                                <option value="mobile_money"><?= __('pppoe_user.pay_mobile_money') ?></option>
                                <option value="bank_transfer"><?= __('pppoe_user.pay_bank_transfer') ?></option>
                                <option value="card"><?= __('pppoe_user.pay_card') ?></option>
                                <option value="other"><?= __('pppoe_user.pay_other') ?></option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe.renew_reference') ?></label>
                            <input type="text" x-model="renewForm.reference"
                                   placeholder="<?= __('pppoe.renew_reference_placeholder') ?>"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe.renew_notes') ?></label>
                            <textarea x-model="renewForm.notes" rows="2"
                                      class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-b-xl flex justify-end gap-3">
                    <button type="button" @click="showRenewModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg transition-colors">
                        <?= __('common.cancel') ?>
                    </button>
                    <button type="button" @click="confirmRenew()" :disabled="processingRenew"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors">
                        <svg x-show="processingRenew" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <?= __('pppoe.renew_confirm') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function pppoePage() {
        return {
            activeTab: 'users',
            loading: false,
            users: [],
            profiles: [],
            stats: {},

            // Pagination
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 20,

            // Filtres
            search: '',
            statusFilter: '',
            profileFilter: '',

            // Modals
            showProfileModal: false,
            showBatchModal: false,
            showRenewModal: false,
            editingProfile: null,
            renewingUser: null,
            processingRenew: false,
            renewForm: {
                payment_method: 'cash',
                reference: '',
                notes: ''
            },

            // Bandwidth Policies
            bandwidthPolicies: [],
            selectedPolicyDownload: 0,
            selectedPolicyUpload: 0,
            selectedPolicyBurstDownload: 0,
            selectedPolicyBurstUpload: 0,
            selectedPolicyBurstTime: 0,

            // Forms
            profileForm: {
                name: '',
                description: '',
                bandwidth_policy_id: '',
                download_mbps: 5,
                upload_mbps: 2,
                validity_days: 30,
                price: 10000,
                ip_pool_name: '',
                mikrotik_group: '',
                is_active: true,
                // FUP fields
                fup_enabled: false,
                fup_quota_value: 50,
                fup_quota_unit: 'GB',
                fup_download_mbps: 1,
                fup_upload_mbps: 0.5,
                fup_reset_day: 1,
                fup_reset_type: 'monthly'
            },
            batchForm: {
                profile_id: '',
                count: 10,
                prefix: 'PPP'
            },

            // Sessions
            sessions: [],
            sessionStats: {},
            loadingSessions: false,
            sessionSearch: '',
            sessionStatusFilter: '',
            sessionPage: 1,
            sessionTotalPages: 1,
            sessionTotal: 0,

            async init() {
                await Promise.all([
                    this.loadStats(),
                    this.loadProfiles(),
                    this.loadUsers(),
                    this.loadSessionStats(),
                    this.loadBandwidthPolicies()
                ]);
            },

            async loadBandwidthPolicies() {
                try {
                    const response = await API.get('/bandwidth/policies');
                    this.bandwidthPolicies = response.data || [];
                } catch (error) {
                    console.error('Error loading bandwidth policies:', error);
                }
            },

            onBandwidthPolicyChange() {
                if (this.profileForm.bandwidth_policy_id) {
                    const policy = this.bandwidthPolicies.find(p => p.id == this.profileForm.bandwidth_policy_id);
                    if (policy) {
                        this.selectedPolicyDownload = policy.download_rate;
                        this.selectedPolicyUpload = policy.upload_rate;
                        this.selectedPolicyBurstDownload = policy.burst_download_rate || 0;
                        this.selectedPolicyBurstUpload = policy.burst_upload_rate || 0;
                        this.selectedPolicyBurstTime = policy.burst_time || 0;
                    }
                } else {
                    this.selectedPolicyDownload = 0;
                    this.selectedPolicyUpload = 0;
                    this.selectedPolicyBurstDownload = 0;
                    this.selectedPolicyBurstUpload = 0;
                    this.selectedPolicyBurstTime = 0;
                }
            },

            async loadStats() {
                try {
                    const response = await API.get('/pppoe/stats');
                    this.stats = response.data || {};
                } catch (error) {
                    console.error('Error loading stats:', error);
                }
            },

            async loadProfiles() {
                try {
                    const response = await API.get('/pppoe/profiles');
                    this.profiles = response.data || [];
                } catch (error) {
                    console.error('Error loading profiles:', error);
                }
            },

            async loadUsers() {
                this.loading = true;
                try {
                    let url = `/pppoe/users?page=${this.currentPage}&per_page=${this.perPage}`;
                    if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                    if (this.statusFilter) url += `&status=${this.statusFilter}`;
                    if (this.profileFilter) url += `&profile_id=${this.profileFilter}`;

                    const response = await API.get(url);
                    this.users = response.data?.data || [];
                    this.totalItems = response.data?.total || 0;
                    this.totalPages = response.data?.total_pages || 1;
                } catch (error) {
                    console.error('Error loading users:', error);
                    showToast(__('pppoe.msg_load_error'), 'error');
                } finally {
                    this.loading = false;
                }
            },

            // User actions
            renewUser(user) {
                this.renewingUser = user;
                this.renewForm = { payment_method: 'cash', reference: '', notes: '' };
                this.showRenewModal = true;
            },

            getRenewProfile() {
                if (!this.renewingUser) return null;
                return this.profiles.find(p => p.id == this.renewingUser.profile_id) || null;
            },

            async confirmRenew() {
                if (!this.renewingUser) return;
                this.processingRenew = true;
                try {
                    await API.post(`/pppoe/users/${this.renewingUser.id}/renew`, this.renewForm);
                    showToast(__('pppoe.msg_renewed'), 'success');
                    this.showRenewModal = false;
                    await this.loadUsers();
                    await this.loadStats();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                } finally {
                    this.processingRenew = false;
                }
            },

            async suspendUser(user) {
                if (!confirm(__('pppoe.confirm_suspend').replace(':name', user.customer_name || user.username))) return;

                try {
                    const response = await API.post(`/pppoe/users/${user.id}/suspend`);
                    showToast(response.message || __('pppoe.msg_suspended'), 'success');
                    await this.loadUsers();
                    await this.loadStats();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            async activateUser(user) {
                try {
                    await API.post(`/pppoe/users/${user.id}/activate`);
                    showToast(__('pppoe.msg_activated'), 'success');
                    await this.loadUsers();
                    await this.loadStats();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            async deleteUser(user) {
                if (!confirm(__('pppoe.confirm_delete').replace(':name', user.customer_name || user.username))) return;

                try {
                    await API.delete(`/pppoe/users/${user.id}`);
                    showToast(__('pppoe.msg_deleted'), 'success');
                    await this.loadUsers();
                    await this.loadStats();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            async generateBatch() {
                try {
                    const response = await API.post('/pppoe/users/batch', this.batchForm);
                    showToast(__('pppoe.msg_batch_created').replace(':count', response.data?.created || 0), 'success');
                    this.showBatchModal = false;
                    await this.loadUsers();
                    await this.loadStats();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            // Profile actions
            openCreateProfileModal() {
                this.editingProfile = null;
                this.profileForm = {
                    name: '',
                    description: '',
                    bandwidth_policy_id: '',
                    download_mbps: 5,
                    upload_mbps: 2,
                    validity_days: 30,
                    price: 10000,
                    ip_pool_name: '',
                    mikrotik_group: '',
                    simultaneous_use: 1,
                    is_active: true,
                    // FUP fields
                    fup_enabled: false,
                    fup_quota_value: 50,
                    fup_quota_unit: 'GB',
                    fup_download_mbps: 1,
                    fup_upload_mbps: 0.5,
                    fup_reset_day: 1,
                    fup_reset_type: 'monthly'
                };
                this.selectedPolicyDownload = 0;
                this.selectedPolicyUpload = 0;
                this.selectedPolicyBurstDownload = 0;
                this.selectedPolicyBurstUpload = 0;
                this.selectedPolicyBurstTime = 0;
                this.showProfileModal = true;
            },

            editProfile(profile) {
                this.editingProfile = profile;

                // Convertir le quota en valeur et unité appropriées
                let quotaValue = 50, quotaUnit = 'GB';
                if (profile.fup_quota) {
                    const quotaBytes = profile.fup_quota;
                    const quotaGB = quotaBytes / 1073741824; // 1024^3
                    const quotaMB = quotaBytes / 1048576; // 1024^2

                    // Si le quota est inférieur à 1 Go, afficher en Mo
                    if (quotaGB < 1) {
                        quotaValue = Math.round(quotaMB);
                        quotaUnit = 'MB';
                    } else {
                        quotaValue = Math.round(quotaGB);
                        quotaUnit = 'GB';
                    }
                }

                this.profileForm = {
                    name: profile.name,
                    description: profile.description || '',
                    bandwidth_policy_id: profile.bandwidth_policy_id ? String(profile.bandwidth_policy_id) : '',
                    download_mbps: Math.round(profile.download_speed / 1048576),
                    upload_mbps: Math.round(profile.upload_speed / 1048576),
                    validity_days: profile.validity_days,
                    price: profile.price,
                    ip_pool_name: profile.ip_pool_name || '',
                    mikrotik_group: profile.mikrotik_group || '',
                    simultaneous_use: profile.simultaneous_use || 1,
                    is_active: profile.is_active == 1,
                    // FUP fields
                    fup_enabled: profile.fup_enabled == 1,
                    fup_quota_value: quotaValue,
                    fup_quota_unit: quotaUnit,
                    fup_download_mbps: profile.fup_download_speed ? (profile.fup_download_speed / 1048576) : 1,
                    fup_upload_mbps: profile.fup_upload_speed ? (profile.fup_upload_speed / 1048576) : 0.5,
                    fup_reset_day: profile.fup_reset_day || 1,
                    fup_reset_type: profile.fup_reset_type || 'calendar'
                };
                // Initialiser les vitesses de la politique si sélectionnée
                this.onBandwidthPolicyChange();
                this.showProfileModal = true;
            },

            async saveProfile() {
                try {
                    // Si une politique est sélectionnée, utiliser ses vitesses et burst
                    let downloadSpeed, uploadSpeed, burstDownload, burstUpload, burstTime;
                    if (this.profileForm.bandwidth_policy_id) {
                        const policy = this.bandwidthPolicies.find(p => p.id == this.profileForm.bandwidth_policy_id);
                        if (policy) {
                            downloadSpeed = policy.download_rate;
                            uploadSpeed = policy.upload_rate;
                            burstDownload = policy.burst_download_rate || 0;
                            burstUpload = policy.burst_upload_rate || 0;
                            burstTime = policy.burst_time || 0;
                        } else {
                            downloadSpeed = this.profileForm.download_mbps * 1048576;
                            uploadSpeed = this.profileForm.upload_mbps * 1048576;
                            burstDownload = 0;
                            burstUpload = 0;
                            burstTime = 0;
                        }
                    } else {
                        downloadSpeed = this.profileForm.download_mbps * 1048576;
                        uploadSpeed = this.profileForm.upload_mbps * 1048576;
                        burstDownload = 0;
                        burstUpload = 0;
                        burstTime = 0;
                    }

                    // Convertir le quota en bytes selon l'unité
                    let fupQuotaBytes = null;
                    if (this.profileForm.fup_enabled) {
                        const quotaValue = parseFloat(this.profileForm.fup_quota_value) || 0;
                        if (this.profileForm.fup_quota_unit === 'GB') {
                            fupQuotaBytes = quotaValue * 1073741824; // Go en bytes
                        } else {
                            fupQuotaBytes = quotaValue * 1048576; // Mo en bytes
                        }
                    }

                    const data = {
                        ...this.profileForm,
                        bandwidth_policy_id: this.profileForm.bandwidth_policy_id || null,
                        download_speed: downloadSpeed,
                        upload_speed: uploadSpeed,
                        burst_download: burstDownload,
                        burst_upload: burstUpload,
                        burst_time: burstTime,
                        // FUP data conversion (Mo/Go to bytes, Mbps to bps)
                        fup_quota: fupQuotaBytes,
                        fup_download_speed: this.profileForm.fup_enabled ? this.profileForm.fup_download_mbps * 1048576 : null,
                        fup_upload_speed: this.profileForm.fup_enabled ? this.profileForm.fup_upload_mbps * 1048576 : null
                    };

                    if (this.editingProfile) {
                        await API.put(`/pppoe/profiles/${this.editingProfile.id}`, data);
                        showToast(__('pppoe.msg_profile_updated'), 'success');
                    } else {
                        await API.post('/pppoe/profiles', data);
                        showToast(__('pppoe.msg_profile_created'), 'success');
                    }

                    this.showProfileModal = false;
                    await this.loadProfiles();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            async deleteProfile(profile) {
                if (!confirm(__('pppoe.confirm_delete_profile').replace(':name', profile.name))) return;

                try {
                    await API.delete(`/pppoe/profiles/${profile.id}`);
                    showToast(__('pppoe.msg_profile_deleted'), 'success');
                    await this.loadProfiles();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            // Pagination
            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.loadUsers();
                }
            },

            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.loadUsers();
                }
            },

            // Helpers
            formatSpeed(bps) {
                if (!bps) return '0';
                const mbps = bps / 1048576;
                if (mbps >= 1) return mbps.toFixed(0) + ' Mbps';
                const kbps = bps / 1024;
                return kbps.toFixed(0) + ' Kbps';
            },

            formatBytes(bytes) {
                if (!bytes) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let i = 0;
                while (bytes >= 1024 && i < units.length - 1) {
                    bytes /= 1024;
                    i++;
                }
                return bytes.toFixed(1) + ' ' + units[i];
            },

            formatDuration(seconds) {
                if (!seconds) return '0s';
                const days = Math.floor(seconds / 86400);
                const hours = Math.floor((seconds % 86400) / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;

                let result = [];
                if (days > 0) result.push(days + 'j');
                if (hours > 0) result.push(hours + 'h');
                if (minutes > 0) result.push(minutes + 'm');
                if (secs > 0 && days === 0) result.push(secs + 's');

                return result.join(' ') || '0s';
            },

            formatPrice(price) {
                return new Intl.NumberFormat('fr-FR').format(price || 0) + ' ' + APP_CURRENCY;
            },

            formatDate(date) {
                if (!date) return '-';
                return new Date(date).toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            },

            getDaysRemaining(date) {
                if (!date) return '';
                const diff = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24));
                if (diff < 0) return __('pppoe.days_expired');
                if (diff === 0) return __('pppoe.days_expires_today');
                if (diff === 1) return __('pppoe.days_remaining_one');
                return __('pppoe.days_remaining').replace(':count', diff);
            },

            isExpiringSoon(date) {
                if (!date) return false;
                const diff = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24));
                return diff <= 7;
            },

            getDataUsagePercent(user) {
                if (!user.profile_data_limit || user.profile_data_limit == 0) return 0;
                return Math.round((user.data_used / user.profile_data_limit) * 100);
            },

            getStatusClass(status) {
                const classes = {
                    'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'suspended': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                    'expired': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'disabled': 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-400'
                };
                return classes[status] || classes['disabled'];
            },

            getStatusLabel(status) {
                const labels = {
                    'active': __('pppoe.status_active'),
                    'suspended': __('pppoe.status_suspended'),
                    'expired': __('pppoe.status_expired'),
                    'disabled': __('pppoe.status_disabled')
                };
                return labels[status] || status;
            },

            copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast(__('pppoe.msg_copied'), 'success');
                });
            },

            // ==========================================
            // Sessions Management
            // ==========================================

            async loadSessionStats() {
                try {
                    const response = await API.get('/pppoe/sessions/stats');
                    this.sessionStats = response.data || {};
                } catch (error) {
                    console.error('Error loading session stats:', error);
                }
            },

            async loadSessions() {
                this.loadingSessions = true;
                try {
                    let url = `/pppoe/sessions?page=${this.sessionPage}&per_page=20`;
                    if (this.sessionSearch) url += `&search=${encodeURIComponent(this.sessionSearch)}`;
                    if (this.sessionStatusFilter) url += `&status=${this.sessionStatusFilter}`;

                    const response = await API.get(url);
                    this.sessions = response.data?.data || [];
                    this.sessionTotal = response.data?.total || 0;
                    this.sessionTotalPages = response.data?.total_pages || 1;

                    // Recharger aussi les stats
                    await this.loadSessionStats();
                } catch (error) {
                    console.error('Error loading sessions:', error);
                    this.sessions = [];
                } finally {
                    this.loadingSessions = false;
                }
            },

            async terminateSession(session) {
                if (!confirm(__('pppoe.confirm_terminate_session').replace(':name', session.username))) return;

                try {
                    await API.delete(`/pppoe/sessions/${session.id}`);
                    showToast(__('pppoe.msg_session_terminated'), 'success');
                    await this.loadSessions();
                    await this.loadStats();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            async cleanupOrphanedSessions() {
                if (!confirm(__('pppoe.confirm_cleanup').replace(':count', this.sessionStats.orphaned_sessions))) return;

                try {
                    const response = await API.post('/pppoe/sessions/cleanup', { minutes: 10 });
                    showToast(__('pppoe.msg_sessions_cleaned').replace(':count', response.data?.cleaned || 0), 'success');
                    await this.loadSessions();
                    await this.loadStats();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            async terminateAllSessions() {
                if (!confirm(__('pppoe.confirm_terminate_all').replace(':count', this.sessionStats.active_sessions))) return;

                try {
                    const response = await API.post('/pppoe/sessions/terminate-all');
                    showToast(__('pppoe.msg_sessions_terminated').replace(':count', response.data?.terminated || 0), 'success');
                    await this.loadSessions();
                    await this.loadStats();
                } catch (error) {
                    showToast(error.message || __('notify.error'), 'error');
                }
            },

            prevSessionPage() {
                if (this.sessionPage > 1) {
                    this.sessionPage--;
                    this.loadSessions();
                }
            },

            nextSessionPage() {
                if (this.sessionPage < this.sessionTotalPages) {
                    this.sessionPage++;
                    this.loadSessions();
                }
            },

            formatDateTime(dateStr) {
                if (!dateStr) return '-';
                return new Date(dateStr).toLocaleString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        };
    }
</script>